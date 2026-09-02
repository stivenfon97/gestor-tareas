<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Proyecto;
use Illuminate\Support\Facades\DB;

/**
 * Fija el orden de una columna del tablero en **una sola transacción**.
 *
 * Recibe la columna entera y no la tarjeta movida: arrastrar una tarjeta al
 * segundo puesto cambia la posición de todas las que estaban por debajo, y
 * treinta peticiones seguidas dejan la columna a medias si una falla. Es el
 * mismo criterio que el árbol de módulos de la fase 05.
 *
 * **No toca `estado` ni ningún otro campo de clasificación.** Reordenar es
 * mover dentro de una columna; cambiar de columna es otra operación, y cuando
 * la agrupación es por estado pasa por `CambiarEstadoTarea`.
 */
final class ReordenarTareas
{
    /**
     * @param  array<int, int>  $tareas  Ids en su orden nuevo, de arriba abajo.
     */
    public function __invoke(Proyecto $proyecto, array $tareas): void
    {
        if ($tareas === []) {
            return;
        }

        DB::transaction(function () use ($proyecto, $tareas): void {
            foreach ($tareas as $posicion => $id) {
                // Por el `tareas()` del proyecto, no por `Tarea::whereKey`: la
                // pertenencia se comprueba en la consulta, no solo en la
                // request.
                $proyecto->tareas()
                    ->whereKey($id)
                    ->update(['orden' => $posicion]);
            }
        });
    }
}
