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
use Inertia\Response;

class NotaTareaController extends Controller
{
    public function index(Request $request, Tarea $tarea): Response
    {
        Gate::authorize('viewAny', [NotaTarea::class, $tarea]);

        return Inertia::render('recursos/Listado', [
            'titulo' => 'recursos.notas',
            'columnas' => ['contenido', 'visibilidad'],
            // El scope, no la policy: la policy solo cubre el acceso por id.
            'filas' => NotaTarea::query()
                ->visiblesPara($request->user())
                ->where('tarea_id', $tarea->getKey())
                ->latest('id')
                ->get(['id', 'contenido', 'visibilidad']),
        ]);
    }

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
