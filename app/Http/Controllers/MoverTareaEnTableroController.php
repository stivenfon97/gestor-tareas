<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PrioridadTarea;
use App\Models\Proyecto;
use App\Models\Tarea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Mover una tarjeta entre columnas **cuando la columna no es un estado**.
 *
 * Es un endpoint aparte del de estado, y esa separación es la decisión de
 * diseño de la fase 08. Agrupado por estado, soltar una tarjeta es un cambio de
 * estado: valida contra la matriz de once transiciones, exige motivo en dos
 * destinos, rellena fechas automáticas y escribe historial. Agrupado por módulo
 * o por prioridad no hay nada de eso: es un `update` de un campo.
 *
 * **Parecen lo mismo y no lo son.** Unificarlos en un endpoint que reciba
 * «columna destino» obligaría a un `if` dentro para decidir si aplica la matriz,
 * y ese `if` es exactamente lo que un día alguien simplifica.
 *
 * Por tipo no se mueve nada: el tipo de una tarea **se fija al crear y no
 * cambia** (D1.3). El tablero agrupado por tipo se lee y se reordena, pero sus
 * columnas no son destinos.
 */
class MoverTareaEnTableroController extends Controller
{
    /** Los ejes que sí son un campo editable. */
    private const CAMPOS = ['modulo_id', 'prioridad'];

    public function update(Request $request, Proyecto $proyecto, Tarea $tarea): RedirectResponse
    {
        Gate::authorize('moverEnTablero', [Tarea::class, $proyecto]);

        abort_unless($tarea->proyecto_id === $proyecto->getKey(), 404);

        $datos = $request->validate([
            'campo' => ['required', Rule::in(self::CAMPOS)],
            'valor' => ['present', 'nullable'],
            'modulo_id' => [
                Rule::requiredIf($request->input('campo') === 'modulo_id'),
                'nullable',
                'integer',
                Rule::exists('modulos', 'id')->where('proyecto_id', $proyecto->getKey()),
            ],
            'prioridad' => [
                Rule::requiredIf($request->input('campo') === 'prioridad'),
                Rule::in(array_column(PrioridadTarea::cases(), 'value')),
            ],
        ]);

        // El valor viaja en su propio campo, ya validado con sus reglas: un
        // `valor` genérico obligaría a validarlo a mano y a acertar siempre.
        $tarea->update([
            $datos['campo'] => $datos[$datos['campo']] ?? null,
        ]);

        return back();
    }
}
