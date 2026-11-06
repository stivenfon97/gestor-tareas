<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permiso;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Modulo;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Reunion;
use App\Models\Tarea;
use App\Models\User;
use App\Services\ResolverRolProyecto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Quién ve el historial de cambios.
 *
 * El historial dice quién cambió qué y a qué valor: es contenido interno. Por
 * eso ver el historial de un registro exige dos cosas a la vez, poder ver el
 * registro (su propia policy) **y** ver lo interno. Un `invitado` global, o
 * alguien con rol de proyecto `cliente`, no lo ve aunque abra la ficha.
 */
class HistorialCambioPolicy
{
    /** La pantalla global: todo el sistema, de todos los proyectos. */
    public function viewAny(User $usuario): bool
    {
        return $usuario->can(Permiso::HistorialVer->value);
    }

    public function verDe(User $usuario, Model $registro): bool
    {
        if ($registro instanceof User) {
            return $usuario->can(Permiso::UsuariosGestionar->value);
        }

        if (! Gate::forUser($usuario)->allows('view', $registro)) {
            return false;
        }

        if ($registro instanceof Cliente || $registro instanceof Equipo) {
            return $usuario->rolGlobal()?->veLoInterno() ?? false;
        }

        $proyecto = self::proyectoDe($registro);

        return $proyecto !== null && ResolverRolProyecto::veLoInterno($usuario, $proyecto);
    }

    private static function proyectoDe(Model $registro): ?Proyecto
    {
        return match (true) {
            $registro instanceof Proyecto => $registro,
            $registro instanceof Modulo, $registro instanceof Tarea,
            $registro instanceof Periodo, $registro instanceof Reunion => $registro->proyecto,
            $registro instanceof Pago => $registro->periodo->proyecto,
            default => null,
        };
    }
}
