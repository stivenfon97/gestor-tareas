<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HistorialCambio;
use App\Models\Modulo;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Reporte;
use App\Models\Reunion;
use App\Models\Tarea;
use App\Models\User;

/**
 * Lo que pinta `layouts/proyecto/Layout.vue`: la cabecera del proyecto y sus
 * pestañas, igual en el resumen y en cada sección (módulos, tareas, periodos…).
 *
 * Cada sección es su propia ruta con su propio controlador; esto es lo único
 * que comparten, y por eso vive en un sitio. Una pestaña se lista si la
 * policy de **su ruta** la deja abrir: la misma pregunta que hace el
 * controlador de destino, así que nunca se ofrece una pestaña que dé 403.
 */
final class PresentarCabeceraDeProyecto
{
    /**
     * @return array{
     *     id: int,
     *     nombre: string,
     *     descripcion: ?string,
     *     prefijo_tarea: string,
     *     estado: string,
     *     cliente: array{id: int, nombre: string},
     *     miRol: ?string,
     *     puede: array{editar: bool, eliminar: bool, verHistorial: bool},
     *     pestanas: array<string, bool>,
     * }
     */
    public static function para(Proyecto $proyecto, User $usuario): array
    {
        $proyecto->loadMissing('cliente:id,nombre');

        return [
            'id' => $proyecto->id,
            'nombre' => $proyecto->nombre,
            'descripcion' => $proyecto->descripcion,
            'prefijo_tarea' => $proyecto->prefijo_tarea,
            'estado' => $proyecto->estado->value,
            'cliente' => ['id' => $proyecto->cliente->id, 'nombre' => $proyecto->cliente->nombre],
            'miRol' => ResolverRolProyecto::para($usuario, $proyecto)?->value,
            'puede' => [
                'editar' => $usuario->can('update', $proyecto),
                'eliminar' => $usuario->can('delete', $proyecto),
                // Solo esconde el botón: la cerradura es `HistorialCambioPolicy@verDe`.
                'verHistorial' => $usuario->can('verDe', [HistorialCambio::class, $proyecto]),
            ],
            'pestanas' => [
                'modulos' => $usuario->can('viewAny', [Modulo::class, $proyecto]),
                'equipo' => $usuario->can('gestionarMiembros', $proyecto),
                'tareas' => $usuario->can('viewAny', [Tarea::class, $proyecto]),
                'periodos' => $usuario->can('viewAny', [Periodo::class, $proyecto]),
                'pagos' => $usuario->can('viewAny', [Pago::class, $proyecto]),
                'bitacora' => $usuario->can('viewAny', [Reunion::class, $proyecto]),
                'reportes' => $usuario->can('viewAny', [Reporte::class, $proyecto]),
            ],
        ];
    }
}
