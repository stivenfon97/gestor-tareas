<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permiso;
use App\Models\Pago;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\ResolverRolProyecto;

/**
 * El dinero no se le muestra al cliente. Nunca.
 *
 * `viewAny` y `view` devuelven falso para cualquier usuario cuyo rol de
 * proyecto sea `cliente`, **sin excepción y sin depender de la interfaz**: no
 * pintar el enlace no es un permiso.
 */
class PagoPolicy
{
    public function viewAny(User $usuario, Proyecto $proyecto): bool
    {
        return ResolverRolProyecto::veLoInterno($usuario, $proyecto)
            && $usuario->can(Permiso::PagosVer->value);
    }

    public function view(User $usuario, Pago $pago): bool
    {
        return $this->viewAny($usuario, $pago->periodo->proyecto);
    }

    public function create(User $usuario, Proyecto $proyecto): bool
    {
        return ResolverRolProyecto::gestiona($usuario, $proyecto)
            && $usuario->can(Permiso::PagosRegistrar->value);
    }

    public function update(User $usuario, Pago $pago): bool
    {
        return $this->create($usuario, $pago->periodo->proyecto);
    }

    public function delete(User $usuario, Pago $pago): bool
    {
        return $this->update($usuario, $pago);
    }
}
