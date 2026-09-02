<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CrearTareasEnLote;
use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Enums\TipoTarea;
use App\Models\Reunion;
use App\Models\Tarea;
use App\Services\ExigirMotivosPorFila;
use App\Services\ParsearTareaRapida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Tareas que salen de una reunión: enlazar las que ya existen, o crear otras
 * nuevas a partir de los acuerdos.
 *
 * **Reutiliza el parser de la fase 06** (`ParsearTareaRapida`,
 * `CrearTareasEnLote`), no lo reimplementa: es el mismo flujo de captura
 * rápida que `CapturaRapidaController`, resuelto sobre el proyecto de la
 * reunión en vez de sobre uno elegido en el URL.
 */
class ReunionTareaController extends Controller
{
    public function vincular(Request $request, Reunion $reunion): RedirectResponse
    {
        Gate::authorize('update', $reunion);

        $datos = $request->validate([
            'tarea_ids' => ['required', 'array', 'min:1'],
            'tarea_ids.*' => [
                'integer',
                Rule::exists('tareas', 'id')->where('proyecto_id', $reunion->proyecto_id),
            ],
        ]);

        $reunion->tareas()->syncWithoutDetaching($datos['tarea_ids']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('reuniones.tareas.vinculadas')]);

        return back();
    }

    public function desvincular(Reunion $reunion, Tarea $tarea): RedirectResponse
    {
        Gate::authorize('update', $reunion);

        $reunion->tareas()->detach($tarea->getKey());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('reuniones.tareas.desvinculada')]);

        return back();
    }

    /**
     * Devuelve lo que se crearía. **No escribe nada.**
     */
    public function previsualizar(Request $request, Reunion $reunion, ParsearTareaRapida $parsear): RedirectResponse
    {
        Gate::authorize('create', [Tarea::class, $reunion->proyecto]);

        $datos = $request->validate([
            'texto' => ['required', 'string', 'max:20000'],
        ]);

        Inertia::flash('previsualizacion', $parsear($reunion->proyecto, $datos['texto']));

        return back();
    }

    /**
     * Crea las tareas confirmadas y las deja vinculadas a la reunión de la
     * que salieron, en el periodo abierto del proyecto — la misma regla que
     * cualquier otra tarea creada por captura rápida.
     */
    public function crear(
        Request $request,
        Reunion $reunion,
        CrearTareasEnLote $crear,
        ExigirMotivosPorFila $exigirMotivos,
    ): RedirectResponse {
        Gate::authorize('create', [Tarea::class, $reunion->proyecto]);

        $datos = $request->validate([
            'filas' => ['required', 'array', 'min:1'],
            'filas.*.nombre' => ['required', 'string', 'max:255'],
            'filas.*.descripcion' => ['nullable', 'string', 'max:20000'],
            'filas.*.estado' => ['required', Rule::enum(EstadoTarea::class)],
            'filas.*.tipo' => ['required', Rule::enum(TipoTarea::class)],
            'filas.*.prioridad' => ['nullable', Rule::enum(PrioridadTarea::class)],
            'filas.*.modulo_id' => [
                'nullable', 'integer',
                Rule::exists('modulos', 'id')->where('proyecto_id', $reunion->proyecto_id),
            ],
            'filas.*.modulo_nuevo' => ['nullable', 'string', 'max:255'],
            'filas.*.motivo_espera' => ['nullable', 'string', 'max:2000'],
            'filas.*.motivo_cancelacion' => ['nullable', 'string', 'max:2000'],
        ]);

        $exigirMotivos($datos['filas']);

        $tareas = $crear($reunion->proyecto, $datos['filas'], $request->user());

        $reunion->tareas()->syncWithoutDetaching(collect($tareas)->pluck('id'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('tareas.rapida.creadas', ['total' => count($tareas)]),
        ]);

        return back();
    }
}
