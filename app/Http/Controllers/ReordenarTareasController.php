<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ReordenarTareas;
use App\Http\Requests\ReordenarTareasRequest;
use App\Models\Proyecto;
use App\Models\Tarea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Persiste la reordenación de una columna del tablero.
 *
 * Autoriza con `moverEnTablero` y no con `update`: reordenar no edita ningún
 * campo de la tarea salvo su posición, y es lo mismo que hace falta poder hacer
 * para arrastrar entre columnas.
 */
class ReordenarTareasController extends Controller
{
    public function update(
        ReordenarTareasRequest $request,
        Proyecto $proyecto,
        ReordenarTareas $reordenar,
    ): RedirectResponse {
        Gate::authorize('moverEnTablero', [Tarea::class, $proyecto]);

        $reordenar($proyecto, $request->tareas());

        return back();
    }
}
