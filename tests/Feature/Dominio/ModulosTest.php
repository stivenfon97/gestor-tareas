<?php

declare(strict_types=1);

use App\Models\Modulo;
use App\Models\Proyecto;

it('devuelve solo los módulos de primer nivel con el scope raiz', function () {
    $proyecto = Proyecto::factory()->create();

    $clientes = Modulo::factory()->for($proyecto)->create(['nombre' => 'Clientes']);
    Modulo::factory()->hijoDe($clientes)->create(['nombre' => 'Facturación']);
    Modulo::factory()->for($proyecto)->create(['nombre' => 'Reportes']);

    $raiz = $proyecto->modulos()->raiz()->pluck('nombre')->all();

    sort($raiz);

    expect($raiz)->toBe(['Clientes', 'Reportes']);
});

it('recorre la jerarquía hacia abajo por hijos', function () {
    $proyecto = Proyecto::factory()->create();

    $clientes = Modulo::factory()->for($proyecto)->create(['nombre' => 'Clientes']);
    Modulo::factory()->hijoDe($clientes)->create(['nombre' => 'Contactos', 'orden' => 1]);
    Modulo::factory()->hijoDe($clientes)->create(['nombre' => 'Ficha', 'orden' => 0]);

    expect($clientes->hijos()->pluck('nombre')->all())->toBe(['Ficha', 'Contactos']);
});

it('formatea la ruta completa desde la raíz', function () {
    $proyecto = Proyecto::factory()->create();

    $clientes = Modulo::factory()->for($proyecto)->create(['nombre' => 'Clientes']);
    $facturacion = Modulo::factory()->hijoDe($clientes)->create(['nombre' => 'Facturación']);

    expect($facturacion->rutaCompleta())->toBe('Clientes › Facturación')
        ->and($clientes->rutaCompleta())->toBe('Clientes');
});

it('formatea la ruta de un tercer nivel', function () {
    $proyecto = Proyecto::factory()->create();

    $raiz = Modulo::factory()->for($proyecto)->create(['nombre' => 'Operación']);
    $medio = Modulo::factory()->hijoDe($raiz)->create(['nombre' => 'Rutas']);
    $hoja = Modulo::factory()->hijoDe($medio)->create(['nombre' => 'Programación']);

    expect($hoja->rutaCompleta())->toBe('Operación › Rutas › Programación');
});
