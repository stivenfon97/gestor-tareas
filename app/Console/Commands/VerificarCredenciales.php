<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

/**
 * Comprueba que un correo y una contraseña autentican de verdad, sin dejar
 * sesión abierta (`Auth::once()`). Pensado para `desplegar.sh`: pasar la
 * contraseña por opción (argv) evita interpolarla en una cadena de PHP —lo
 * que sí hacía un `tinker --execute="..."` anterior—, donde una comilla
 * tipeada por quien crea el usuario rompería el comando en vez de solo
 * fallar el login.
 */
class VerificarCredenciales extends Command
{
    protected $signature = 'admin:verificar {--correo=} {--clave=}';

    protected $description = 'Comprueba que un correo y una contraseña autentican correctamente.';

    public function handle(): int
    {
        $autentica = Auth::once([
            'email' => (string) $this->option('correo'),
            'password' => (string) $this->option('clave'),
        ]);

        return $autentica ? self::SUCCESS : self::FAILURE;
    }
}
