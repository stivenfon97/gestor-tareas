<?php

declare(strict_types=1);

use App\Enums\PrioridadTarea;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Cliente;
use App\Models\Modulo;
use App\Models\Proyecto;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\DB;

/**
 * El listado servido al componente `TablaDatos`: orden multicolumna, traducción
 * de los parámetros de la URL a estado de tabla y ruta completa del módulo.
 */
beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);
});

/** @return array<int, string> */
function codigosDelListadoDeTabla($respuesta): array
{
    return array_column($respuesta->viewData('page')['props']['tareas']['data'], 'codigo');
}

function propsDelListado($respuesta): array
{
    return $respuesta->viewData('page')['props'];
}

/** Árbol de tres niveles: Clientes › Facturación › Notas de crédito. */
function arbolDeTresNiveles(Proyecto $proyecto): Modulo
{
    $raiz = Modulo::factory()->for($proyecto)->create(['nombre' => 'Clientes']);
    $medio = Modulo::factory()->for($proyecto)->create([
        'nombre' => 'Facturación',
        'parent_id' => $raiz->getKey(),
    ]);

    return Modulo::factory()->for($proyecto)->create([
        'nombre' => 'Notas de crédito',
        'parent_id' => $medio->getKey(),
    ]);
}

/* ------------------------------------------------------ orden multicolumna */

it('ordena por varios campos, en la prioridad que dice la URL', function () {
    // Dos prioridades y dos códigos por prioridad: solo el desempate por código
    // distingue un orden correcto de uno que ignoró el segundo campo.
    $altaB = Tarea::factory()->for($this->proyecto)->create(['prioridad' => PrioridadTarea::Alta]);
    $bajaA = Tarea::factory()->for($this->proyecto)->create(['prioridad' => PrioridadTarea::Baja]);
    $altaA = Tarea::factory()->for($this->proyecto)->create(['prioridad' => PrioridadTarea::Alta]);
    $bajaB = Tarea::factory()->for($this->proyecto)->create(['prioridad' => PrioridadTarea::Baja]);

    // Los códigos son correlativos por creación, así que reordenarlos a mano
    // deja claro cuál es el desempate esperado.
    [$altaPrimero, $altaSegundo] = collect([$altaB, $altaA])->sortBy('codigo')->values()->all();
    [$bajaPrimero, $bajaSegundo] = collect([$bajaA, $bajaB])->sortBy('codigo')->values()->all();

    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', [
        $this->proyecto, 'sort' => '-prioridad,codigo',
    ]));

    expect(codigosDelListadoDeTabla($respuesta))->toBe([
        $altaPrimero->codigo, $altaSegundo->codigo,
        $bajaPrimero->codigo, $bajaSegundo->codigo,
    ]);
});

it('invierte el resultado al invertir la prioridad de los campos', function () {
    $alta = Tarea::factory()->for($this->proyecto)->create(['prioridad' => PrioridadTarea::Alta]);
    $baja = Tarea::factory()->for($this->proyecto)->create(['prioridad' => PrioridadTarea::Baja]);

    // Con `codigo` primero manda el código; con `-prioridad` primero, la
    // prioridad. Si el servidor ignorara la posición, las dos darían lo mismo.
    $porCodigo = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', [
        $this->proyecto, 'sort' => 'codigo,-prioridad',
    ]));

    $porPrioridad = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', [
        $this->proyecto, 'sort' => '-prioridad,codigo',
    ]));

    expect(codigosDelListadoDeTabla($porCodigo))->toBe([$alta->codigo, $baja->codigo])
        ->and(codigosDelListadoDeTabla($porPrioridad))->toBe([$alta->codigo, $baja->codigo]);

    // Y con la prioridad invertida sí cambian entre sí.
    $ascendente = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', [
        $this->proyecto, 'sort' => 'prioridad,codigo',
    ]));

    expect(codigosDelListadoDeTabla($ascendente))->toBe([$baja->codigo, $alta->codigo]);
});

it('descompone el orden en la lista que la tabla necesita para pintar la prioridad', function () {
    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', [
        $this->proyecto, 'sort' => '-prioridad,codigo',
    ]));

    expect(propsDelListado($respuesta)['orden'])->toBe([
        ['campo' => 'prioridad', 'direccion' => 'desc'],
        ['campo' => 'codigo', 'direccion' => 'asc'],
    ]);
});

it('sin sort explícito informa del orden por defecto, no de una lista vacía', function () {
    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', $this->proyecto));

    expect(propsDelListado($respuesta)['orden'])->toBe([
        ['campo' => 'codigo', 'direccion' => 'asc'],
    ]);
});

it('rechaza ordenar por un campo que no está permitido', function () {
    // `descripcion` no está en `allowedSorts`: preferimos el error a que el
    // parámetro se ignore en silencio y la tabla mienta sobre su orden.
    $this->actingAs($this->lider)
        ->get(route('proyectos.tareas.index', [$this->proyecto, 'sort' => 'descripcion']))
        ->assertBadRequest();
});

it('ordena por varios campos también en el listado de clientes', function () {
    $usuario = usuarioCon(RolGlobal::Administrador);

    Cliente::factory()->create(['nombre' => 'Zeta', 'activo' => true]);
    Cliente::factory()->create(['nombre' => 'Alfa', 'activo' => true]);

    // Posiciones relativas y no la lista entera: el proyecto del `beforeEach`
    // arrastra su propio cliente y su nombre no es asunto de esta prueba.
    $posiciones = function (string $sort) use ($usuario): array {
        $respuesta = $this->actingAs($usuario)->get(route('clientes.index', ['sort' => $sort]));
        $nombres = array_column($respuesta->viewData('page')['props']['clientes']['data'], 'nombre');

        return [
            'alfa' => array_search('Alfa', $nombres, true),
            'zeta' => array_search('Zeta', $nombres, true),
            'orden' => $respuesta->viewData('page')['props']['orden'],
        ];
    };

    $ascendente = $posiciones('nombre,-created_at');
    $descendente = $posiciones('-nombre,created_at');

    expect($ascendente['alfa'])->toBeLessThan($ascendente['zeta'])
        ->and($descendente['zeta'])->toBeLessThan($descendente['alfa'])
        ->and($ascendente['orden'])->toBe([
            ['campo' => 'nombre', 'direccion' => 'asc'],
            ['campo' => 'created_at', 'direccion' => 'desc'],
        ]);
});

/* ------------------------------------------- ruta del módulo y consultas */

it('muestra la ruta completa del módulo, no solo su nombre', function () {
    $hoja = arbolDeTresNiveles($this->proyecto);

    Tarea::factory()->for($this->proyecto)->create(['modulo_id' => $hoja->getKey()]);

    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', $this->proyecto));

    expect(propsDelListado($respuesta)['tareas']['data'][0]['modulo'])
        ->toBe('Clientes › Facturación › Notas de crédito');
});

it('no dispara una consulta por fila para resolver la ruta del módulo', function () {
    $hoja = arbolDeTresNiveles($this->proyecto);

    $consultas = function (int $filas) use ($hoja): int {
        Tarea::factory()->count($filas)->for($this->proyecto)
            ->create(['modulo_id' => $hoja->getKey()]);

        // Una petición de calentamiento antes de medir: la primera del proceso
        // carga roles y permisos y los deja en caché, y esa diferencia no tiene
        // nada que ver con el número de filas.
        $this->actingAs($this->lider)->get(route('proyectos.tareas.index', $this->proyecto));

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->lider)
            ->get(route('proyectos.tareas.index', $this->proyecto))
            ->assertOk();

        $total = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $total;
    };

    $conPocas = $consultas(5);
    $conMuchas = $consultas(25);

    // El número **no crece con las filas**: si creciera, `rutaCompleta()` estaría
    // subiendo por `padre` fila a fila otra vez.
    expect($conMuchas)->toBe($conPocas)
        // Techo generoso pero real: si alguien mete un `with` de más, salta.
        ->and($conMuchas)->toBeLessThanOrEqual(15);
});

it('deja el módulo nulo cuando la tarea no tiene ninguno', function () {
    Tarea::factory()->for($this->proyecto)->create(['modulo_id' => null]);

    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', $this->proyecto));

    expect(propsDelListado($respuesta)['tareas']['data'][0]['modulo'])->toBeNull();
});

/* ------------------------------------- traducción de la URL a estado de tabla */

it('traduce las columnas de la URL y deja nulo cuando no se piden', function () {
    $sinPedir = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', $this->proyecto));

    // Nulo significa «las de por defecto», y cuáles son es cosa de la interfaz:
    // el servidor no sabe qué columnas existen.
    expect(propsDelListado($sinPedir)['columnasVisibles'])->toBeNull();

    $pedidas = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', [
        $this->proyecto, 'columnas' => 'codigo,nombre,estado',
    ]));

    expect(propsDelListado($pedidas)['columnasVisibles'])->toBe(['codigo', 'nombre', 'estado']);
});

it('acota el tamaño de página a la lista admitida', function () {
    Tarea::factory()->count(20)->for($this->proyecto)->create();

    $valido = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', [
        $this->proyecto, 'por_pagina' => 15,
    ]));

    expect(propsDelListado($valido)['tareas']['data'])->toHaveCount(15);

    // Un valor fuera de la lista cae en el de por defecto: sin el tope, un
    // `por_pagina=100000` descarga el proyecto entero de una vez.
    $abusivo = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', [
        $this->proyecto, 'por_pagina' => 100000,
    ]));

    expect(propsDelListado($abusivo)['tareas']['per_page'])->toBe(30);
});

it('devuelve los filtros activos tal y como llegaron', function () {
    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', [
        $this->proyecto, 'filter' => ['estado' => 'pendiente', 'texto' => 'trm'],
    ]));

    expect(propsDelListado($respuesta)['filtros'])
        ->toBe(['estado' => 'pendiente', 'texto' => 'trm']);
});

it('aplica visiblesPara antes que el orden, las columnas y el tamaño de página', function () {
    $ajeno = Proyecto::factory()->create();
    $tareaAjena = Tarea::factory()->for($ajeno)->create();
    Tarea::factory()->for($this->proyecto)->create();

    $miembro = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($miembro, $this->proyecto, RolProyecto::Colaborador);

    // Todos los parámetros nuevos a la vez, buscando la tarea ajena por texto.
    $respuesta = $this->actingAs($miembro)->get(route('proyectos.tareas.index', [
        $this->proyecto,
        'filter' => ['texto' => $tareaAjena->codigo],
        'sort' => '-prioridad,codigo',
        'columnas' => 'codigo,nombre',
        'por_pagina' => 100,
    ]));

    expect(codigosDelListadoDeTabla($respuesta))->toBe([]);
});

/* ------------------------------------------------ pestañas del concentrador */

it('lleva la pestaña activa del proyecto en la URL', function () {
    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.show', [
        $this->proyecto, 'seccion' => 'tareas',
    ]));

    expect($respuesta->viewData('page')['props']['seccion'])->toBe('tareas');
});

it('abre el resumen cuando la sección pedida no existe', function () {
    // Un `?seccion=` inventado no puede dejar la página en blanco.
    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.show', [
        $this->proyecto, 'seccion' => 'inventada',
    ]));

    expect($respuesta->viewData('page')['props']['seccion'])->toBe('resumen');
});
