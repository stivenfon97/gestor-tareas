<?php

declare(strict_types=1);

use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Modulo;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Services\ParsearTareaRapida;
use Database\Seeders\RolesYPermisosSeeder;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();
    $this->modulo = Modulo::factory()->for($this->proyecto)->create(['nombre' => 'Clientes']);

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);

    $this->parsear = app(ParsearTareaRapida::class);
});

it('parsea una línea completa con los cuatro campos', function () {
    $filas = ($this->parsear)(
        $this->proyecto,
        'migracion de interfaz de clientes | Pendiente | se debe migrar la interfaz al nuevo diseño | Clientes',
    );

    expect($filas)->toHaveCount(1);

    expect($filas[0])
        ->nombre->toBe('migracion de interfaz de clientes')
        ->estado->toBe('pendiente')
        ->descripcion->toBe('se debe migrar la interfaz al nuevo diseño')
        ->modulo->toBe('Clientes')
        ->modulo_id->toBe($this->modulo->id)
        ->modulo_por_crear->toBeFalse()
        ->avisos->toBe([]);
});

it('parsea una línea con solo el nombre y cae a pendiente', function () {
    $filas = ($this->parsear)($this->proyecto, 'revisar el cálculo del IVA');

    expect($filas[0])
        ->nombre->toBe('revisar el cálculo del IVA')
        ->estado->toBe('pendiente')
        ->descripcion->toBeNull()
        ->modulo->toBeNull()
        ->avisos->toBe([]);
});

it('reconoce el estado escrito con acentos, mayúsculas o guion bajo', function (string $crudo, string $esperado) {
    $filas = ($this->parsear)($this->proyecto, "una tarea | {$crudo}");

    expect($filas[0]['estado'])->toBe($esperado)
        ->and($filas[0]['avisos'])->not->toContain('tareas.rapida.avisos.estado_desconocido');
})->with([
    'con espacios' => ['En Proceso', 'en_proceso'],
    'minúsculas con espacio' => ['en proceso', 'en_proceso'],
    'mayúsculas con guion bajo' => ['EN_PROCESO', 'en_proceso'],
    'con espacios de sobra' => ['  en   proceso  ', 'en_proceso'],
    'terminada' => ['Terminada', 'terminada'],
    'entregada en mayúsculas' => ['ENTREGADA', 'entregada'],
]);

it('avisa del módulo inexistente y ofrece crearlo en lugar de fallar', function () {
    $filas = ($this->parsear)($this->proyecto, 'una tarea | Pendiente | algo | Facturación electrónica');

    expect($filas[0])
        ->modulo->toBe('Facturación electrónica')
        ->modulo_id->toBeNull()
        ->modulo_por_crear->toBeTrue()
        ->avisos->toContain('tareas.rapida.avisos.modulo_nuevo');
});

it('usa pendiente y avisa cuando el estado no se reconoce, sin rechazar la línea', function () {
    $filas = ($this->parsear)($this->proyecto, 'una tarea | Congelada | algo');

    expect($filas)->toHaveCount(1);

    expect($filas[0])
        ->estado->toBe('pendiente')
        ->avisos->toContain('tareas.rapida.avisos.estado_desconocido');
});

it('deja el separador dentro de la descripción en lugar de robárselo al módulo', function () {
    $filas = ($this->parsear)(
        $this->proyecto,
        'el total sale mal | En proceso | pasa con descuento | y también con IVA | Clientes',
    );

    // El módulo se toma desde el final; la descripción se queda con lo de en
    // medio, separadores incluidos.
    expect($filas[0])
        ->nombre->toBe('el total sale mal')
        ->estado->toBe('en_proceso')
        ->descripcion->toBe('pasa con descuento | y también con IVA')
        ->modulo->toBe('Clientes')
        ->modulo_id->toBe($this->modulo->id);
});

it('no parte la descripción cuando no hay campo de módulo', function () {
    $filas = ($this->parsear)($this->proyecto, 'el total sale mal | En proceso | pasa con descuento');

    expect($filas[0])
        ->descripcion->toBe('pasa con descuento')
        ->modulo->toBeNull();
});

it('salta las líneas vacías intercaladas sin avisar', function () {
    $texto = "primera tarea\n\n   \nsegunda tarea\n\n";

    $filas = ($this->parsear)($this->proyecto, $texto);

    expect($filas)->toHaveCount(2)
        ->and(array_column($filas, 'nombre'))->toBe(['primera tarea', 'segunda tarea'])
        // El número de línea es el del texto original, no el del resultado.
        ->and(array_column($filas, 'linea'))->toBe([1, 4]);
});

it('parsea diez líneas mezcladas de una vez', function () {
    $texto = implode("\n", [
        'migracion de interfaz de clientes | Pendiente | se debe migrar la interfaz | Clientes',
        'revisar el cálculo del IVA',
        '',
        'ajustar el pie del PDF | En Proceso | falta el NIT',
        'exportar a Excel | terminada | ya está en pruebas | Clientes',
        'evaluar pasarela de pagos | Congelada',
        '   ',
        'corregir el filtro de fechas | EN_PROCESO | ignora el último día | Reportes',
        'documentar el despliegue | | sin estado explícito | Clientes',
        'revisar permisos | Entregada | | Clientes',
        'tarea con | separador de más | en la | descripción larga | de verdad',
    ]);

    $filas = ($this->parsear)($this->proyecto, $texto);

    // Nueve líneas con contenido; las dos vacías no producen fila.
    expect($filas)->toHaveCount(9);

    expect(array_column($filas, 'estado'))->toBe([
        'pendiente',   // explícito
        'pendiente',   // solo nombre
        'en_proceso',
        'terminada',
        'pendiente',   // "Congelada" no se reconoce
        'en_proceso',  // EN_PROCESO
        'pendiente',   // estado vacío entre separadores
        'entregada',
        'pendiente',   // separadores de más
    ]);

    // El módulo "Reportes" no existe: se propone crearlo.
    $corregirFiltro = collect($filas)->firstWhere('nombre', 'corregir el filtro de fechas');

    expect($corregirFiltro['modulo_por_crear'])->toBeTrue();

    // Y "Clientes" sí existe en las que lo mencionan.
    $migracion = collect($filas)->firstWhere('nombre', 'migracion de interfaz de clientes');

    expect($migracion['modulo_id'])->toBe($this->modulo->id);
});

it('marca la fila cuyo estado exige motivo en lugar de degradarla en silencio', function () {
    $filas = ($this->parsear)($this->proyecto, 'algo bloqueado | En espera | falta el acceso');

    expect($filas[0])
        ->estado->toBe('en_espera')
        ->requiere_motivo->toBe('motivo_espera')
        ->avisos->toContain('tareas.rapida.avisos.requiere_motivo');
});

it('avisa de la línea sin nombre', function () {
    $filas = ($this->parsear)($this->proyecto, ' | Pendiente | solo descripción');

    expect($filas[0])
        ->nombre->toBe('')
        ->avisos->toContain('tareas.rapida.avisos.sin_nombre');
});

it('previsualiza sin crear ni una sola tarea', function () {
    $respuesta = $this->actingAs($this->lider)
        ->post(route('proyectos.tareas.rapida.previsualizar', $this->proyecto), [
            'texto' => "una tarea\notra tarea | En proceso",
        ]);

    $respuesta->assertRedirect()->assertInertiaFlash('previsualizacion');

    expect(Tarea::query()->count())->toBe(0);
});

it('crea una tarea por línea desde la previsualización confirmada', function () {
    $this->actingAs($this->lider)
        ->post(route('proyectos.tareas.rapida.store', $this->proyecto), [
            'filas' => [
                ['nombre' => 'primera', 'estado' => 'pendiente', 'tipo' => 'desarrollo', 'modulo_id' => $this->modulo->id],
                ['nombre' => 'segunda', 'estado' => 'en_proceso', 'tipo' => 'mejora', 'descripcion' => 'con descripción'],
            ],
        ])
        ->assertRedirect(route('proyectos.tareas.index', $this->proyecto));

    expect(Tarea::query()->count())->toBe(2);

    $primera = Tarea::query()->where('nombre', 'primera')->sole();

    expect($primera->modulo_id)->toBe($this->modulo->id)
        ->and($primera->creado_por)->toBe($this->lider->id)
        // El observer asigna el código correlativo también en lote.
        ->and($primera->codigo)->toStartWith($this->proyecto->prefijo_tarea.'-');
});

it('crea el módulo que falta una sola vez aunque lo mencionen dos líneas', function () {
    $this->actingAs($this->lider)
        ->post(route('proyectos.tareas.rapida.store', $this->proyecto), [
            'filas' => [
                ['nombre' => 'primera', 'estado' => 'pendiente', 'tipo' => 'desarrollo', 'modulo_nuevo' => 'Reportes'],
                ['nombre' => 'segunda', 'estado' => 'pendiente', 'tipo' => 'desarrollo', 'modulo_nuevo' => 'Reportes'],
            ],
        ])
        ->assertRedirect();

    $reportes = Modulo::query()->where('proyecto_id', $this->proyecto->id)->where('nombre', 'Reportes')->get();

    expect($reportes)->toHaveCount(1);

    expect(Tarea::query()->pluck('modulo_id')->unique()->all())->toBe([$reportes->first()->id]);
});

it('rechaza el lote entero si una fila en_espera llega sin motivo', function () {
    $this->actingAs($this->lider)
        ->post(route('proyectos.tareas.rapida.store', $this->proyecto), [
            'filas' => [
                ['nombre' => 'primera', 'estado' => 'pendiente', 'tipo' => 'desarrollo'],
                ['nombre' => 'segunda', 'estado' => 'en_espera', 'tipo' => 'desarrollo'],
            ],
        ])
        ->assertSessionHasErrors('filas.1.motivo_espera');

    // Todo o nada: ni siquiera entra la primera.
    expect(Tarea::query()->count())->toBe(0);
});

it('niega la captura rápida a un observador', function () {
    $observador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($observador, $this->proyecto, RolProyecto::Observador);

    $this->actingAs($observador)
        ->post(route('proyectos.tareas.rapida.previsualizar', $this->proyecto), ['texto' => 'algo'])
        ->assertForbidden();

    $this->actingAs($observador)
        ->post(route('proyectos.tareas.rapida.store', $this->proyecto), [
            'filas' => [['nombre' => 'algo', 'estado' => 'pendiente', 'tipo' => 'desarrollo']],
        ])
        ->assertForbidden();
});

it('asigna el periodo abierto a todas las tareas del lote', function () {
    // La factoría de periodos nace abierta.
    $periodo = Periodo::factory()->for($this->proyecto)->create();

    $this->actingAs($this->lider)
        ->post(route('proyectos.tareas.rapida.store', $this->proyecto), [
            'filas' => [
                ['nombre' => 'primera', 'estado' => 'pendiente', 'tipo' => 'desarrollo'],
                ['nombre' => 'segunda', 'estado' => 'pendiente', 'tipo' => 'desarrollo'],
            ],
        ]);

    expect(Tarea::query()->pluck('periodo_id')->unique()->all())->toBe([$periodo->id]);
});
