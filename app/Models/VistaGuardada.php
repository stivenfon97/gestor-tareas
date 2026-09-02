<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoDeVista;
use Database\Factories\VistaGuardadaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Filtros, agrupación y columnas que un usuario guarda para reutilizar.
 *
 * Como el estado de toda vista vive en la URL desde la fase 07, una vista
 * guardada es literalmente **un nombre y una cadena de consulta**. No hace
 * falta ningún mecanismo paralelo, y por eso `configuracion` guarda los mismos
 * parámetros que ya viajan en la barra de direcciones.
 *
 * `proyecto_id` nulo significa que la vista aplica a todos los proyectos.
 *
 * **Son privadas de cada usuario.** Compartirlas suena bien y no lo es: dos
 * personas con la misma vista guardada acaban discutiendo sobre qué debería
 * mostrar. El día que haga falta se añade un campo; hoy la ausencia de ese
 * campo es la decisión.
 *
 * @property int $id
 * @property int $usuario_id
 * @property int|null $proyecto_id
 * @property string $nombre
 * @property TipoDeVista $tipo
 * @property array<string, mixed> $configuracion
 * @property bool $es_predeterminada
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['usuario_id', 'proyecto_id', 'nombre', 'tipo', 'configuracion', 'es_predeterminada'])]
class VistaGuardada extends Model
{
    /** @use HasFactory<VistaGuardadaFactory> */
    use HasFactory;

    protected $table = 'vistas_guardadas';

    /*
     * Columna generada: la calcula MySQL a partir de `es_predeterminada` y es
     * lo que hace cumplir «una sola predeterminada por usuario y tipo». Si
     * entrara en un `insert` o un `update`, el motor rechazaría la escritura.
     */
    protected $guarded = ['marca_predeterminada'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoDeVista::class,
            'configuracion' => 'array',
            'es_predeterminada' => 'boolean',
        ];
    }

    /**
     * Las vistas de un usuario aplicables a una pantalla.
     *
     * Una vista global (`proyecto_id` nulo) vale en cualquier proyecto; una de
     * proyecto solo vale en el suyo. Sin este acotado, aplicar la vista
     * guardada del proyecto A dentro del proyecto B filtraría por módulos que
     * no existen ahí.
     *
     * @param  Builder<VistaGuardada>  $consulta
     */
    public function scopeDelAlcance(Builder $consulta, ?int $proyectoId): void
    {
        $consulta->where(function (Builder $interna) use ($proyectoId): void {
            $interna->whereNull('proyecto_id');

            if ($proyectoId !== null) {
                $interna->orWhere('proyecto_id', $proyectoId);
            }
        });
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
