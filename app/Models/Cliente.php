<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Empresa o persona que contrata. Tiene uno o más proyectos.
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $contacto_nombre
 * @property string|null $contacto_email
 * @property string|null $contacto_telefono
 * @property string|null $notas
 * @property bool $activo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nombre', 'contacto_nombre', 'contacto_email', 'contacto_telefono', 'notas', 'activo'])]
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory;

    protected $table = 'clientes';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /** @return HasMany<Proyecto, $this> */
    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class);
    }

    /** @param Builder<static> $query */
    public function scopeActivos(Builder $query): void
    {
        $query->where('activo', true);
    }
}
