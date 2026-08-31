<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\TransicionNoPermitida;
use App\Http\Requests\CambiarEstadoTareaRequest;
use App\Models\Tarea;
use App\Services\CambiarEstadoTarea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Único endpoint que cambia el estado de una tarea.
 *
 * Separado de `TareaController@update` a propósito, y no por simetría REST: si
 * `estado` viajara con el resto de campos del formulario, cualquiera podría
 * saltarse la matriz enviándolo entre los demás. Autoriza además con
 * `cambiarEstado` y no con `update`, porque el Kanban de la fase 08 mueve
 * tarjetas sin editar nada más.
 */
class EstadoTareaController extends Controller
{
    public function update(
        CambiarEstadoTareaRequest $request,
        Tarea $tarea,
        CambiarEstadoTarea $cambiar,
    ): RedirectResponse {
        Gate::authorize('cambiarEstado', $tarea);

        try {
            $cambiar($tarea, $request->destino(), $request->motivos());
        } catch (TransicionNoPermitida $excepcion) {
            // 422 y no 500: es una entrada inválida, no un fallo del sistema.
            throw ValidationException::withMessages([
                'estado' => __('tareas.validacion.transicion_no_permitida', [
                    'desde' => __('enums.estado_tarea.'.$tarea->estado->value),
                    'hacia' => __('enums.estado_tarea.'.$request->input('estado')),
                ]),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('tareas.estado_cambiado')]);

        return back();
    }
}
