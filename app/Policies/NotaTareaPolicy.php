<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Visibilidad;
use App\Models\NotaTarea;
use App\Models\Tarea;
use App\Models\User;
use App\Services\ResolverRolProyecto;

/**
 * Mismo reparto que `ReunionPolicy`: la policy cubre el acceso por id y
 * `NotaTarea::scopeVisiblesPara` cubre los listados. Las dos, siempre.
 */
class NotaTareaPolicy
{
    public function viewAny(User $usuario, Tarea $tarea): bool
    {
        return ResolverRolProyecto::participa($usuario, $tarea->proyecto);
    }

    public function view(User $usuario, NotaTarea $nota): bool
    {
        $proyecto = $nota->tarea->proyecto;

        if (! ResolverRolProyecto::participa($usuario, $proyecto)) {
            return false;
        }

        return ResolverRolProyecto::veLoInterno($usuario, $proyecto)
            || $nota->visibilidad === Visibilidad::Cliente;
    }

    public function create(User $usuario, Tarea $tarea): bool
    {
        return ResolverRolProyecto::escribe($usuario, $tarea->proyecto);
    }

    public function update(User $usuario, NotaTarea $nota): bool
    {
        // Una nota la edita quien la escribió, mientras siga pudiendo escribir.
        return $nota->usuario_id === $usuario->getKey()
            && ResolverRolProyecto::escribe($usuario, $nota->tarea->proyecto);
    }

    public function delete(User $usuario, NotaTarea $nota): bool
    {
        return $this->update($usuario, $nota)
            || ResolverRolProyecto::gestiona($usuario, $nota->tarea->proyecto);
    }
}
