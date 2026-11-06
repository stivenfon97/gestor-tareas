<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HistorialCambio;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * Búsqueda general del historial: por el nombre de quien hizo el cambio y por
 * los valores antes y después («¿quién puso este correo?»).
 *
 * No es `BuscarEnColumnas` porque los valores viven en columnas JSON, y en
 * MySQL un `LIKE` directo sobre JSON compara en binario: distingue mayúsculas
 * y «ana» no encontraría «Ana». `CAST(… AS CHAR)` lo pasa a texto con la
 * collation de la conexión (`utf8mb4_0900_ai_ci`), que no las distingue; en
 * SQLite el mismo `CAST` da texto y la consulta vale igual.
 *
 * Mismo escape que `BuscarEnColumnas`: `%` y `_` se buscan literalmente.
 *
 * @implements Filter<HistorialCambio>
 */
final readonly class BuscarEnHistorial implements Filter
{
    /**
     * @param  Builder<HistorialCambio>  $query
     */
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $termino = trim(is_array($value) ? implode(',', $value) : (string) $value);

        if ($termino === '') {
            return;
        }

        $patron = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $termino).'%';

        $query->where(function (Builder $consulta) use ($patron): void {
            $consulta
                ->whereHas('usuario', fn (Builder $usuario) => $usuario->whereRaw("name like ? escape '!'", [$patron]))
                ->orWhereRaw("cast(valores_anteriores as char) like ? escape '!'", [$patron])
                ->orWhereRaw("cast(valores_nuevos as char) like ? escape '!'", [$patron]);
        });
    }
}
