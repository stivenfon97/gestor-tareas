<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permiso;
use App\Enums\Visibilidad;
use App\Models\Proyecto;
use App\Models\Reunion;
use App\Models\User;
use App\Services\ResolverRolProyecto;

/**
 * La visibilidad se aplica en **dos sitios a la vez**: aquí, para el acceso
 * directo por id, y en el scope obligatorio de los listados
 * (`Reunion::scopeVisiblesPara`).
 *
 * Solo con la policy, un listado sin filtrar devolvería los títulos de las
 * reuniones internas aunque el detalle diera 403 — y el título ya es
 * información filtrada.
 */
class ReunionPolicy
{
    public function viewAny(User $usuario, Proyecto $proyecto): bool
    {
        return ResolverRolProyecto::participa($usuario, $proyecto);
    }

    public function view(User $usuario, Reunion $reunion): bool
    {
        if (! ResolverRolProyecto::participa($usuario, $reunion->proyecto)) {
            return false;
        }

        return ResolverRolProyecto::veLoInterno($usuario, $reunion->proyecto)
            || $reunion->visibilidad === Visibilidad::Cliente;
    }

    public function create(User $usuario, Proyecto $proyecto): bool
    {
        return ResolverRolProyecto::escribe($usuario, $proyecto)
            && $usuario->can(Permiso::ReunionesCrear->value);
    }

    public function update(User $usuario, Reunion $reunion): bool
    {
        return $this->create($usuario, $reunion->proyecto);
    }

    public function delete(User $usuario, Reunion $reunion): bool
    {
        return ResolverRolProyecto::gestiona($usuario, $reunion->proyecto);
    }
}
