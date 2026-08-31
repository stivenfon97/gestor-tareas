<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Modulo;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\ResolverRolProyecto;

class ModuloPolicy
{
    public function viewAny(User $usuario, Proyecto $proyecto): bool
    {
        return ResolverRolProyecto::participa($usuario, $proyecto);
    }

    public function view(User $usuario, Modulo $modulo): bool
    {
        return ResolverRolProyecto::participa($usuario, $modulo->proyecto);
    }

    /**
     * La estructura del proyecto la define quien lo lidera.
     */
    public function create(User $usuario, Proyecto $proyecto): bool
    {
        return ResolverRolProyecto::gestiona($usuario, $proyecto);
    }

    public function update(User $usuario, Modulo $modulo): bool
    {
        return ResolverRolProyecto::gestiona($usuario, $modulo->proyecto);
    }

    public function delete(User $usuario, Modulo $modulo): bool
    {
        return $this->update($usuario, $modulo);
    }
}
