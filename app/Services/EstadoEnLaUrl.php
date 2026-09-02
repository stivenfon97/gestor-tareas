<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Lo que toda vista de tareas lee de la URL, sea cual sea su forma.
 *
 * Hoy son los filtros y el criterio de agrupación: el listado, el tablero, la
 * lista agrupada y el calendario los comparten. El orden, las columnas y el
 * tamaño de página son de la tabla y viven en `EstadoDeTabla`.
 *
 * **No filtra ni ordena nada**: de eso se encarga spatie/laravel-query-builder
 * con sus `allowedFilters` y `allowedSorts`. Esto solo lee lo que llegó.
 */
final class EstadoEnLaUrl
{
    /**
     * Filtros activos, tal y como llegaron.
     *
     * @return array<string, string>
     */
    public static function filtros(Request $request): array
    {
        $filtros = $request->input('filter', []);

        if (! is_array($filtros)) {
            return [];
        }

        return array_map(static fn (mixed $valor): string => (string) $valor, $filtros);
    }

    /**
     * Criterio de agrupación pedido, acotado a los admitidos.
     *
     * Un valor desconocido cae en el primero en lugar de dar error: llega de la
     * barra de direcciones y de configuraciones guardadas hace meses, y una
     * vista que no abre porque su agrupación ya no existe es peor que una vista
     * que abre agrupada por estado.
     *
     * @param  array<int, string>  $admitidos
     */
    public static function agrupacion(Request $request, array $admitidos): string
    {
        $pedida = (string) $request->input('agrupar', '');

        return in_array($pedida, $admitidos, true) ? $pedida : $admitidos[0];
    }
}
