<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permiso;
use App\Models\Equipo;
use App\Models\User;

/**
 * Los equipos son estructura de la organización, no de un proyecto: los
 * administra quien gestiona usuarios.
 */
class EquipoPolicy
{
    public function viewAny(User $usuario): bool
    {
        return $usuario->can(Permiso::UsuariosGestionar->value);
    }

    public function view(User $usuario, Equipo $equipo): bool
    {
        return $this->viewAny($usuario);
    }

    public function create(User $usuario): bool
    {
        return $this->viewAny($usuario);
    }

    public function update(User $usuario, Equipo $equipo): bool
    {
        return $this->viewAny($usuario);
    }

    public function delete(User $usuario, Equipo $equipo): bool
    {
        return $this->viewAny($usuario);
    }
}
