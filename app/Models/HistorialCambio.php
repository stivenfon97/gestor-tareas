<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventoHistorial;
use App\Exceptions\HistorialInmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Un cambio en un registro auditado: qué campos cambiaron, cuánto valían antes
 * y después, quién lo hizo y cuándo. Lo escribe `RegistrarHistorial`, llamado
 * desde observers y desde las pocas escrituras masivas que no los disparan;
 * jamás un controlador.
 *
 * Es inmutable: no tiene `updated_at` y guardar una fila existente o borrarla
 * lanza `HistorialInmutable`.
 *
 * @property int $id
 * @property string $registro_type
 * @property int $registro_id
 * @property int|null $usuario_id
 * @property EventoHistorial $evento
 * @property array<int, string> $campos
 * @property array<string, mixed>|null $valores_anteriores
 * @property array<string, mixed>|null $valores_nuevos
 * @property string|null $ip
 * @property Carbon|null $created_at
 */
#[Fillable([
    'registro_type',
    'registro_id',
    'usuario_id',
    'evento',
    'campos',
    'valores_anteriores',
    'valores_nuevos',
    'ip',
])]
class HistorialCambio extends Model
{
    protected $table = 'historial_cambios';

    public const UPDATED_AT = null;

    /**
     * Alias en español de cada modelo auditado. Es lo que se guarda en
     * `registro_type`: renombrar una clase no rompe el historial, y la tabla
     * se lee sin conocer el espacio de nombres de PHP.
     *
     * Deliberadamente **no** es `Relation::morphMap()`: ese mapa es global y
     * cambiaría también el `model_type` de medialibrary y de
     * spatie/permission, cuyas filas existentes guardan `App\Models\…`.
     *
     * @var array<string, class-string<Model>>
     */
    public const TIPOS = [
        'cliente' => Cliente::class,
        'proyecto' => Proyecto::class,
        'modulo' => Modulo::class,
        'tarea' => Tarea::class,
        'periodo' => Periodo::class,
        'pago' => Pago::class,
        'reunion' => Reunion::class,
        'equipo' => Equipo::class,
        'usuario' => User::class,
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw HistorialInmutable::alModificar());
        static::deleting(fn () => throw HistorialInmutable::alModificar());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'evento' => EventoHistorial::class,
            'campos' => 'array',
            'valores_anteriores' => 'array',
            'valores_nuevos' => 'array',
            'registro_id' => 'integer',
        ];
    }

    /**
     * @param  class-string<Model>  $clase
     */
    public static function aliasDe(string $clase): string
    {
        $alias = array_search($clase, self::TIPOS, true);

        if ($alias === false) {
            throw new \LogicException("{$clase} no está en HistorialCambio::TIPOS.");
        }

        return $alias;
    }

    /**
     * @return class-string<Model>|null
     */
    public static function claseDe(string $alias): ?string
    {
        return self::TIPOS[$alias] ?? null;
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Las filas de un registro concreto.
     *
     * @param  Builder<HistorialCambio>  $consulta
     */
    public function scopeDe(Builder $consulta, Model $registro): void
    {
        $consulta->where('registro_type', self::aliasDe($registro::class))
            ->where('registro_id', $registro->getKey());
    }
}
