<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Modulo;
use App\Models\Proyecto;
use Illuminate\Support\Collection;

/**
 * Arma el árbol de módulos de un proyecto y sus conteos recursivos.
 *
 * **Dos consultas, sea cual sea la profundidad**: una para los módulos y otra
 * para las tareas agrupadas por módulo. El árbol se ensambla en memoria.
 *
 * La alternativa obvia —`with('hijos.hijos')`— fija la profundidad en el código
 * y dispara una consulta por nivel; con 33 módulos sembrados no se nota, con
 * 200 y tres niveles sí. Una consulta recursiva en SQL resolvería la carga pero
 * no los conteos acumulados, que igual habría que sumar aquí.
 */
final class ArbolDeModulos
{
    /**
     * Árbol completo, con `tareas_directas`, `tareas_totales` y
     * `submodulos_totales` ya acumulados hacia arriba.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function para(Proyecto $proyecto): array
    {
        $modulos = $proyecto->modulos()
            ->orderBy('orden')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'nombre', 'descripcion', 'orden']);

        $tareasPorModulo = $proyecto->tareas()
            ->whereNotNull('modulo_id')
            ->selectRaw('modulo_id, count(*) as total')
            ->groupBy('modulo_id')
            ->pluck('total', 'modulo_id');

        return self::ramas($modulos, $tareasPorModulo, null);
    }

    /**
     * Ids de todos los descendientes de un módulo, en una sola consulta sobre
     * los módulos del proyecto.
     *
     * @return array<int, int>
     */
    public static function descendientes(Modulo $modulo): array
    {
        $porPadre = $modulo->proyecto->modulos()
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id');

        return self::descendientesDesde($porPadre, $modulo->getKey());
    }

    /**
     * Cuántos submódulos y cuántas tareas quedan afectados al borrar.
     *
     * Los submódulos se van en cascada; las tareas **no se borran**, quedan con
     * `modulo_id` nulo. La confirmación necesita los dos números exactos.
     *
     * @return array{submodulos: int, tareas: int}
     */
    public static function impactoDeBorrar(Modulo $modulo): array
    {
        $descendientes = self::descendientes($modulo);

        return [
            'submodulos' => count($descendientes),
            'tareas' => $modulo->proyecto->tareas()
                ->whereIn('modulo_id', [...$descendientes, $modulo->getKey()])
                ->count(),
        ];
    }

    /**
     * @param  Collection<int, Modulo>  $modulos
     * @param  Collection<int, int>  $tareasPorModulo
     * @return array<int, array<string, mixed>>
     */
    private static function ramas(Collection $modulos, Collection $tareasPorModulo, ?int $padre): array
    {
        return $modulos
            ->where('parent_id', $padre)
            ->values()
            ->map(function (Modulo $modulo) use ($modulos, $tareasPorModulo): array {
                $hijos = self::ramas($modulos, $tareasPorModulo, $modulo->getKey());
                $directas = (int) ($tareasPorModulo[$modulo->getKey()] ?? 0);

                return [
                    'id' => $modulo->getKey(),
                    'parent_id' => $modulo->parent_id,
                    'nombre' => $modulo->nombre,
                    'descripcion' => $modulo->descripcion,
                    'orden' => $modulo->orden,
                    'hijos' => $hijos,
                    'tareas_directas' => $directas,
                    // Incluye las de los descendientes: es el número que
                    // interesa al mirar una rama colapsada.
                    'tareas_totales' => $directas + array_sum(array_column($hijos, 'tareas_totales')),
                    'submodulos_totales' => count($hijos) + array_sum(array_column($hijos, 'submodulos_totales')),
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int|null, Collection<int, Modulo>>  $porPadre
     * @return array<int, int>
     */
    private static function descendientesDesde(Collection $porPadre, int $id): array
    {
        $encontrados = [];

        foreach ($porPadre->get($id, collect()) as $hijo) {
            $encontrados[] = $hijo->getKey();
            $encontrados = [...$encontrados, ...self::descendientesDesde($porPadre, $hijo->getKey())];
        }

        return $encontrados;
    }
}
