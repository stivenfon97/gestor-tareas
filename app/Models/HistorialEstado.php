<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoTarea;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Un cambio de estado de una tarea. Lo escribe `TareaObserver`, jamás un
 * controlador.
 *
 * No tiene `updated_at`: un registro histórico no se corrige, se añade otro.
 *
 * @property int $id
 * @property int $tarea_id
 * @property int|null $usuario_id
 * @property EstadoTarea|null $estado_anterior
 * @property EstadoTarea $estado_nuevo
 * @property string|null $comentario
 * @property Carbon|null $created_at
 */
#[Fillable(['tarea_id', 'usuario_id', 'estado_anterior', 'estado_nuevo', 'comentario'])]
class HistorialEstado extends Model
{
    protected $table = 'historial_estados';

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado_anterior' => EstadoTarea::class,
            'estado_nuevo' => EstadoTarea::class,
        ];
    }

    /** @return BelongsTo<Tarea, $this> */
    public function tarea(): BelongsTo
    {
        return $this->belongsTo(Tarea::class);
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
