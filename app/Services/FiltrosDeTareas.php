<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Modulo;
use App\Models\Proyecto;
use App\Models\Tarea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Los filtros de tareas, declarados **una sola vez**.
 *
 * Desde la fase 08 hay cuatro formas de ver las mismas tareas —tabla, tablero,
 * lista agrupada y calendario— y las cuatro filtran igual. Un filtro que
 * existiera en el listado y no en el tablero convertiría cambiar de vista en
 * perder lo que habías buscado, que es la peor forma de perderlo: sin aviso.
 *
 * El scope `visiblesPara()` va **antes** que cualquier filtro, sin excepción:
 * sin él, un `filter[proyecto_id]` puesto a mano devuelve tareas de proyectos
 * ajenos.
 */
final class FiltrosDeTareas
{
    /**
     * Tareas de un proyecto concreto.
     *
     * @return QueryBuilder<Tarea>
     */
    public static function paraProyecto(Request $request, Proyecto $proyecto): QueryBuilder
    {
        return QueryBuilder::for(Tarea::class, $request)
            ->visiblesPara($request->user())
            ->where('proyecto_id', $proyecto->getKey())
            ->allowedFilters(
                AllowedFilter::exact('estado'),
                AllowedFilter::exact('tipo'),
                AllowedFilter::exact('prioridad'),
                AllowedFilter::exact('periodo_id'),
                AllowedFilter::callback('modulo_id', self::porModulo(...)),
                AllowedFilter::callback('arrastradas', self::porArrastradas(...)),
                AllowedFilter::callback('texto', self::porTexto(...)),
            );
    }

    /**
     * El filtro por módulo **incluye los submódulos**.
     *
     * Filtrar por «Facturación» y no ver las tareas de «Facturación › Notas de
     * crédito» sería contar mal el trabajo de un módulo: el árbol existe
     * justamente porque un módulo contiene a otros.
     *
     * Un módulo que no existe no devuelve nada, y eso es deliberado: un filtro
     * puesto a mano en la barra de direcciones tiene que dar cero filas, no
     * todas. Lo que sí tolera referencias muertas es aplicar una **vista
     * guardada**, y de eso se encarga `AplicarVistaGuardada` antes de que el
     * filtro llegue hasta aquí.
     *
     * @param  Builder<Tarea>  $consulta
     */
    public static function porModulo(Builder $consulta, mixed $valor): void
    {
        $modulo = Modulo::query()->find((int) $valor);

        if ($modulo === null) {
            $consulta->whereRaw('1 = 0');

            return;
        }

        $consulta->whereIn('modulo_id', [
            $modulo->getKey(),
            ...ArbolDeModulos::descendientes($modulo),
        ]);
    }

    /** @param Builder<Tarea> $consulta */
    public static function porArrastradas(Builder $consulta, mixed $valor): void
    {
        filter_var($valor, FILTER_VALIDATE_BOOLEAN)
            ? $consulta->whereNotNull('periodo_origen_id')
            : $consulta->whereNull('periodo_origen_id');
    }

    /** @param Builder<Tarea> $consulta */
    public static function porTexto(Builder $consulta, mixed $valor): void
    {
        $termino = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $valor).'%';

        $consulta->where(function (Builder $interna) use ($termino): void {
            $interna->where('nombre', 'like', $termino)
                ->orWhere('codigo', 'like', $termino)
                ->orWhere('descripcion', 'like', $termino);
        });
    }
}
