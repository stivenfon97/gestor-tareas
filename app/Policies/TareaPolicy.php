<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permiso;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Models\User;
use App\Services\ResolverRolProyecto;

class TareaPolicy
{
    public function viewAny(User $usuario, Proyecto $proyecto): bool
    {
        return ResolverRolProyecto::participa($usuario, $proyecto);
    }

    public function view(User $usuario, Tarea $tarea): bool
    {
        return ResolverRolProyecto::participa($usuario, $tarea->proyecto);
    }

    public function create(User $usuario, Proyecto $proyecto): bool
    {
        return ResolverRolProyecto::escribe($usuario, $proyecto)
            && $usuario->can(Permiso::TareasCrear->value);
    }

    /**
     * Falso para `cliente` y `observador`: ninguno de los dos escribe trabajo.
     */
    public function update(User $usuario, Tarea $tarea): bool
    {
        return ResolverRolProyecto::escribe($usuario, $tarea->proyecto)
            && $usuario->can(Permiso::TareasEditar->value);
    }

    /**
     * Separado de `update` a propósito: en la fase 06 el Kanban mueve tarjetas
     * sin editar el resto de campos, y ese permiso puede evolucionar aparte.
     */
    public function cambiarEstado(User $usuario, Tarea $tarea): bool
    {
        return ResolverRolProyecto::escribe($usuario, $tarea->proyecto)
            && $usuario->can(Permiso::TareasMover->value);
    }

    /**
     * Mover y reordenar tarjetas en el tablero.
     *
     * Pregunta por el **proyecto** y no por una tarea porque el tablero
     * reordena un conjunto: preguntar por cada tarjeta de la columna sería la
     * misma respuesta treinta veces. Para mover **entre** columnas agrupando
     * por estado manda además `cambiarEstado`, que es lo que autoriza
     * `EstadoTareaController`.
     */
    public function moverEnTablero(User $usuario, Proyecto $proyecto): bool
    {
        return ResolverRolProyecto::escribe($usuario, $proyecto)
            && $usuario->can(Permiso::TareasMover->value);
    }

    public function delete(User $usuario, Tarea $tarea): bool
    {
        return ResolverRolProyecto::gestiona($usuario, $tarea->proyecto);
    }
}
