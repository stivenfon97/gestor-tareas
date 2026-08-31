<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RolProyecto;
use App\Models\Equipo;
use App\Models\Proyecto;
use Illuminate\Support\Facades\DB;

/**
 * Da de alta en un proyecto a todos los miembros de un equipo, con un rol.
 *
 * Es una operación de **conveniencia, no un vínculo permanente**: al terminar,
 * las filas de `proyecto_usuario` son independientes del equipo. Quitar a
 * alguien del equipo **no** lo saca del proyecto, y eso es intencional — el
 * equipo no otorga permisos por sí mismo, solo ahorra clics al asignarlos.
 */
final class AsignarEquipoAProyecto
{
    /**
     * @return array{agregados: array<int, string>, omitidos: array<int, array{usuario: string, motivo: string, rol: string}>}
     */
    public function __invoke(Equipo $equipo, Proyecto $proyecto, RolProyecto $rol): array
    {
        $agregados = [];
        $omitidos = [];

        DB::transaction(function () use ($equipo, $proyecto, $rol, &$agregados, &$omitidos): void {
            $existentes = $proyecto->usuarios()->pluck('proyecto_usuario.rol', 'users.id');

            foreach ($equipo->usuarios()->get() as $usuario) {
                $rolActual = $existentes[$usuario->getKey()] ?? null;

                if ($rolActual !== null) {
                    // Nunca se sobrescribe: pudieron ascenderlo a líder a
                    // propósito, y una asignación masiva no debe degradarlo.
                    $omitidos[] = [
                        'usuario' => $usuario->name,
                        'motivo' => 'ya_asignado',
                        'rol' => (string) $rolActual,
                    ];

                    continue;
                }

                $proyecto->usuarios()->attach($usuario->getKey(), ['rol' => $rol->value]);
                $agregados[] = $usuario->name;
            }
        });

        return ['agregados' => $agregados, 'omitidos' => $omitidos];
    }
}
