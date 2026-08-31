<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permiso;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\ResolverRolProyecto;

/**
 * El cliente sí ve los periodos —son el marco de su reporte— pero **no ve el
 * pago** que cuelga de ellos. Eso lo decide `PagoPolicy`.
 */
class PeriodoPolicy
{
    public function viewAny(User $usuario, Proyecto $proyecto): bool
    {
        return ResolverRolProyecto::participa($usuario, $proyecto);
    }

    public function view(User $usuario, Periodo $periodo): bool
    {
        return ResolverRolProyecto::participa($usuario, $periodo->proyecto);
    }

    public function create(User $usuario, Proyecto $proyecto): bool
    {
        return ResolverRolProyecto::gestiona($usuario, $proyecto);
    }

    public function update(User $usuario, Periodo $periodo): bool
    {
        return ResolverRolProyecto::gestiona($usuario, $periodo->proyecto);
    }

    /**
     * El cierre es manual y es del líder: nunca lo hace el sistema solo.
     */
    public function cerrar(User $usuario, Periodo $periodo): bool
    {
        return ResolverRolProyecto::gestiona($usuario, $periodo->proyecto)
            && $usuario->can(Permiso::PeriodosCerrar->value);
    }

    public function delete(User $usuario, Periodo $periodo): bool
    {
        return false;
    }
}
