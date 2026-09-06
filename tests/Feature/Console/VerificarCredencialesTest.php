<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('acepta un correo y contraseña correctos', function () {
    User::factory()->create([
        'email' => 'ana@gestor.test',
        'password' => Hash::make('ClaveCorrecta#1'),
        'activo' => true,
    ]);

    $this->artisan('admin:verificar', [
        '--correo' => 'ana@gestor.test',
        '--clave' => 'ClaveCorrecta#1',
    ])->assertSuccessful();
});

it('rechaza una contraseña incorrecta', function () {
    User::factory()->create([
        'email' => 'ana@gestor.test',
        'password' => Hash::make('ClaveCorrecta#1'),
        'activo' => true,
    ]);

    $this->artisan('admin:verificar', [
        '--correo' => 'ana@gestor.test',
        '--clave' => 'clave-equivocada',
    ])->assertFailed();
});

it('rechaza una contraseña con comillas sin romper el comando', function () {
    User::factory()->create([
        'email' => 'ana@gestor.test',
        'password' => Hash::make("Clave'Con\"Comillas#1"),
        'activo' => true,
    ]);

    $this->artisan('admin:verificar', [
        '--correo' => 'ana@gestor.test',
        '--clave' => "Clave'Con\"Comillas#1",
    ])->assertSuccessful();
});
