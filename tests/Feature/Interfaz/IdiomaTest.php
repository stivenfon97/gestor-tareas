<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\Carbon;

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

test('el idioma activo también fija el locale de Carbon, no solo el de Laravel', function () {
    // Sin esto, diffForHumans() (SecurityController) sale siempre en inglés
    // -el locale por defecto de Carbon-, sea cual sea el idioma de la
    // interfaz: Laravel no sincroniza los dos solo con app()->setLocale().
    $usuario = User::factory()->create();

    $this->actingAs($usuario)
        ->withUnencryptedCookie('idioma', 'en')
        ->get(route('dashboard'));

    expect(Carbon::getLocale())->toBe('en');
});
