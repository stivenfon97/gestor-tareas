<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Enums\TipoTarea;
use App\Observers\TareaObserver;
use App\Services\ResolverRolProyecto;
use Database\Factories\TareaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Unidad de trabajo. Pertenece a un proyecto, un módulo y un periodo.
 *
 * Estado, tipo y prioridad son tres ejes independientes: "corrección" y
 * "soporte" son tipos, no estados.
 *
 * @property int $id
 * @property int $proyecto_id
 * @property int|null $modulo_id
 * @property int|null $periodo_id
 * @property int|null $periodo_origen_id
 * @property int|null $tarea_origen_id
 * @property string $codigo
 * @property string $nombre
 * @property string|null $descripcion
 * @property EstadoTarea $estado
 * @property TipoTarea $tipo
 * @property PrioridadTarea $prioridad
 * @property string|null $motivo_espera
 * @property string|null $motivo_cancelacion
 * @property Carbon|null $fecha_inicio
 * @property Carbon|null $fecha_fin
 * @property int $orden
 * @property int $creado_por
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'proyecto_id',
    'modulo_id',
    'periodo_id',
    'periodo_origen_id',
    'tarea_origen_id',
    'nombre',
    'descripcion',
    'estado',
    'tipo',
    'prioridad',
    'motivo_espera',
    'motivo_cancelacion',
    'fecha_inicio',
    'fecha_fin',
    'orden',
    'creado_por',
])]
#[ObservedBy([TareaObserver::class])]
class Tarea extends Model implements HasMedia
{
    /** @use HasFactory<TareaFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $table = 'tareas';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoTarea::class,
            'tipo' => TipoTarea::class,
            'prioridad' => PrioridadTarea::class,
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'orden' => 'integer',
        ];
    }

    /**
     * Solo `imagenes` lleva conversiones. Un documento de 20 MB no tiene
     * miniatura que generar y encolar la conversión solo quema tiempo de worker.
     */
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

    /** @return BelongsTo<Proyecto, $this> */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    /** @return BelongsTo<Modulo, $this> */
    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class);
    }

    /** @return BelongsTo<Periodo, $this> */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    /** Periodo en el que nació, si se arrastró. @return BelongsTo<Periodo, $this> */
    public function periodoOrigen(): BelongsTo
    {
        return $this->belongsTo(Periodo::class, 'periodo_origen_id');
    }

    /**
     * La entrega que originó esta corrección, si la hay.
     *
     * Existe porque `entregada` es terminal (D2.6): un fallo posterior crea una
     * tarea nueva de tipo `correccion` en lugar de reabrir la anterior. Sin esta
     * columna, "cuántas correcciones generó esta entrega" solo se respondía
     * leyendo descripciones.
     *
     * @return BelongsTo<Tarea, $this>
     */
    public function origen(): BelongsTo
    {
        return $this->belongsTo(Tarea::class, 'tarea_origen_id');
    }

    /** Correcciones y soportes nacidos de esta tarea. @return HasMany<Tarea, $this> */
    public function correcciones(): HasMany
    {
        return $this->hasMany(Tarea::class, 'tarea_origen_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /** @return HasMany<NotaTarea, $this> */
    public function notas(): HasMany
    {
        return $this->hasMany(NotaTarea::class);
    }

    /** @return HasMany<HistorialEstado, $this> */
    public function historial(): HasMany
    {
        return $this->hasMany(HistorialEstado::class)->orderBy('created_at');
    }

    /** @return BelongsToMany<Reunion, $this> */
    public function reuniones(): BelongsToMany
    {
        return $this->belongsToMany(Reunion::class, 'reunion_tarea', 'tarea_id', 'reunion_id');
    }

    /** @param Builder<static> $query */
    public function scopeDelProyecto(Builder $query, Proyecto|int $proyecto): void
    {
        $query->where('proyecto_id', $proyecto instanceof Proyecto ? $proyecto->id : $proyecto);
    }

    /** @param Builder<static> $query */
    public function scopePorEstado(Builder $query, EstadoTarea $estado): void
    {
        $query->where('estado', $estado);
    }

    /** Tareas que no han llegado a un estado terminal. @param Builder<static> $query */
    public function scopeActivas(Builder $query): void
    {
        $query->whereNotIn('estado', [EstadoTarea::Entregada, EstadoTarea::Cancelada]);
    }

    /** @param Builder<static> $query */
    public function scopeArrastradas(Builder $query): void
    {
        $query->whereNotNull('periodo_origen_id');
    }

    /**
     * Listado acotado a los proyectos donde el usuario tiene fila en el pivote.
     *
     * **Obligatorio en todo listado.** Sin él, un filtro mal puesto devuelve
     * tareas de proyectos ajenos y la policy no se entera: la policy solo mira
     * el acceso por id.
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisiblesPara(Builder $query, User $usuario): void
    {
        if ($usuario->esAdministrador()) {
            return;
        }

        $query->whereIn('proyecto_id', ResolverRolProyecto::proyectosDelUsuario($usuario));
    }

    public function estaArrastrada(): bool
    {
        return $this->periodo_origen_id !== null;
    }

    public function puedeTransicionarA(EstadoTarea $destino): bool
    {
        return $this->estado->puedeTransicionarA($destino);
    }
}
