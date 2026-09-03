<?php

declare(strict_types=1);

use App\Models\User;

test('sin cookie de idioma, el locale es el de config/app.php', function () {
    $usuario = User::factory()->create();

    $respuesta = $this->actingAs($usuario)->get(route('dashboard'));

    expect($respuesta->viewData('page')['props']['locale'])->toBe(config('app.locale'));
});

test('con la cookie idioma en un valor válido, el locale cambia', function () {
    $usuario = User::factory()->create();

    $respuesta = $this->actingAs($usuario)
        ->withUnencryptedCookie('idioma', 'en')
        ->get(route('dashboard'));

    expect($respuesta->viewData('page')['props']['locale'])->toBe('en');
});

test('una cookie de idioma con un valor que no existe se ignora', function () {
    $usuario = User::factory()->create();

    $respuesta = $this->actingAs($usuario)
        ->withUnencryptedCookie('idioma', 'fr')
        ->get(route('dashboard'));

    expect($respuesta->viewData('page')['props']['locale'])->toBe(config('app.locale'));
});
