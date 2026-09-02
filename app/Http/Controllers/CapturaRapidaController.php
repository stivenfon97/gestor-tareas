<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CrearTareasEnLote;
use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Enums\TipoTarea;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Services\ExigirMotivosPorFila;
use App\Services\ParsearTareaRapida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Captura rápida: pegar varias líneas y crear una tarea por línea.
 *
 * Dos pasos separados a propósito. `previsualizar` no escribe nada; `store`
 * recibe las filas **ya revisadas y editadas** por el usuario. Crear
 * directamente desde el texto pegado convertiría cada errata del parser en una
 * tarea que alguien tendría que borrar a mano.
 */
class CapturaRapidaController extends Controller
{
    public function create(Proyecto $proyecto): Response
    {
        Gate::authorize('create', [Tarea::class, $proyecto]);

        return Inertia::render('tareas/CapturaRapida', [
            'proyecto' => $proyecto->only(['id', 'nombre', 'prefijo_tarea']),
            'opciones' => [
                'estados' => $this->comoOpciones(EstadoTarea::cases()),
                'tipos' => $this->comoOpciones(TipoTarea::cases()),
                'prioridades' => $this->comoOpciones(PrioridadTarea::cases()),
            ],
            'periodoAbierto' => $proyecto->periodoAbierto()->value('consecutivo'),
        ]);
    }

    /**
     * Devuelve lo que se crearía. **No escribe nada.**
     */
    public function previsualizar(
        Request $request,
        Proyecto $proyecto,
        ParsearTareaRapida $parsear,
    ): RedirectResponse {
        Gate::authorize('create', [Tarea::class, $proyecto]);

        $datos = $request->validate([
            'texto' => ['required', 'string', 'max:20000'],
        ]);

        Inertia::flash('previsualizacion', $parsear($proyecto, $datos['texto']));

        return back();
    }

    public function store(
        Request $request,
        Proyecto $proyecto,
        CrearTareasEnLote $crear,
        ExigirMotivosPorFila $exigirMotivos,
    ): RedirectResponse {
        Gate::authorize('create', [Tarea::class, $proyecto]);

        $datos = $request->validate([
            'filas' => ['required', 'array', 'min:1'],
            'filas.*.nombre' => ['required', 'string', 'max:255'],
            'filas.*.descripcion' => ['nullable', 'string', 'max:20000'],
            'filas.*.estado' => ['required', Rule::enum(EstadoTarea::class)],
            'filas.*.tipo' => ['required', Rule::enum(TipoTarea::class)],
            'filas.*.prioridad' => ['nullable', Rule::enum(PrioridadTarea::class)],
            'filas.*.modulo_id' => [
                'nullable', 'integer',
                Rule::exists('modulos', 'id')->where('proyecto_id', $proyecto->getKey()),
            ],
            'filas.*.modulo_nuevo' => ['nullable', 'string', 'max:255'],
            // Los motivos se exigen fila a fila según su estado destino: la
            // regla D2.9 vale igual al crear que al transicionar.
            'filas.*.motivo_espera' => ['nullable', 'string', 'max:2000'],
            'filas.*.motivo_cancelacion' => ['nullable', 'string', 'max:2000'],
        ]);

        $exigirMotivos($datos['filas']);

        $tareas = $crear($proyecto, $datos['filas'], $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('tareas.rapida.creadas', ['total' => count($tareas)]),
        ]);

        return to_route('proyectos.tareas.index', $proyecto);
    }

    /**
     * @param  array<int, EstadoTarea|TipoTarea|PrioridadTarea>  $casos
     * @return array<int, array{valor: string, etiqueta: string}>
     */
    private function comoOpciones(array $casos): array
    {
        return array_map(static fn ($caso): array => [
            'valor' => $caso->value,
            'etiqueta' => $caso->etiqueta(),
        ], $casos);
    }
}
