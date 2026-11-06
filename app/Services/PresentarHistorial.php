<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventoHistorial;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\HistorialCambio;
use App\Models\Modulo;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\ProyectoUsuario;
use App\Models\Reunion;
use App\Models\Tarea;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;

/**
 * Convierte filas de `historial_cambios` en lo que pinta la interfaz.
 *
 * La fila guarda valores crudos (`en_proceso`, `12`, `2026-09-03 00:00:00`);
 * aquí cada valor recibe un **tipo** que le dice al cliente cómo pintarlo:
 *
 * - `clave`: una clave de traducción (el `etiqueta()` de un enum).
 * - `texto`: texto tal cual (un nombre, o una referencia ya resuelta: `12` →
 *   «Pagos» si `modulo_id` apunta al módulo Pagos).
 * - `fecha`, `fecha_hora`, `monto_usd`, `monto_cop`, `booleano`: los formatea
 *   el cliente con `lib/formato.ts`, según el idioma activo.
 * - `vacio`: no tenía valor. `oculto`: cambió, pero el valor no se registra.
 *
 * Las referencias se resuelven en bloque —una consulta por tipo de modelo
 * para todas las filas de la página—, nunca una por valor.
 *
 * @phpstan-type Valor array{tipo: string, valor: string|int|float|bool|null}
 * @phpstan-type Cambio array{campo: string, etiqueta: string|null, anterior: Valor|null, nuevo: Valor|null}
 */
final class PresentarHistorial
{
    /**
     * Campos que guardan el id de otro registro, y de qué modelo.
     *
     * @var array<string, class-string<Model>>
     */
    private const REFERENCIAS = [
        'cliente_id' => Cliente::class,
        'proyecto_id' => Proyecto::class,
        'modulo_id' => Modulo::class,
        'parent_id' => Modulo::class,
        'periodo_id' => Periodo::class,
        'periodo_origen_id' => Periodo::class,
        'tarea_origen_id' => Tarea::class,
        'usuario_id' => User::class,
        'creado_por' => User::class,
    ];

    /** @var array<class-string<Model>, array<int|string, string>> */
    private array $etiquetasDeReferencias = [];

    /** @var array<class-string<Model>, array<string, string>> */
    private array $castsPorClase = [];

    /**
     * @param  iterable<HistorialCambio>  $filas
     * @param  bool  $conRegistro  Añade a qué registro pertenece cada fila
     *                             (para la pantalla global; en la ficha de un
     *                             registro sobra).
     * @return array<int, array<string, mixed>>
     */
    public function presentar(iterable $filas, bool $conRegistro = false): array
    {
        $filas = collect($filas);

        $this->resolverReferencias($filas);
        $registros = $conRegistro ? $this->cargarRegistros($filas) : [];

        return $filas->map(function (HistorialCambio $fila) use ($conRegistro, $registros): array {
            $presentada = [
                'id' => $fila->id,
                'evento' => $fila->evento->value,
                'fecha' => $fila->created_at?->toDateTimeString(),
                'usuario' => $fila->usuario === null ? null : [
                    'id' => $fila->usuario->id,
                    'name' => $fila->usuario->name,
                ],
                'cambios' => $this->cambiosDe($fila),
            ];

            if ($conRegistro) {
                $registro = $registros[$fila->registro_type][$fila->registro_id] ?? null;
                $presentada['registro'] = [
                    'tipo' => $fila->registro_type,
                    'id' => $fila->registro_id,
                    'etiqueta' => $registro === null ? null : self::etiquetaDeRegistro($registro),
                    'url' => $registro === null ? null : self::urlDeRegistro($registro),
                ];
            }

            return $presentada;
        })->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cambiosDe(HistorialCambio $fila): array
    {
        $clase = in_array($fila->evento, [
            EventoHistorial::MiembroAgregado,
            EventoHistorial::MiembroActualizado,
            EventoHistorial::MiembroRetirado,
        ], true) ? ProyectoUsuario::class : HistorialCambio::claseDe($fila->registro_type);

        return array_map(fn (string $campo): array => [
            'campo' => $campo,
            'etiqueta' => Lang::has("historial.campos.{$campo}") ? "historial.campos.{$campo}" : null,
            'anterior' => $this->valor($fila->valores_anteriores, $campo, $clase),
            'nuevo' => $this->valor($fila->valores_nuevos, $campo, $clase),
        ], $fila->campos);
    }

    /**
     * @param  array<string, mixed>|null  $valores
     * @param  class-string<Model>|null  $clase
     * @return array{tipo: string, valor: string|int|float|bool|null}|null
     */
    private function valor(?array $valores, string $campo, ?string $clase): ?array
    {
        // El lado no existe (lo «anterior» de una creación).
        if ($valores === null) {
            return null;
        }

        // El campo cambió, pero su valor no se registra (la contraseña).
        if (! array_key_exists($campo, $valores)) {
            return ['tipo' => 'oculto', 'valor' => null];
        }

        $valor = $valores[$campo];

        if ($valor === null || $valor === '') {
            return ['tipo' => 'vacio', 'valor' => null];
        }

        if (isset(self::REFERENCIAS[$campo])) {
            $referida = self::REFERENCIAS[$campo];

            return ['tipo' => 'texto', 'valor' => $this->etiquetasDeReferencias[$referida][$valor] ?? '#'.$valor];
        }

        $cast = $clase === null ? null : ($this->castsDe($clase)[$campo] ?? null);

        if ($campo === 'monto_usd' || $campo === 'monto_cop') {
            return ['tipo' => $campo, 'valor' => (string) $valor];
        }

        if ($cast !== null && enum_exists($cast) && is_subclass_of($cast, BackedEnum::class)) {
            $caso = $cast::tryFrom($valor);

            return $caso !== null && method_exists($caso, 'etiqueta')
                ? ['tipo' => 'clave', 'valor' => $caso->etiqueta()]
                : ['tipo' => 'texto', 'valor' => (string) $valor];
        }

        return match (true) {
            $cast === 'boolean', $cast === 'bool' => ['tipo' => 'booleano', 'valor' => (bool) $valor],
            $cast !== null && str_contains($cast, 'datetime') => ['tipo' => 'fecha_hora', 'valor' => (string) $valor],
            $cast !== null && str_contains($cast, 'date') => ['tipo' => 'fecha', 'valor' => (string) $valor],
            default => ['tipo' => 'texto', 'valor' => is_scalar($valor) ? $valor : json_encode($valor)],
        };
    }

    /**
     * @param  class-string<Model>  $clase
     * @return array<string, string>
     */
    private function castsDe(string $clase): array
    {
        return $this->castsPorClase[$clase] ??= (new $clase)->getCasts();
    }

    /**
     * Una consulta por modelo referido, para todas las filas a la vez.
     *
     * @param  Collection<int, HistorialCambio>  $filas
     */
    private function resolverReferencias($filas): void
    {
        /** @var array<class-string<Model>, array<int, int>> $idsPorClase */
        $idsPorClase = [];

        foreach ($filas as $fila) {
            foreach ([$fila->valores_anteriores ?? [], $fila->valores_nuevos ?? []] as $valores) {
                foreach ($valores as $campo => $valor) {
                    if (isset(self::REFERENCIAS[$campo]) && is_numeric($valor)) {
                        $idsPorClase[self::REFERENCIAS[$campo]][] = (int) $valor;
                    }
                }
            }
        }

        foreach ($idsPorClase as $clase => $ids) {
            $this->etiquetasDeReferencias[$clase] = self::consultaConBorrados($clase)
                ->whereKey(array_unique($ids))
                ->get()
                ->mapWithKeys(fn (Model $modelo): array => [$modelo->getKey() => self::etiquetaCorta($modelo)])
                ->all();
        }
    }

    /**
     * @param  Collection<int, HistorialCambio>  $filas
     * @return array<string, array<int, Model>>
     */
    private function cargarRegistros($filas): array
    {
        $registros = [];

        foreach ($filas->groupBy('registro_type') as $alias => $delTipo) {
            $clase = HistorialCambio::claseDe((string) $alias);

            if ($clase === null) {
                continue;
            }

            $consulta = self::consultaConBorrados($clase);

            if ($clase === Periodo::class) {
                $consulta->with('proyecto:id,nombre');
            } elseif ($clase === Pago::class) {
                $consulta->with('periodo.proyecto:id,nombre');
            }

            $registros[$alias] = $consulta
                ->whereKey($delTipo->pluck('registro_id')->unique()->all())
                ->get()
                ->keyBy(fn (Model $modelo): int => (int) $modelo->getKey())
                ->all();
        }

        return $registros;
    }

    /**
     * @param  class-string<Model>  $clase
     * @return Builder<Model>
     */
    private static function consultaConBorrados(string $clase)
    {
        $consulta = $clase::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($clase), true)) {
            $consulta->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $consulta;
    }

    /** Cómo se nombra un registro cuando aparece como valor de otro. */
    private static function etiquetaCorta(Model $modelo): string
    {
        return match (true) {
            $modelo instanceof User => $modelo->name,
            $modelo instanceof Periodo => 'P'.$modelo->consecutivo,
            $modelo instanceof Tarea => $modelo->codigo,
            default => (string) ($modelo->getAttribute('nombre') ?? '#'.$modelo->getKey()),
        };
    }

    /** Cómo se nombra el registro dueño de una fila en la pantalla global. */
    private static function etiquetaDeRegistro(Model $registro): string
    {
        return match (true) {
            $registro instanceof Tarea => $registro->codigo.' · '.$registro->nombre,
            $registro instanceof Periodo => ($registro->proyecto->nombre ?? '').' · P'.$registro->consecutivo,
            $registro instanceof Pago => ($registro->periodo->proyecto->nombre ?? '').' · P'.$registro->periodo->consecutivo,
            $registro instanceof Reunion => $registro->titulo,
            default => self::etiquetaCorta($registro),
        };
    }

    /**
     * La ficha del registro, si sigue existiendo. Uno borrado (suave o
     * definitivo) no tiene a dónde llevar.
     */
    private static function urlDeRegistro(Model $registro): ?string
    {
        if (method_exists($registro, 'trashed') && $registro->trashed()) {
            return null;
        }

        return match (true) {
            $registro instanceof Tarea => route('tareas.show', $registro),
            $registro instanceof Proyecto => route('proyectos.show', $registro),
            $registro instanceof Cliente => route('clientes.show', $registro),
            $registro instanceof Modulo => route('proyectos.modulos.index', $registro->proyecto_id),
            $registro instanceof Periodo => route('periodos.show', $registro),
            $registro instanceof Pago => route('pagos.show', $registro),
            $registro instanceof Reunion => route('reuniones.show', $registro),
            $registro instanceof Equipo => route('equipos.edit', $registro),
            $registro instanceof User => route('usuarios.edit', $registro),
            default => null,
        };
    }
}
