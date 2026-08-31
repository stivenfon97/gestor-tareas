<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EquipoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Agrupación de usuarios. Es un atajo de asignación masiva: el rol efectivo
 * sobre un proyecto vive siempre en el pivote `proyecto_usuario`.
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nombre', 'descripcion'])]
class Equipo extends Model
{
    /** @use HasFactory<EquipoFactory> */
    use HasFactory;

    protected $table = 'equipos';

    /** @return BelongsToMany<User, $this> */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'equipo_usuario', 'equipo_id', 'usuario_id');
    }
}
