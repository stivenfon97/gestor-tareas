<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RolProyecto;
use App\Observers\MiembroDeProyectoObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * Pivote de `proyecto_usuario`. Existe para castear el rol al enum: leerlo como
 * string desde `->pivot->rol` es la forma de que una policy compare mal.
 *
 * @property int $proyecto_id
 * @property int $usuario_id
 * @property RolProyecto $rol
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[ObservedBy([MiembroDeProyectoObserver::class])]
class ProyectoUsuario extends Pivot
{
    protected $table = 'proyecto_usuario';

    public $incrementing = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rol' => RolProyecto::class,
        ];
    }
}
