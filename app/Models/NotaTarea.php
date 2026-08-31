<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Visibilidad;
use App\Services\ResolverRolProyecto;
use Database\Factories\NotaTareaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Comentario sobre una tarea. Nace `interna`.
 *
 * @property int $id
 * @property int $tarea_id
 * @property int $usuario_id
 * @property string $contenido
 * @property Visibilidad $visibilidad
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tarea_id', 'usuario_id', 'contenido', 'visibilidad'])]
class NotaTarea extends Model implements HasMedia
{
    /** @use HasFactory<NotaTareaFactory> */
    use HasFactory, InteractsWithMedia;

    protected $table = 'notas_tarea';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibilidad' => Visibilidad::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('imagenes')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

        $this->addMediaCollection('documentos')
            ->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('miniatura')
            ->fit(Fit::Contain, 320, 320)
            ->performOnCollections('imagenes')
            ->nonQueued();
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

    /**
     * La exclusión se aplica en la consulta, no ocultando columnas en la
     * interfaz.
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisiblesParaCliente(Builder $query): void
    {
        $query->where('visibilidad', Visibilidad::Cliente);
    }

    /**
     * Listado acotado a lo que este usuario puede ver. **Obligatorio en todo
     * listado**, por la misma razón que en `Reunion`: la policy no filtra
     * listados.
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisiblesPara(Builder $query, User $usuario): void
    {
        if ($usuario->esAdministrador()) {
            return;
        }

        $suyos = ResolverRolProyecto::proyectosDelUsuario($usuario);
        // Mismo criterio que en `Reunion`: el rol global `invitado` recorta
        // todos sus proyectos, no solo aquellos donde su rol es `cliente`.
        $sinAccesoInterno = ResolverRolProyecto::proyectosSinAccesoInterno($usuario);

        $query->whereHas('tarea', function (Builder $consulta) use ($suyos): void {
            $consulta->whereIn('proyecto_id', $suyos);
        })->where(function (Builder $consulta) use ($sinAccesoInterno): void {
            $consulta->whereDoesntHave('tarea', function (Builder $interna) use ($sinAccesoInterno): void {
                $interna->whereIn('proyecto_id', $sinAccesoInterno);
            })->orWhere('visibilidad', Visibilidad::Cliente);
        });
    }
}
