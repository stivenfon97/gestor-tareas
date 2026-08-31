<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RolGlobal;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property bool $activo
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'activo'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    /**
     * El rol global es único: `spatie/laravel-permission` permite varios, pero
     * este dominio asigna exactamente uno y lo trata como un techo.
     */
    public function rolGlobal(): ?RolGlobal
    {
        $nombre = $this->getRoleNames()->first();

        return $nombre === null ? null : RolGlobal::tryFrom($nombre);
    }

    public function esAdministrador(): bool
    {
        return $this->rolGlobal() === RolGlobal::Administrador;
    }

    /**
     * Proyectos en los que participa, con su rol en el pivote.
     *
     * El rol efectivo se lee siempre de aquí, nunca del equipo: el equipo es
     * solo un atajo de asignación masiva.
     *
     * @return BelongsToMany<Proyecto, $this, ProyectoUsuario>
     */
    public function proyectos(): BelongsToMany
    {
        return $this->belongsToMany(Proyecto::class, 'proyecto_usuario', 'usuario_id', 'proyecto_id')
            ->using(ProyectoUsuario::class)
            ->withPivot('rol')
            ->withTimestamps();
    }

    /** @return BelongsToMany<Equipo, $this> */
    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class, 'equipo_usuario', 'usuario_id', 'equipo_id');
    }

    /** @return HasMany<VistaGuardada, $this> */
    public function vistasGuardadas(): HasMany
    {
        return $this->hasMany(VistaGuardada::class, 'usuario_id');
    }
}
