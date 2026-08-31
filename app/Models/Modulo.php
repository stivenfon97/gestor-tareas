<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ModuloFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * División funcional de un proyecto. Autoreferencial: puede contener submódulos.
 *
 * @property int $id
 * @property int $proyecto_id
 * @property int|null $parent_id
 * @property string $nombre
 * @property string|null $descripcion
 * @property int $orden
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['proyecto_id', 'parent_id', 'nombre', 'descripcion', 'orden'])]
class Modulo extends Model
{
    /** @use HasFactory<ModuloFactory> */
    use HasFactory;

    protected $table = 'modulos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
        ];
    }

    /** @return BelongsTo<Proyecto, $this> */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    /** @return BelongsTo<Modulo, $this> */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'parent_id');
    }

    /** @return HasMany<Modulo, $this> */
    public function hijos(): HasMany
    {
        return $this->hasMany(Modulo::class, 'parent_id')->orderBy('orden');
    }

    /** @return HasMany<Tarea, $this> */
    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class);
    }

    /** Módulos de primer nivel. @param Builder<static> $query */
    public function scopeRaiz(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /**
     * Ruta jerárquica legible: "Clientes › Facturación".
     *
     * Recorre hacia arriba por `padre`. En un árbol de dos niveles el coste es
     * trivial; si el árbol crece, precarga la relación antes de llamarlo.
     */
    public function rutaCompleta(string $separador = ' › '): string
    {
        $segmentos = [$this->nombre];
        $actual = $this->padre;

        while ($actual !== null) {
            array_unshift($segmentos, $actual->nombre);
            $actual = $actual->padre;
        }

        return implode($separador, $segmentos);
    }
}
