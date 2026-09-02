<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AgrupacionTablero;
use App\Models\Tarea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Agrupa tareas en columnas para el tablero y la lista agrupada.
 *
 * **El número de consultas no crece con el número de columnas.** La forma
 * ingenua —una consulta por columna, más otra por las relaciones de cada
 * tarea— pasa de seis consultas agrupando por estado a más de veinte agrupando
 * por módulo, y el tablero se vuelve más lento cuanto mejor está organizado el
 * proyecto, que es exactamente al revés de lo que debería.
 *
 * Aquí son siempre cuatro, agrupe por lo que agrupe:
 *
 * 1. Las tareas de todas las columnas, recortadas a `LIMITE` cada una con una
 *    función de ventana (`ROW_NUMBER() OVER (PARTITION BY …)`).
 * 2. El total real de cada columna, que **no** es el número de tareas cargadas:
 *    sin él, una columna con doscientas entregadas diría «50».
 * 3 y 4. Las relaciones (`modulo`, `periodo`) en bloque.
 */
final class ArmarTablero
{
    /**
     * Tareas que se cargan por columna al abrir el tablero.
     *
     * Una columna con doscientas entregadas no se trae entera: nadie las mira,
     * y arrastrar se vuelve pesado mucho antes de llegar al final.
     */
    public const LIMITE = 50;

    /**
     * @param  Builder<Tarea>  $filtradas  Consulta ya acotada por `visiblesPara` y por los filtros.
     * @param  array<int, array{clave: string, etiqueta: string}>  $columnas  Las columnas a pintar, en su orden.
     * @return array<int, array{clave: string, etiqueta: string, total: int, tareas: array<int, array<string, mixed>>}>
     */
    public function __invoke(
        Builder $filtradas,
        AgrupacionTablero $agrupacion,
        array $columnas,
        int $limite = self::LIMITE,
    ): array {
        $campo = $agrupacion->columna();

        $tareas = $this->primerasDeCadaColumna($filtradas, $campo, $limite);
        $totales = $this->totales($filtradas, $campo);

        $porColumna = $tareas->groupBy(
            static fn (Tarea $tarea): string => self::clave($tarea->getAttribute($campo)),
        );

        return array_map(static fn (array $columna): array => [
            'clave' => $columna['clave'],
            'etiqueta' => $columna['etiqueta'],
            'total' => $totales[$columna['clave']] ?? 0,
            'tareas' => ($porColumna[$columna['clave']] ?? collect())
                ->map(static fn (Tarea $tarea): array => self::comoTarjeta($tarea))
                ->values()
                ->all(),
        ], $columnas);
    }

    /**
     * Una página de una sola columna. Es lo que sirve al «cargar más».
     *
     * @param  Builder<Tarea>  $filtradas
     * @return array<int, array<string, mixed>>
     */
    public function pagina(
        Builder $filtradas,
        AgrupacionTablero $agrupacion,
        string $clave,
        int $desde,
        int $limite = self::LIMITE,
    ): array {
        $campo = $agrupacion->columna();

        $consulta = $clave === ''
            ? $filtradas->whereNull($campo)
            : $filtradas->where($campo, $clave);

        return $consulta
            ->with(['modulo:id,nombre', 'periodo:id,consecutivo'])
            ->orderBy('orden')
            ->orderBy('id')
            ->skip($desde)
            ->take($limite)
            ->get()
            ->map(static fn (Tarea $tarea): array => self::comoTarjeta($tarea))
            ->all();
    }

    /**
     * Las `$limite` primeras de **cada** columna, en una sola consulta.
     *
     * Con `LIMIT` por columna harían falta tantas consultas como columnas. La
     * función de ventana numera las filas dentro de cada partición y descarta
     * las que pasan del tope, así que la consulta es una sea cual sea el número
     * de columnas. MySQL 8.4 las soporta desde la 8.0.
     *
     * @param  Builder<Tarea>  $filtradas
     * @return Collection<int, Tarea>
     */
    private function primerasDeCadaColumna(Builder $filtradas, string $campo, int $limite): Collection
    {
        $numeradas = DB::query()
            ->fromSub(
                $filtradas->clone()->select('tareas.*')->reorder()->toBase(),
                'filtradas',
            )
            ->select('*')
            ->selectRaw("ROW_NUMBER() OVER (PARTITION BY `{$campo}` ORDER BY `orden` ASC, `id` ASC) AS numero_en_columna");

        $filas = DB::query()
            ->fromSub($numeradas, 'numeradas')
            ->where('numero_en_columna', '<=', $limite)
            ->get();

        // `hydrate` y no `get()` sobre el modelo: la consulta ya salió del
        // builder de Eloquent al envolverse en la subconsulta.
        $tareas = Tarea::hydrate($filas->map(static fn (object $fila): array => (array) $fila)->all());

        // Dos consultas más, constantes: sin esto sería una por tarjeta.
        $tareas->load(['modulo:id,nombre', 'periodo:id,consecutivo']);

        return $tareas;
    }

    /**
     * El total **real** de cada columna, no el de las cargadas.
     *
     * Es la diferencia entre «50» y «50 de 218», y sin ella el tablero miente
     * sobre cuánto trabajo hay en cada sitio.
     *
     * @param  Builder<Tarea>  $filtradas
     * @return array<string, int>
     */
    private function totales(Builder $filtradas, string $campo): array
    {
        return $filtradas->clone()
            ->reorder()
            ->toBase()
            ->select($campo, DB::raw('count(*) as total'))
            ->groupBy($campo)
            ->get()
            ->mapWithKeys(static fn (object $fila): array => [
                self::clave($fila->{$campo}) => (int) $fila->total,
            ])
            ->all();
    }

    /**
     * La clave de una columna, siempre cadena.
     *
     * `null` es una columna legítima —tareas sin módulo— y se representa con la
     * cadena vacía: un `null` como clave de array en PHP se convierte en `''`
     * por su cuenta, y hacerlo explícito evita que las dos representaciones
     * convivan.
     */
    private static function clave(mixed $valor): string
    {
        if ($valor === null) {
            return '';
        }

        return $valor instanceof \BackedEnum ? (string) $valor->value : (string) $valor;
    }

    /**
     * @return array<string, mixed>
     */
    private static function comoTarjeta(Tarea $tarea): array
    {
        return [
            'id' => $tarea->id,
            'codigo' => $tarea->codigo,
            'nombre' => $tarea->nombre,
            'estado' => $tarea->estado->value,
            'tipo' => $tarea->tipo->value,
            'prioridad' => $tarea->prioridad->value,
            'modulo_id' => $tarea->modulo_id,
            'modulo' => $tarea->modulo?->nombre,
            'periodo' => $tarea->periodo?->consecutivo,
            'arrastrada' => $tarea->estaArrastrada(),
            'fecha_fin' => $tarea->fecha_fin?->toDateString(),
            'orden' => $tarea->orden,
            // La matriz la da el servidor. Replicarla en Vue sería una cuarta
            // fuente de verdad, y la única que ninguna prueba comprueba.
            'transiciones' => CambiarEstadoTarea::disponiblesPara($tarea->estado),
        ];
    }
}
