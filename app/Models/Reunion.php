<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoReunion;
use App\Enums\Visibilidad;
use App\Services\Adjuntos;
use App\Services\ResolverRolProyecto;
use Database\Factories\ReunionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Un encuentro, llamada o intercambio de la bitácora de un proyecto.
 *
 * @property int $id
 * @property int $proyecto_id
 * @property Carbon $fecha
 * @property TipoReunion $tipo
 * @property string $titulo
 * @property array<string, mixed> $contenido_json
 * @property string $contenido_html
 * @property string|null $acuerdos
 * @property Visibilidad $visibilidad
 * @property int $creado_por
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'proyecto_id',
    'fecha',
    'tipo',
    'titulo',
    'contenido_json',
    'contenido_html',
    'acuerdos',
    'visibilidad',
    'creado_por',
])]
class Reunion extends Model implements HasMedia
{
    /** @use HasFactory<ReunionFactory> */
    use HasFactory, InteractsWithMedia;

    protected $table = 'reuniones';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'tipo' => TipoReunion::class,
            'visibilidad' => Visibilidad::class,
            'contenido_json' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('imagenes')
            ->useDisk('public')
            ->acceptsMimeTypes(Adjuntos::MIME_IMAGENES)
            ->acceptsFile(fn (File $archivo): bool => $archivo->size <= Adjuntos::LIMITE_IMAGENES);

        $this->addMediaCollection('documentos')
            ->useDisk('public')
            ->acceptsMimeTypes(Adjuntos::MIME_DOCUMENTOS)
            ->acceptsFile(fn (File $archivo): bool => $archivo->size <= Adjuntos::LIMITE_DOCUMENTOS);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('miniatura')
            ->fit(Fit::Contain, 320, 320)
            ->performOnCollections('imagenes')
            ->nonQueued();
    }

    /** @return BelongsTo<Proyecto, $this> */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /** @return HasMany<AsistenteReunion, $this> */
    public function asistentes(): HasMany
    {
        return $this->hasMany(AsistenteReunion::class, 'reunion_id');
    }

    /** Tareas que nacieron de esta reunión. @return BelongsToMany<Tarea, $this> */
    public function tareas(): BelongsToMany
    {
        return $this->belongsToMany(Tarea::class, 'reunion_tarea', 'reunion_id', 'tarea_id');
    }

    /** @param Builder<static> $query */
    public function scopeVisiblesParaCliente(Builder $query): void
    {
        $query->where('visibilidad', Visibilidad::Cliente);
    }

    /**
     * Listado acotado a lo que este usuario puede ver.
     *
     * **Obligatorio en todo listado.** La policy cubre el acceso por id; sin
     * este scope, el listado devolvería los títulos de las reuniones internas
     * aunque el detalle diera 403 — y el título ya es información filtrada.
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisiblesPara(Builder $query, User $usuario): void
    {
        if ($usuario->esAdministrador()) {
            return;
        }

        // No es "donde es cliente": un `invitado` global no ve lo interno en
        // ningún proyecto suyo, aunque su rol de proyecto sea `lider`.
        $sinAccesoInterno = ResolverRolProyecto::proyectosSinAccesoInterno($usuario);

        $query->whereIn('proyecto_id', ResolverRolProyecto::proyectosDelUsuario($usuario))
            ->where(function (Builder $consulta) use ($sinAccesoInterno): void {
                $consulta->whereNotIn('proyecto_id', $sinAccesoInterno)
                    ->orWhere('visibilidad', Visibilidad::Cliente);
            });
    }
}
