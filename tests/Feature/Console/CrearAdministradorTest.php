<?php

declare(strict_types=1);

use App\Enums\RolGlobal;
use App\Models\User;

it('crea un administrador nuevo con el rol asignado', function () {
    $this->artisan('admin:crear', [
        '--nombre' => 'Ana Admin',
        '--correo' => 'ana@gestor.test',
        '--clave' => 'ClaveSegura#2026',
    ])->assertSuccessful();

    $usuario = User::where('email', 'ana@gestor.test')->firstOrFail();

    expect($usuario->name)->toBe('Ana Admin')
        ->and($usuario->activo)->toBeTrue()
        ->and($usuario->rolGlobal())->toBe(RolGlobal::Administrador);
});

it('es idempotente: correr dos veces con el mismo correo actualiza en vez de duplicar', function () {
    $this->artisan('admin:crear', [
        '--nombre' => 'Ana Admin',
        '--correo' => 'ana@gestor.test',
        '--clave' => 'ClaveSegura#2026',
    ])->assertSuccessful();

    $this->artisan('admin:crear', [
        '--nombre' => 'Ana Actualizada',
        '--correo' => 'ana@gestor.test',
        '--clave' => 'OtraClaveSegura#2026',
    ])->assertSuccessful();

    expect(User::where('email', 'ana@gestor.test')->count())->toBe(1)
        ->and(User::where('email', 'ana@gestor.test')->first()->name)->toBe('Ana Actualizada');
});

it('promueve a administrador a un usuario que ya existía con otro rol', function () {
    $usuario = usuarioCon(RolGlobal::Miembro);

    $this->artisan('admin:crear', [
        '--nombre' => $usuario->name,
        '--correo' => $usuario->email,
        '--clave' => 'ClaveSegura#2026',
    ])->assertSuccessful();

    expect($usuario->fresh()->rolGlobal())->toBe(RolGlobal::Administrador);
});

it('rechaza una contraseña que no cumple la política y no crea el usuario', function () {
    $this->artisan('admin:crear', [
        '--nombre' => 'Ana Admin',
        '--correo' => 'ana@gestor.test',
        '--clave' => '123',
    ])->assertFailed();

    expect(User::where('email', 'ana@gestor.test')->exists())->toBeFalse();
});

it('rechaza un correo con formato inválido', function () {
    $this->artisan('admin:crear', [
        '--nombre' => 'Ana Admin',
        '--correo' => 'no-es-un-correo',
        '--clave' => 'ClaveSegura#2026',
    ])->assertFailed();

    expect(User::count())->toBe(0);
});
