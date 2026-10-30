<?php

declare(strict_types=1);

use App\Models\User;

/*
 * La raíz no tiene pantalla: manda a cada quien a donde iba a ir de todas
 * formas. El invitado llega al formulario de acceso en un solo salto, sin pasar
 * por `/dashboard` y rebotar en el middleware `auth`.
 */

test('un invitado en la raíz va directo al acceso', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

test('un usuario autenticado en la raíz va al dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});
