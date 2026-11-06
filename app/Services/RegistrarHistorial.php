<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventoHistorial;
use App\Models\HistorialCambio;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Stringable;

/**
 * Escribe las filas de `historial_cambios`.
 *
 * Es el único sitio que lo hace. Lo llaman `HistorialDeCambiosObserver` (todo
 * guardado por Eloquent), `MiembroDeProyectoObserver` (la membresía) y las
 * escrituras masivas que no disparan eventos (`CerrarPeriodo`,
 * `ReordenarModulos`). Nunca un controlador: el día que alguien no se acuerde
 * de llamarlo, el hueco sería silencioso.
 *
 * Guarda los valores **en crudo** (lo que hay en la columna: el `value` del
 * enum, la fecha como texto, el decimal como cadena), no lo que devuelve el
 * cast. Así la fila no depende de que el enum o el cast sigan existiendo; la
 * traducción a algo legible la hace `PresentarHistorial` al leer.
 *
 * Un campo oculto (`camposOcultosEnHistorial()`) figura en `campos` pero falta
 * en los dos arreglos de valores: se sabe que cambió, nunca a qué.
 */
final class RegistrarHistorial
{
    /**
     * No se registran en ningún modelo: o los cubre el propio evento
     * (`deleted_at`), o son contabilidad del framework, o cambian solos sin
     * que nadie haga nada (`remember_token` en cada inicio de sesión).
     */
    public const CAMPOS_SIEMPRE_EXCLUIDOS = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
    ];

    public function creado(Model $registro): void
    {
        $valores = array_filter(
            $this->sinExcluidos($registro, $registro->getAttributes()),
            static fn (mixed $valor): bool => $valor !== null,
        );

        if ($valores === []) {
            return;
        }

        $this->escribir(
            $registro,
            EventoHistorial::Creado,
            array_keys($valores),
            null,
            $this->sinOcultos($registro, $valores),
        );
    }

    public function actualizado(Model $registro): void
    {
        $nuevos = $this->sinExcluidos($registro, $registro->getChanges());

        if ($nuevos === []) {
            return;
        }

        $previos = $registro->getPrevious();
        $anteriores = [];

        foreach (array_keys($nuevos) as $campo) {
            $anteriores[$campo] = $previos[$campo] ?? null;
        }

        $this->escribir(
            $registro,
            EventoHistorial::Actualizado,
            array_keys($nuevos),
            $this->sinOcultos($registro, $anteriores),
            $this->sinOcultos($registro, $nuevos),
        );
    }

    /**
     * Guarda una foto de cómo estaba el registro: si el borrado es definitivo
     * es lo único que queda de él.
     */
    public function eliminado(Model $registro): void
    {
        $valores = array_filter(
            $this->sinExcluidos($registro, $registro->getRawOriginal()),
            static fn (mixed $valor): bool => $valor !== null,
        );

        $this->escribir(
            $registro,
            EventoHistorial::Eliminado,
            array_keys($valores),
            $this->sinOcultos($registro, $valores),
            null,
        );
    }

    public function restaurado(Model $registro): void
    {
        $this->escribir($registro, EventoHistorial::Restaurado, [], null, null);
    }

    /**
     * Una fila arbitraria sobre un registro. Para lo que no es un guardado
     * del propio modelo: la membresía de un proyecto, por ejemplo.
     *
     * @param  array<int, string>  $campos
     * @param  array<string, mixed>|null  $anteriores
     * @param  array<string, mixed>|null  $nuevos
     */
    public function escribir(
        Model $registro,
        EventoHistorial $evento,
        array $campos,
        ?array $anteriores,
        ?array $nuevos,
    ): void {
        HistorialCambio::query()->create([
            'registro_type' => HistorialCambio::aliasDe($registro::class),
            'registro_id' => $registro->getKey(),
            'evento' => $evento,
            'campos' => array_values($campos),
            'valores_anteriores' => $anteriores === null ? null : $this->normalizar($anteriores),
            'valores_nuevos' => $nuevos === null ? null : $this->normalizar($nuevos),
        ] + $this->autoria());
    }

    /**
     * Varias actualizaciones de un mismo tipo en un solo `INSERT`. Para las
     * escrituras masivas que no pasan por Eloquent y por tanto no disparan el
     * observer.
     *
     * @param  class-string<Model>  $clase
     * @param  array<int, array{id: int, anteriores: array<string, mixed>, nuevos: array<string, mixed>}>  $cambios
     */
    public function actualizacionesMasivas(string $clase, array $cambios): void
    {
        if ($cambios === []) {
            return;
        }

        $alias = HistorialCambio::aliasDe($clase);
        $autoria = $this->autoria();
        $ahora = now();

        $filas = array_map(fn (array $cambio): array => [
            'registro_type' => $alias,
            'registro_id' => $cambio['id'],
            'evento' => EventoHistorial::Actualizado->value,
            'campos' => json_encode(array_keys($cambio['nuevos']), JSON_THROW_ON_ERROR),
            'valores_anteriores' => json_encode($this->normalizar($cambio['anteriores']), JSON_THROW_ON_ERROR),
            'valores_nuevos' => json_encode($this->normalizar($cambio['nuevos']), JSON_THROW_ON_ERROR),
            'usuario_id' => $autoria['usuario_id'],
            'ip' => $autoria['ip'],
            'created_at' => $ahora,
        ], $cambios);

        foreach (array_chunk($filas, 500) as $lote) {
            HistorialCambio::query()->insert($lote);
        }
    }

    /**
     * @return array{usuario_id: int|null, ip: string|null}
     */
    private function autoria(): array
    {
        $id = auth()->id();

        return [
            'usuario_id' => $id === null ? null : (int) $id,
            'ip' => app()->bound('request') ? request()->ip() : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $valores
     * @return array<string, mixed>
     */
    private function sinExcluidos(Model $registro, array $valores): array
    {
        // Los modelos auditados afinan la lista con `TieneHistorialDeCambios`;
        // un modelo sin el trait (el pivote de membresía) usa solo la base.
        $excluidos = self::CAMPOS_SIEMPRE_EXCLUIDOS;

        if (method_exists($registro, 'camposSinHistorial')) {
            $excluidos = [...$excluidos, ...$registro->camposSinHistorial()];
        }

        return array_diff_key($valores, array_flip($excluidos));
    }

    /**
     * @param  array<string, mixed>  $valores
     * @return array<string, mixed>
     */
    private function sinOcultos(Model $registro, array $valores): array
    {
        if (! method_exists($registro, 'camposOcultosEnHistorial')) {
            return $valores;
        }

        return array_diff_key($valores, array_flip($registro->camposOcultosEnHistorial()));
    }

    /**
     * @param  array<string, mixed>  $valores
     * @return array<string, scalar|null>
     */
    private function normalizar(array $valores): array
    {
        return array_map(static fn (mixed $valor): string|int|float|bool|null => match (true) {
            $valor === null, is_scalar($valor) => $valor,
            $valor instanceof BackedEnum => $valor->value,
            $valor instanceof DateTimeInterface => $valor->format('Y-m-d H:i:s'),
            $valor instanceof Stringable => (string) $valor,
            default => json_encode($valor, JSON_THROW_ON_ERROR),
        }, $valores);
    }
}
