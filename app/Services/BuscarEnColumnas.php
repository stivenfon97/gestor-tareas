<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * Búsqueda general de un listado: un término que se busca a la vez en varias
 * columnas, con `OR`, para la caja de búsqueda de `TablaDatos`.
 *
 * Se declara en el controlador con las columnas que recorre:
 *
 *     AllowedFilter::custom('buscar', new BuscarEnColumnas(['nombre', 'cliente.nombre']))
 *
 * Una columna con punto se busca en la relación (`cliente.nombre` es el
 * `nombre` del `cliente`). Las columnas las fija el código, nunca la
 * petición: lo único que llega de fuera es el término, y viaja como parámetro.
 *
 * `%` y `_` se escapan para que se busquen literalmente: sin eso, buscar «_»
 * devolvía todas las filas. El carácter de escape es `!` y se declara con
 * `ESCAPE`, porque la barra invertida no vale igual en los dos motores: MySQL
 * la toma como escape por defecto y SQLite (el de las pruebas) no tiene
 * ninguno.
 *
 * @template TModel of Model
 *
 * @implements Filter<TModel>
 */
final readonly class BuscarEnColumnas implements Filter
{
    /**
     * @param  array<int, string>  $columnas
     */
    public function __construct(private array $columnas) {}

    /**
     * @param  Builder<TModel>  $query
     */
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        // query-builder parte el valor por comas: «Ruiz, Ana» llega como dos
        // piezas y se vuelve a unir para buscar lo que se escribió.
        $termino = trim(is_array($value) ? implode(',', $value) : (string) $value);

        if ($termino === '') {
            return;
        }

        $patron = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $termino).'%';

        $query->where(function (Builder $consulta) use ($patron): void {
            foreach ($this->columnas as $columna) {
                if (str_contains($columna, '.')) {
                    [$relacion, $campo] = explode('.', $columna, 2);
                    $consulta->orWhereHas($relacion, fn (Builder $relacionada) => self::parecido($relacionada, $campo, $patron));

                    continue;
                }

                $consulta->orWhere(fn (Builder $propia) => self::parecido($propia, $columna, $patron));
            }
        });
    }

    /**
     * `columna LIKE ? ESCAPE '!'`. El nombre de la columna lo fija el código y
     * lo envuelve la gramática; el término viaja siempre como parámetro.
     *
     * @param  Builder<Model>  $consulta
     */
    private static function parecido(Builder $consulta, string $columna, string $patron): void
    {
        $envuelta = $consulta->getQuery()->getGrammar()->wrap($consulta->qualifyColumn($columna));

        $consulta->whereRaw("{$envuelta} like ? escape '!'", [$patron]);
    }
}
