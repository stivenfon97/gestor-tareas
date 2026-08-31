<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Quién estuvo en una reunión.
 *
 * `nombre` es obligatorio incluso cuando hay `usuario_id`: la gente del cliente
 * no es usuaria del sistema, y el nombre debe sobrevivir al borrado del usuario.
 *
 * @property int $id
 * @property int $reunion_id
 * @property int|null $usuario_id
 * @property string $nombre
 * @property string|null $organizacion
 */
#[Fillable(['reunion_id', 'usuario_id', 'nombre', 'organizacion'])]
class AsistenteReunion extends Model
{
    protected $table = 'asistentes_reunion';

    public $timestamps = false;

    /** @return BelongsTo<Reunion, $this> */
    public function reunion(): BelongsTo
    {
        return $this->belongsTo(Reunion::class, 'reunion_id');
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
