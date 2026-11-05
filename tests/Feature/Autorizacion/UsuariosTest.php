<?php

declare(strict_types=1);

use App\Enums\RolGlobal;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->administrador = usuarioCon(RolGlobal::Administrador);
});

it('un usuario desactivado no puede iniciar sesión', function () {
    $desactivado = User::factory()->create([
        'email' => 'fuera@gestor.test',
        'password' => Hash::make('contrasena-valida'),
        'activo' => false,
    ]);

    $this->post(route('login'), [
        'email' => $desactivado->email,
        'password' => 'contrasena-valida',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('el mismo usuario entra en cuanto se le reactiva', function () {
    $usuario = User::factory()->create([
        'email' => 'dentro@gestor.test',
        'password' => Hash::make('contrasena-valida'),
        'activo' => false,
    ]);

    $usuario->update(['activo' => true]);

    $this->post(route('login'), [
        'email' => $usuario->email,
        'password' => 'contrasena-valida',
    ]);

    $this->assertAuthenticatedAs($usuario);
});

it('desactivar a alguien con la sesión abierta lo echa del sistema', function () {
    $usuario = usuarioCon(RolGlobal::Miembro);

    $this->actingAs($usuario)->get(route('dashboard'))->assertOk();

    $usuario->update(['activo' => false]);

    $this->actingAs($usuario)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('un miembro no puede acceder a la gestión de usuarios', function () {
    $miembro = usuarioCon(RolGlobal::Miembro);

    $this->actingAs($miembro)->get(route('usuarios.index'))->assertForbidden();
    $this->actingAs($miembro)->get(route('usuarios.create'))->assertForbidden();
    $this->actingAs($miembro)
        ->post(route('usuarios.store'), [
            'name' => 'Alguien',
            'email' => 'alguien@gestor.test',
            'password' => 'contrasena-valida',
            'password_confirmation' => 'contrasena-valida',
            'rol_global' => RolGlobal::Miembro->value,
        ])
        ->assertForbidden();
});

it('un invitado tampoco puede acceder a la gestión de usuarios', function () {
    $invitado = usuarioCon(RolGlobal::Invitado);

    $this->actingAs($invitado)->get(route('usuarios.index'))->assertForbidden();
});

it('el administrador crea usuarios con su rol global', function () {
    $this->actingAs($this->administrador)
        ->post(route('usuarios.store'), [
            'name' => 'Nueva Persona',
            'email' => 'nueva@gestor.test',
            'password' => 'contrasena-valida',
            'password_confirmation' => 'contrasena-valida',
            'rol_global' => RolGlobal::Invitado->value,
        ])
        ->assertRedirect(route('usuarios.index'));

    $creada = User::query()->where('email', 'nueva@gestor.test')->sole();

    expect($creada->rolGlobal())->toBe(RolGlobal::Invitado)
        ->and($creada->activo)->toBeTrue();
});

it('cambiar el rol global no deja dos roles pegados al usuario', function () {
    $usuario = usuarioCon(RolGlobal::Invitado);

    $this->actingAs($this->administrador)
        ->put(route('usuarios.update', $usuario), [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'rol_global' => RolGlobal::Miembro->value,
        ])
        ->assertRedirect(route('usuarios.index'));

    $usuario = $usuario->fresh();

    expect($usuario->getRoleNames()->all())->toBe([RolGlobal::Miembro->value]);
});

it('desactiva en lugar de borrar', function () {
    $usuario = usuarioCon(RolGlobal::Miembro);

    $this->actingAs($this->administrador)
        ->put(route('usuarios.estado.update', $usuario))
        ->assertRedirect();

    expect($usuario->fresh()->activo)->toBeFalse()
        ->and(User::query()->whereKey($usuario->getKey())->exists())->toBeTrue();
});

it('no deja al administrador desactivarse a sí mismo', function () {
    $this->actingAs($this->administrador)
        ->put(route('usuarios.estado.update', $this->administrador))
        ->assertForbidden();

    expect($this->administrador->fresh()->activo)->toBeTrue();
});

it('no hay registro público', function () {
    $this->get('/register')->assertNotFound();
});

/* -------------------------------------------- listado servido a TablaDatos */

/** @return array<int, string> */
function correosDelListadoDeUsuarios($respuesta): array
{
    return array_column($respuesta->viewData('page')['props']['usuarios']['data'], 'email');
}

it('el listado de usuarios deja llegar a quien queda en la página siguiente', function () {
    User::factory()->count(15)->create();

    $paginador = $this->actingAs($this->administrador)
        ->get(route('usuarios.index', ['por_pagina' => 15, 'page' => 2]))
        ->assertOk()
        ->viewData('page')['props']['usuarios'];

    expect($paginador['per_page'])->toBe(15)
        ->and($paginador['last_page'])->toBe(2)
        ->and($paginador['total'])->toBe(16)
        ->and($paginador['data'])->toHaveCount(1);
});

it('el listado de usuarios filtra por lo que declara cada columna', function (array $filtro, string $esperado) {
    User::factory()->create(['name' => 'Ana Ruiz', 'email' => 'ana@gestor.test', 'activo' => true]);
    User::factory()->create(['name' => 'Beto Paz', 'email' => 'beto@otra.test', 'activo' => false]);
    $this->administrador->update(['name' => 'Zoe', 'email' => 'zoe@admin.test']);

    $respuesta = $this->actingAs($this->administrador)
        ->get(route('usuarios.index', ['filter' => $filtro]))
        ->assertOk();

    expect(correosDelListadoDeUsuarios($respuesta))->toBe([$esperado])
        ->and($respuesta->viewData('page')['props']['filtros'])->toBe($filtro);
})->with([
    'nombre' => [['name' => 'Rui'], 'ana@gestor.test'],
    'correo' => [['email' => 'otra.test'], 'beto@otra.test'],
    'inactivos' => [['activo' => '0'], 'beto@otra.test'],
]);

it('el listado de usuarios rechaza filtrar por una columna no declarada', function () {
    $this->actingAs($this->administrador)
        ->get(route('usuarios.index', ['filter' => ['password' => 'x']]))
        ->assertStatus(400);
});

it('el listado de usuarios ordena y devuelve el estado de la URL a la tabla', function () {
    User::factory()->create(['email' => 'a@gestor.test']);
    User::factory()->create(['email' => 'z@gestor.test']);
    $this->administrador->update(['email' => 'm@gestor.test']);

    $respuesta = $this->actingAs($this->administrador)
        ->get(route('usuarios.index', ['sort' => '-email', 'columnas' => 'nombre,correo']))
        ->assertOk();

    $props = $respuesta->viewData('page')['props'];

    expect(correosDelListadoDeUsuarios($respuesta))->toBe(['z@gestor.test', 'm@gestor.test', 'a@gestor.test'])
        ->and($props['orden'])->toBe([['campo' => 'email', 'direccion' => 'desc']])
        ->and($props['columnasVisibles'])->toBe(['nombre', 'correo']);
});

it('la búsqueda del listado de usuarios encuentra por nombre o por correo', function (string $termino) {
    User::factory()->create(['name' => 'Ana Ruiz', 'email' => 'ana@gestor.test']);
    User::factory()->create(['name' => 'Beto Paz', 'email' => 'beto@otra.test']);
    $this->administrador->update(['name' => 'Zoe', 'email' => 'zoe@admin.test']);

    $respuesta = $this->actingAs($this->administrador)
        ->get(route('usuarios.index', ['filter' => ['buscar' => $termino]]))
        ->assertOk();

    expect(correosDelListadoDeUsuarios($respuesta))->toBe(['ana@gestor.test']);
})->with(['nombre' => 'ana ru', 'correo' => 'ana@']);

it('la búsqueda trata el comodín de LIKE como un carácter literal', function () {
    User::factory()->create(['name' => 'Ana Ruiz', 'email' => 'ana_ruiz@gestor.test']);
    User::factory()->create(['name' => 'Beto Paz', 'email' => 'betopaz@gestor.test']);
    $this->administrador->update(['email' => 'zoe@admin.test']);

    $respuesta = $this->actingAs($this->administrador)
        ->get(route('usuarios.index', ['filter' => ['buscar' => '_']]))
        ->assertOk();

    expect(correosDelListadoDeUsuarios($respuesta))->toBe(['ana_ruiz@gestor.test']);
});
