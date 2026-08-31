<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permiso;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\ResolverRolProyecto;

class ProyectoPolicy
{
    /**
     * Cualquiera puede pedir su listado: lo que ve dentro lo acota la consulta,
     * que solo devuelve los proyectos donde tiene fila en el pivote.
     */
    public function viewAny(User $usuario): bool
    {
        return $usuario->rolGlobal() !== null;
    }

    public function view(User $usuario, Proyecto $proyecto): bool
    {
        return ResolverRolProyecto::participa($usuario, $proyecto);
    }

    /**
     * Crear proyectos es del administrador, que entra por `Gate::before`.
     */
    public function create(User $usuario): bool
    {
        return false;
    }

    public function update(User $usuario, Proyecto $proyecto): bool
    {
        return ResolverRolProyecto::gestiona($usuario, $proyecto)
            && $usuario->can(Permiso::ProyectosEditar->value);
    }

    public function delete(User $usuario, Proyecto $proyecto): bool
    {
        return false;
    }

    /**
     * Asignar personas al proyecto y cambiarles el rol.
     */
    public function gestionarMiembros(User $usuario, Proyecto $proyecto): bool
    {
        return $this->update($usuario, $proyecto);
    }
}
