<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Traduce los parámetros de URL de un listado a las props que espera el
 * componente `TablaDatos`.
 *
 * Existe porque los tres listados —clientes, proyectos y tareas— necesitan lo
 * mismo, y porque la interfaz no debe tener que descomponer `sort` por su
 * cuenta: si lo hiciera, el formato del parámetro pasaría a ser un contrato
 * implícito entre dos capas que nadie prueba.
 *
 * **Solo lo que es de una tabla.** Los filtros y la agrupación los lee
 * `EstadoEnLaUrl`, que también sirve al tablero, a la lista agrupada y al
 * calendario: ninguno de los tres tiene columnas ni páginas.
 *
 * **No filtra ni ordena nada**: de eso se encarga spatie/laravel-query-builder
 * con sus `allowedFilters` y `allowedSorts`. Esto solo lee lo que llegó.
 */
final class EstadoDeTabla
{
    /** Tamaños de página admitidos. Cualquier otro valor cae en el primero. */
    private const TAMANOS = [30, 15, 50, 100];

    /**
     * Orden aplicado, descompuesto en la lista ordenada de campos que pidió la
     * petición: `sort=-prioridad,codigo` da prioridad descendente primero y
     * código ascendente como desempate.
     *
     * La lista **conserva el orden del parámetro** porque esa es justamente la
     * información que el orden multicolumna necesita: «por estado y luego por
     * código» y «por código y luego por estado» dan resultados distintos.
     *
     * @return array<int, array{campo: string, direccion: string}>
     */
    public static function orden(Request $request, string $porDefecto): array
    {
        $sort = trim((string) $request->input('sort', ''));

        // El valor por defecto se lee igual que el parámetro, guion incluido:
        // el listado global de tareas ordena por `-updated_at`, y sin esto no
        // habría forma de expresar un descendente por defecto.
        $campos = array_filter(array_map('trim', explode(',', $sort === '' ? $porDefecto : $sort)));

        return array_values(array_map(static fn (string $campo): array => [
            'campo' => ltrim($campo, '-'),
            'direccion' => str_starts_with($campo, '-') ? 'desc' : 'asc',
        ], $campos));
    }

    /**
     * Columnas visibles pedidas por la URL.
     *
     * `null` significa «las de por defecto», y quién sean esas es cosa de la
     * interfaz: el servidor no sabe qué columnas existen ni debería saberlo.
     *
     * @return array<int, string>|null
     */
    public static function columnas(Request $request): ?array
    {
        $columnas = trim((string) $request->input('columnas', ''));

        if ($columnas === '') {
            return null;
        }

        return array_values(array_filter(array_map('trim', explode(',', $columnas))));
    }

    /**
     * Tamaño de página, acotado a la lista admitida: sin el tope, un
     * `por_pagina=100000` en la barra de direcciones descarga el proyecto
     * entero de una vez.
     */
    public static function porPagina(Request $request): int
    {
        $pedido = (int) $request->input('por_pagina', self::TAMANOS[0]);

        return in_array($pedido, self::TAMANOS, true) ? $pedido : self::TAMANOS[0];
    }
}
