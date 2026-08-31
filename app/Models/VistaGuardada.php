<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Filtros, agrupación y columnas que un usuario guarda para reutilizar.
 *
 * `proyecto_id` nulo significa que la vista aplica a todos los proyectos.
 *
 * @property int $id
 * @property int $usuario_id
 * @property int|null $proyecto_id
 * @property string $nombre
 * @property string $tipo
 * @property array<string, mixed> $configuracion
 * @property bool $es_predeterminada
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['usuario_id', 'proyecto_id', 'nombre', 'tipo', 'configuracion', 'es_predeterminada'])]
class VistaGuardada extends Model
{
    protected $table = 'vistas_guardadas';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'configuracion' => 'array',
            'es_predeterminada' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** @return BelongsTo<Proyecto, $this> */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}
