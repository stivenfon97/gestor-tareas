<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\RolGlobal;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Crea o promueve al primer administrador sin pasar por el registro: está
 * deshabilitado a propósito (`UsuarioController`), así que en un entorno
 * recién desplegado sin datos de ejemplo no hay ninguna otra puerta de
 * entrada para el primer usuario.
 *
 * Los tres valores se piden por opción y no por argumento posicional para
 * que `desplegar.sh` (u otro script) pueda invocarlo sin depender del
 * orden, y cada uno cae a un prompt interactivo si falta —usable a mano,
 * no solo desde un script—.
 *
 * Idempotente por correo (`updateOrCreate`): volver a correrlo con el
 * mismo correo actualiza nombre, contraseña y rol en vez de chocar con el
 * `unique` de `email`.
 */
class CrearAdministrador extends Command
{
    protected $signature = 'admin:crear {--nombre=} {--correo=} {--clave=}';

    protected $description = 'Crea o promueve al primer usuario administrador (no hay registro público).';

    public function handle(): int
    {
        $nombre = $this->option('nombre') ?? $this->ask('Nombre');
        $correo = $this->option('correo') ?? $this->ask('Correo');
        $clave = $this->option('clave') ?? $this->secret('Contraseña');

        $validador = Validator::make(
            ['nombre' => $nombre, 'correo' => $correo, 'clave' => $clave],
            [
                'nombre' => ['required', 'string', 'max:255'],
                'correo' => ['required', 'string', 'email', 'max:255'],
                'clave' => ['required', 'string', Password::default()],
            ],
        );

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $usuario = User::query()->updateOrCreate(
            ['email' => $correo],
            [
                'name' => $nombre,
                'password' => Hash::make($clave),
                'activo' => true,
            ],
        );

        $usuario->syncRoles([RolGlobal::Administrador->value]);

        $this->info("Administrador listo: {$usuario->email}");

        return self::SUCCESS;
    }
}
