<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permiso;
use App\Models\Proyecto;
use App\Models\Reporte;
use App\Models\User;
use App\Services\ResolverRolProyecto;

/**
 * Generar un reporte es una acción interna: el cliente **recibe** un archivo,
 * no lo emite. El reporte completo además lleva pagos, así que dejar generarlo
 * a alguien con rol `cliente` sería filtrar dinero por la puerta de atrás.
 */
class ReportePolicy
{
    public function viewAny(User $usuario, Proyecto $proyecto): bool
    {
        return ResolverRolProyecto::veLoInterno($usuario, $proyecto);
    }

    public function view(User $usuario, Reporte $reporte): bool
    {
        return $this->viewAny($usuario, $reporte->proyecto);
    }

    /**
     * Generar deja rastro: inserta una fila en `reportes` con el snapshot. Por
     * eso cuenta como escritura y el observador —que lee, pero no escribe— no
     * puede, igual que un `invitado` global aunque sea líder del proyecto.
     */
    public function create(User $usuario, Proyecto $proyecto): bool
    {
        return ResolverRolProyecto::escribe($usuario, $proyecto)
            && $usuario->can(Permiso::ReportesGenerar->value);
    }

    public function delete(User $usuario, Reporte $reporte): bool
    {
        return false;
    }
}
