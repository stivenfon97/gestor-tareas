<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoPeriodo;
use Database\Factories\PeriodoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Bloque de trabajo facturable de un proyecto. Se cierra a mano.
 *
 * @property int $id
 * @property int $proyecto_id
 * @property int $consecutivo
 * @property Carbon $fecha_inicio
 * @property Carbon|null $fecha_fin
 * @property EstadoPeriodo $estado
 * @property string|null $notas
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['proyecto_id', 'consecutivo', 'fecha_inicio', 'fecha_fin', 'estado', 'notas'])]
class Periodo extends Model
{
    /** @use HasFactory<PeriodoFactory> */
    use HasFactory;

    protected $table = 'periodos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'estado' => EstadoPeriodo::class,
            'consecutivo' => 'integer',
        ];
    }

    /** @return BelongsTo<Proyecto, $this> */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    /**
     * Un periodo tiene exactamente un pago. El `unique` en `pagos.periodo_id`
     * es lo que lo hace cierto en la base de datos.
     *
     * @return HasOne<Pago, $this>
     */
    public function pago(): HasOne
    {
        return $this->hasOne(Pago::class);
    }

    /** @return HasMany<Tarea, $this> */
    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class);
    }

    /** Tareas que este periodo cedió al siguiente. @return HasMany<Tarea, $this> */
    public function tareasArrastradas(): HasMany
    {
        return $this->hasMany(Tarea::class, 'periodo_origen_id');
    }

    /** @return HasMany<Reporte, $this> */
    public function reportes(): HasMany
    {
        return $this->hasMany(Reporte::class);
    }

    /**
     * Fecha de fin superada y el periodo sigue abierto.
     *
     * No es un error: es un estado válido y frecuente que la interfaz señala y
     * la validación no rechaza.
     */
    public function estaVencido(): bool
    {
        return $this->estado === EstadoPeriodo::Abierto
            && $this->fecha_fin !== null
            && $this->fecha_fin->isPast();
    }

    /**
     * Días que abarca el periodo, ambos extremos incluidos. Null mientras no
     * tenga fecha de fin.
     */
    public function duracionEnDias(): ?int
    {
        if ($this->fecha_fin === null) {
            return null;
        }

        return (int) $this->fecha_inicio->diffInDays($this->fecha_fin) + 1;
    }

    /** @param Builder<static> $query */
    public function scopeAbiertos(Builder $query): void
    {
        $query->where('estado', EstadoPeriodo::Abierto);
    }

    /** @param Builder<static> $query */
    public function scopeCerrados(Builder $query): void
    {
        $query->where('estado', EstadoPeriodo::Cerrado);
    }
}
