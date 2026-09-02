<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoPeriodo;
use App\Enums\EstadoProyecto;
use App\Observers\ProyectoObserver;
use App\Services\Adjuntos;
use Database\Factories\ProyectoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\File;

/**
 * Una aplicación o sistema. Tiene módulos, tareas y periodos propios.
 *
 * @property int $id
 * @property int $cliente_id
 * @property string $nombre
 * @property string $slug
 * @property string $prefijo_tarea
 * @property string|null $descripcion
 * @property EstadoProyecto $estado
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['cliente_id', 'nombre', 'slug', 'prefijo_tarea', 'descripcion', 'estado'])]
#[ObservedBy([ProyectoObserver::class])]
class Proyecto extends Model implements HasMedia
{
    /** @use HasFactory<ProyectoFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $table = 'proyectos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoProyecto::class,
        ];
    }

    /**
     * El prefijo se guarda siempre en mayúsculas y sin espacios.
     *
     * Se normaliza aquí y no en el formulario porque alimenta el código
     * correlativo de tarea (`MAQ-0001`): si entra `maq ` por un seeder o por
     * `tinker`, los códigos de ese proyecto salen distintos de los de todos los
     * demás y ya no hay forma de repararlos sin reescribir historial.
     *
     * @return Attribute<string, string>
     */
    protected function prefijoTarea(): Attribute
    {
        return Attribute::make(
            set: static fn (string $valor): string => self::normalizarPrefijo($valor),
        );
    }

    /**
     * Mayúsculas y sin espacios. Público porque el formulario normaliza antes
     * de validar el `unique`: sin eso, `maq` y `MAQ` pasarían la validación
     * como distintos y chocarían al guardar.
     */
    public static function normalizarPrefijo(string $valor): string
    {
        return mb_strtoupper((string) preg_replace('/\s+/u', '', $valor));
    }

    /**
     * Slug libre a partir del nombre, con sufijo numérico si ya está tomado.
     *
     * La colisión es frecuente de verdad: dos clientes distintos piden un
     * "Portal de Clientes" y el segundo no debe fallar con un error de base de
     * datos.
     */
    public static function slugUnico(string $desde, ?int $ignorarId = null): string
    {
        $base = Str::slug($desde) ?: 'proyecto';
        $candidato = $base;
        $sufijo = 1;

        while (self::withTrashed()
            ->where('slug', $candidato)
            ->when($ignorarId !== null, fn (Builder $consulta) => $consulta->whereKeyNot($ignorarId))
            ->exists()
        ) {
            $candidato = $base.'-'.(++$sufijo);
        }

        return $candidato;
    }

    /**
     * Si el proyecto ya emitió algún código de tarea.
     *
     * Mira el contador y no solo las tareas vivas: borrar la tarea no borra el
     * código que ya viajó al cliente dentro de un reporte.
     */
    public function tieneCodigosEmitidos(): bool
    {
        return ($this->contador()->value('ultimo_numero') ?? 0) > 0
            || $this->tareas()->exists();
    }

    /**
     * Los respaldos `.sql` van a disco privado, jamás al público, y no llevan
     * conversiones: un dump de 250 MB no pasa por procesamiento de imagen.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('respaldos_bd')
            ->useDisk('local')
            ->acceptsMimeTypes(Adjuntos::MIME_RESPALDOS)
            ->acceptsFile(fn (File $archivo): bool => $archivo->size <= Adjuntos::LIMITE_RESPALDOS);
    }

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return HasMany<Modulo, $this> */
    public function modulos(): HasMany
    {
        return $this->hasMany(Modulo::class);
    }

    /** @return HasMany<Tarea, $this> */
    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class);
    }

    /** @return HasMany<Periodo, $this> */
    public function periodos(): HasMany
    {
        return $this->hasMany(Periodo::class);
    }

    /** @return HasMany<Reunion, $this> */
    public function reuniones(): HasMany
    {
        return $this->hasMany(Reunion::class);
    }

    /** @return HasMany<Reporte, $this> */
    public function reportes(): HasMany
    {
        return $this->hasMany(Reporte::class);
    }

    /** @return HasOne<ContadorTarea, $this> */
    public function contador(): HasOne
    {
        return $this->hasOne(ContadorTarea::class);
    }

    /**
     * El rol se resuelve siempre desde este pivote, nunca desde el equipo.
     *
     * @return BelongsToMany<User, $this, ProyectoUsuario>
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'proyecto_usuario', 'proyecto_id', 'usuario_id')
            ->using(ProyectoUsuario::class)
            ->withPivot('rol')
            ->withTimestamps();
    }

    /**
     * El periodo abierto del proyecto, si lo hay. Solo puede haber uno a la vez.
     *
     * @return HasOne<Periodo, $this>
     */
    public function periodoAbierto(): HasOne
    {
        return $this->hasOne(Periodo::class)->where('estado', EstadoPeriodo::Abierto);
    }

    /** @param Builder<static> $query */
    public function scopeActivos(Builder $query): void
    {
        $query->where('estado', EstadoProyecto::Activo);
    }
}
