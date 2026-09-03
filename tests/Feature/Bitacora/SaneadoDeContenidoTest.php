<?php

declare(strict_types=1);

use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Proyecto;
use App\Models\Reunion;
use Database\Seeders\RolesYPermisosSeeder;

/**
 * El HTML que produce el editor es HTML que un usuario puede fabricar: llega
 * de una petición HTTP, no del editor. `contenido_html` se renderiza con
 * `v-html` en `reuniones/Detalle.vue`, así que un fallo de saneado es
 * ejecución de código en el navegador de quien abra la reunión.
 */
beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);
});

it('sanea <script>, onerror y javascript: del contenido_html al guardar', function () {
    $malicioso = '<p>Hola</p>'
        .'<script>alert(document.cookie)</script>'
        .'<img src="x" onerror="alert(1)">'
        .'<a href="javascript:alert(1)">enlace</a>';

    $respuesta = $this->actingAs($this->lider)->post(
        route('proyectos.reuniones.store', $this->proyecto),
        datosDeReunionValidos(['contenido_html' => $malicioso]),
    );

    $respuesta->assertRedirect();

    $reunion = Reunion::query()->sole();

    expect($reunion->contenido_html)
        ->not->toContain('<script')
        ->not->toContain('onerror')
        ->not->toContain('javascript:')
        // Lo legítimo sobrevive: el saneador no vacía el documento entero.
        ->toContain('Hola');
});

it('rechaza un contenido_json con un nodo image (D1, sin base64 en el documento)', function () {
    $conImagen = [
        'type' => 'doc',
        'content' => [[
            'type' => 'image',
            'attrs' => ['src' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAE='],
        ]],
    ];

    $respuesta = $this->actingAs($this->lider)->post(
        route('proyectos.reuniones.store', $this->proyecto),
        datosDeReunionValidos(['contenido_json' => $conImagen]),
    );

    $respuesta->assertInvalid(['contenido_json']);
    expect(Reunion::query()->count())->toBe(0);
});

it('rechaza un nodo o marca fuera de la lista blanca aunque el tipo raíz sea válido', function () {
    $conNodoDesconocido = [
        'type' => 'doc',
        'content' => [[
            'type' => 'paragraph',
            'content' => [[
                'type' => 'text',
                'text' => 'texto',
                'marks' => [['type' => 'textStyle', 'attrs' => ['color' => 'red']]],
            ]],
        ]],
    ];

    $this->actingAs($this->lider)->post(
        route('proyectos.reuniones.store', $this->proyecto),
        datosDeReunionValidos(['contenido_json' => $conNodoDesconocido]),
    )->assertInvalid(['contenido_json']);
});

it('mantiene contenido_json y contenido_html coherentes tras guardar', function () {
    $datos = datosDeReunionValidos([
        'contenido_html' => '<p>Se acordó revisar el <strong>alcance</strong> del periodo.</p>',
    ]);

    $this->actingAs($this->lider)
        ->post(route('proyectos.reuniones.store', $this->proyecto), $datos)
        ->assertRedirect();

    $reunion = Reunion::query()->sole();

    expect($reunion->contenido_json)->toBe($datos['contenido_json'])
        ->and($reunion->contenido_html)->toContain('Se acordó revisar el')
        ->and($reunion->contenido_html)->toContain('<strong>alcance</strong>');
});

it('fuerza rel="noopener noreferrer" y target="_blank" en los enlaces guardados', function () {
    $datos = datosDeReunionValidos([
        'contenido_html' => '<p>Ver <a href="https://ejemplo.com">el documento</a>.</p>',
    ]);

    $this->actingAs($this->lider)
        ->post(route('proyectos.reuniones.store', $this->proyecto), $datos)
        ->assertRedirect();

    $reunion = Reunion::query()->sole();

    expect($reunion->contenido_html)
        ->toContain('rel="noopener noreferrer"')
        ->toContain('target="_blank"')
        ->toContain('href="https://ejemplo.com"');
});
