<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permiso;
use App\Enums\RolGlobal;
use App\Models\Cliente;
use App\Models\User;

/**
 * Los clientes son un catálogo global: no cuelgan de ningún proyecto, así que
 * aquí solo manda el rol global.
 */
class ClientePolicy
{
    public function viewAny(User $usuario): bool
    {
        return in_array($usuario->rolGlobal(), [RolGlobal::Administrador, RolGlobal::Miembro], strict: true);
    }

    public function view(User $usuario, Cliente $cliente): bool
    {
        return $this->viewAny($usuario);
    }

    public function create(User $usuario): bool
    {
        return $usuario->can(Permiso::ClientesCrear->value);
    }

    public function update(User $usuario, Cliente $cliente): bool
    {
        return $this->create($usuario);
    }

    public function delete(User $usuario, Cliente $cliente): bool
    {
        return $this->create($usuario);
    }
}
