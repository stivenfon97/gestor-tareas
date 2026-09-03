<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Visibilidad;
use App\Models\NotaTarea;
use App\Models\Tarea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Sin `index`: las notas de una tarea se leen ya en `TareaController::show`
 * (con el mismo scope `visiblesPara`), que es la única pantalla que las
 * muestra. Un listado aparte —heredado de la fase 04 sobre `recursos/
 * Listado`— nunca tuvo un enlace real que llevara ahí (fase 10, retiro de
 * las pantallas provisionales).
 */
class NotaTareaController extends Controller
{
    /**
     * Una nota nace `interna` salvo que se marque lo contrario: exponerla al
     * cliente es siempre una decisión explícita (D6.1).
     *
     * El editor enriquecido llega en la fase 10; aquí el contenido es texto
     * plano.
     */
    public function store(Request $request, Tarea $tarea): RedirectResponse
    {
        Gate::authorize('create', [NotaTarea::class, $tarea]);

        $datos = $request->validate([
            'contenido' => ['required', 'string', 'max:20000'],
            'visibilidad' => ['required', Rule::enum(Visibilidad::class)],
        ]);

        $tarea->notas()->create($datos + ['usuario_id' => $request->user()->getKey()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('tareas.notas.creada')]);

        return back();
    }
}
