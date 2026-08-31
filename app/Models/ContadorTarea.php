<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Último número de tarea entregado para un proyecto.
 *
 * Lo administra `TareaObserver`, que bloquea la fila con `lockForUpdate()`.
 * Nadie más debería escribir aquí.
 *
 * @property int $id
 * @property int $proyecto_id
 * @property int $ultimo_numero
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['proyecto_id', 'ultimo_numero'])]
class ContadorTarea extends Model
{
    protected $table = 'contadores_tarea';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ultimo_numero' => 'integer',
        ];
    }

    /** @return BelongsTo<Proyecto, $this> */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}
