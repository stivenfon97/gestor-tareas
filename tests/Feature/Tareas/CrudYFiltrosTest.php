<?php

declare(strict_types=1);

use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Enums\TipoTarea;
use App\Models\Modulo;
use App\Models\NotaTarea;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);
});

/** Códigos devueltos por el listado, en orden. */
function codigosDelListado($respuesta): array
{
    return array_column($respuesta->viewData('page')['props']['tareas']['data'], 'codigo');
}

it('crea una tarea y le asigna el código correlativo', function () {
    $this->actingAs($this->lider)
        ->post(route('proyectos.tareas.store', $this->proyecto), [
            'nombre' => 'Exportar el listado de clientes a Excel',
            'tipo' => TipoTarea::Desarrollo->value,
            'prioridad' => PrioridadTarea::Media->value,
        ])
        ->assertRedirect();

    $tarea = Tarea::query()->sole();

    expect($tarea->codigo)->toBe($this->proyecto->prefijo_tarea.'-0001')
        ->and($tarea->estado)->toBe(EstadoTarea::Pendiente)
        ->and($tarea->creado_por)->toBe($this->lider->id);
});

it('asigna la tarea nueva al periodo abierto del proyecto', function () {
    $periodo = Periodo::factory()->for($this->proyecto)->create();

    $this->actingAs($this->lider)
        ->post(route('proyectos.tareas.store', $this->proyecto), [
            'nombre' => 'Una tarea del periodo abierto',
            'tipo' => TipoTarea::Desarrollo->value,
            'prioridad' => PrioridadTarea::Media->value,
        ]);

    expect(Tarea::query()->sole()->periodo_id)->toBe($periodo->id);
});

it('crea la tarea sin periodo cuando no hay ninguno abierto', function () {
    Periodo::factory()->for($this->proyecto)->cerrado()->create();

    $this->actingAs($this->lider)
        ->post(route('proyectos.tareas.store', $this->proyecto), [
            'nombre' => 'Una tarea huérfana de periodo',
            'tipo' => TipoTarea::Desarrollo->value,
            'prioridad' => PrioridadTarea::Media->value,
        ]);

    expect(Tarea::query()->sole()->periodo_id)->toBeNull();
});

it('avisa en el formulario de que no hay periodo abierto', function () {
    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.tareas.create', $this->proyecto));

    expect($respuesta->viewData('page')['props']['periodoAbierto'])->toBeNull();
});

it('sugiere el último módulo usado en el proyecto', function () {
    $viejo = Modulo::factory()->for($this->proyecto)->create();
    $reciente = Modulo::factory()->for($this->proyecto)->create();

    Tarea::factory()->for($this->proyecto)->create(['modulo_id' => $viejo->id]);
    Tarea::factory()->for($this->proyecto)->create(['modulo_id' => $reciente->id]);

    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.tareas.create', $this->proyecto));

    expect($respuesta->viewData('page')['props']['sugerencias']['modulo_id'])->toBe($reciente->id);
});

it('rechaza un módulo de otro proyecto al crear', function () {
    $ajeno = Modulo::factory()->for(Proyecto::factory()->create())->create();

    $this->actingAs($this->lider)
        ->post(route('proyectos.tareas.store', $this->proyecto), [
            'nombre' => 'Intruso',
            'tipo' => TipoTarea::Desarrollo->value,
            'prioridad' => PrioridadTarea::Media->value,
            'modulo_id' => $ajeno->id,
        ])
        ->assertSessionHasErrors('modulo_id');
});

it('no deja cambiar el estado desde el formulario general de edición', function () {
    $tarea = Tarea::factory()->for($this->proyecto)->create(['estado' => EstadoTarea::Pendiente]);

    $this->actingAs($this->lider)
        ->put(route('tareas.update', $tarea), [
            'nombre' => 'Renombrada',
            'prioridad' => PrioridadTarea::Alta->value,
            // Se envía a propósito: el formulario general no lo acepta.
            'estado' => EstadoTarea::Entregada->value,
        ])
        ->assertRedirect();

    $tarea = $tarea->fresh();

    expect($tarea->nombre)->toBe('Renombrada')
        ->and($tarea->prioridad)->toBe(PrioridadTarea::Alta)
        // El estado no se movió: tiene su propio endpoint.
        ->and($tarea->estado)->toBe(EstadoTarea::Pendiente);
});

it('no deja cambiar el tipo al editar: se fija al crear', function () {
    $tarea = Tarea::factory()->for($this->proyecto)->create(['tipo' => TipoTarea::Desarrollo]);

    $this->actingAs($this->lider)
        ->put(route('tareas.update', $tarea), [
            'nombre' => $tarea->nombre,
            'prioridad' => $tarea->prioridad->value,
            'tipo' => TipoTarea::Correccion->value,
        ]);

    expect($tarea->fresh()->tipo)->toBe(TipoTarea::Desarrollo);
});

it('precarga la prioridad alta en correccion y soporte', function (string $tipo) {
    $this->actingAs($this->lider)
        ->post(route('proyectos.tareas.store', $this->proyecto), [
            'nombre' => 'Una tarea',
            'tipo' => $tipo,
            'prioridad' => PrioridadTarea::Alta->value,
        ]);

    expect(Tarea::query()->sole()->prioridad)->toBe(PrioridadTarea::Alta);
})->with(['correccion', 'soporte']);

it('borra la tarea en suave y conserva su historial', function () {
    $tarea = Tarea::factory()->for($this->proyecto)->create();

    $this->actingAs($this->lider)
        ->delete(route('tareas.destroy', $tarea))
        ->assertRedirect(route('proyectos.tareas.index', $this->proyecto));

    expect(Tarea::query()->whereKey($tarea->id)->exists())->toBeFalse()
        ->and(Tarea::withTrashed()->whereKey($tarea->id)->exists())->toBeTrue();
});

it('filtra por módulo incluyendo las tareas de sus submódulos', function () {
    $clientes = Modulo::factory()->for($this->proyecto)->create(['nombre' => 'Clientes']);
    $facturacion = Modulo::factory()->hijoDe($clientes)->create(['nombre' => 'Facturación']);
    $notas = Modulo::factory()->hijoDe($facturacion)->create(['nombre' => 'Notas crédito']);
    $reportes = Modulo::factory()->for($this->proyecto)->create(['nombre' => 'Reportes']);

    $enRaiz = Tarea::factory()->for($this->proyecto)->create(['modulo_id' => $clientes->id]);
    $enHijo = Tarea::factory()->for($this->proyecto)->create(['modulo_id' => $facturacion->id]);
    $enNieto = Tarea::factory()->for($this->proyecto)->create(['modulo_id' => $notas->id]);
    $fuera = Tarea::factory()->for($this->proyecto)->create(['modulo_id' => $reportes->id]);
    $sinModulo = Tarea::factory()->for($this->proyecto)->create(['modulo_id' => null]);

    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', [
        $this->proyecto, 'filter' => ['modulo_id' => $clientes->id],
    ]));

    $codigos = codigosDelListado($respuesta);

    expect($codigos)->toHaveCount(3)
        ->and($codigos)->toContain($enRaiz->codigo, $enHijo->codigo, $enNieto->codigo)
        ->and($codigos)->not->toContain($fuera->codigo)
        ->and($codigos)->not->toContain($sinModulo->codigo);
});

it('filtra por estado, tipo y prioridad', function () {
    $buscada = Tarea::factory()->for($this->proyecto)->deCorreccion()->critica()->enProceso()->create();
    Tarea::factory()->for($this->proyecto)->create(['tipo' => TipoTarea::Desarrollo]);

    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', [
        $this->proyecto,
        'filter' => ['estado' => 'en_proceso', 'tipo' => 'correccion', 'prioridad' => 'critica'],
    ]));

    expect(codigosDelListado($respuesta))->toBe([$buscada->codigo]);
});

it('filtra por periodo', function () {
    $periodo = Periodo::factory()->for($this->proyecto)->create();
    $otro = Periodo::factory()->for($this->proyecto)->cerrado()->create();

    $dentro = Tarea::factory()->for($this->proyecto)->create(['periodo_id' => $periodo->id]);
    Tarea::factory()->for($this->proyecto)->create(['periodo_id' => $otro->id]);

    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', [
        $this->proyecto, 'filter' => ['periodo_id' => $periodo->id],
    ]));

    expect(codigosDelListado($respuesta))->toBe([$dentro->codigo]);
});

it('filtra las arrastradas', function () {
    $periodo = Periodo::factory()->for($this->proyecto)->cerrado()->create();

    $arrastrada = Tarea::factory()->for($this->proyecto)->arrastrada($periodo)->create();
    $normal = Tarea::factory()->for($this->proyecto)->create();

    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', [
        $this->proyecto, 'filter' => ['arrastradas' => '1'],
    ]));

    expect(codigosDelListado($respuesta))->toBe([$arrastrada->codigo])
        ->and(codigosDelListado($respuesta))->not->toContain($normal->codigo);
});

it('busca por texto libre en nombre, código y descripción', function () {
    $porNombre = Tarea::factory()->for($this->proyecto)->create(['nombre' => 'Ajustar el pie del PDF']);
    $porDescripcion = Tarea::factory()->for($this->proyecto)->create([
        'nombre' => 'Otra cosa',
        'descripcion' => 'El PDF sale sin logo en Safari',
    ]);
    $fuera = Tarea::factory()->for($this->proyecto)->create([
        'nombre' => 'Nada que ver',
        'descripcion' => 'Nada que ver tampoco',
    ]);

    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', [
        $this->proyecto, 'filter' => ['texto' => 'PDF'],
    ]));

    $codigos = codigosDelListado($respuesta);

    expect($codigos)->toContain($porNombre->codigo, $porDescripcion->codigo)
        ->and($codigos)->not->toContain($fuera->codigo);

    // Y por código, que es como se busca cuando el cliente cita uno.
    $porCodigo = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', [
        $this->proyecto, 'filter' => ['texto' => $fuera->codigo],
    ]));

    expect(codigosDelListado($porCodigo))->toBe([$fuera->codigo]);
});

it('ordena por prioridad según su peso, no alfabéticamente', function () {
    $baja = Tarea::factory()->for($this->proyecto)->create(['prioridad' => PrioridadTarea::Baja]);
    $critica = Tarea::factory()->for($this->proyecto)->create(['prioridad' => PrioridadTarea::Critica]);
    $media = Tarea::factory()->for($this->proyecto)->create(['prioridad' => PrioridadTarea::Media]);
    $alta = Tarea::factory()->for($this->proyecto)->create(['prioridad' => PrioridadTarea::Alta]);

    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', [
        $this->proyecto, 'sort' => '-prioridad',
    ]));

    // Alfabéticamente sería alta, baja, critica, media: sin ningún sentido.
    expect(codigosDelListado($respuesta))
        ->toBe([$critica->codigo, $alta->codigo, $media->codigo, $baja->codigo]);
});

it('pagina el listado del lado del servidor', function () {
    Tarea::factory(35)->for($this->proyecto)->create();

    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.tareas.index', $this->proyecto));

    $tareas = $respuesta->viewData('page')['props']['tareas'];

    expect($tareas['data'])->toHaveCount(30)
        ->and($tareas['total'])->toBe(35)
        ->and($tareas['last_page'])->toBe(2);
});

it('aplica visiblesPara aunque se manipulen los parámetros del filtro', function () {
    $ajeno = Proyecto::factory()->create();
    $tareaAjena = Tarea::factory()->for($ajeno)->create();
    $miTarea = Tarea::factory()->for($this->proyecto)->create();

    $miembro = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($miembro, $this->proyecto, RolProyecto::Colaborador);

    // Se pide el listado del proyecto propio pero se intenta colar el ajeno por
    // el filtro de periodo y de texto.
    $respuesta = $this->actingAs($miembro)->get(route('proyectos.tareas.index', [
        $this->proyecto, 'filter' => ['texto' => $tareaAjena->codigo],
    ]));

    expect(codigosDelListado($respuesta))->toBe([]);

    // Y el proyecto ajeno ni se abre.
    $this->actingAs($miembro)
        ->get(route('proyectos.tareas.index', $ajeno))
        ->assertForbidden();

    $this->actingAs($miembro)
        ->get(route('tareas.show', $tareaAjena))
        ->assertForbidden();

    $this->actingAs($miembro)
        ->get(route('tareas.show', $miTarea))
        ->assertOk();
});

it('niega crear y editar tareas al observador y al cliente', function (string $rol) {
    $usuario = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($usuario, $this->proyecto, RolProyecto::from($rol));

    $tarea = Tarea::factory()->for($this->proyecto)->create();

    $this->actingAs($usuario)
        ->post(route('proyectos.tareas.store', $this->proyecto), [
            'nombre' => 'Intento',
            'tipo' => TipoTarea::Desarrollo->value,
            'prioridad' => PrioridadTarea::Media->value,
        ])
        ->assertForbidden();

    $this->actingAs($usuario)
        ->put(route('tareas.update', $tarea), [
            'nombre' => 'Intento',
            'prioridad' => PrioridadTarea::Media->value,
        ])
        ->assertForbidden();

    // Pero el listado y el detalle sí los ve.
    $this->actingAs($usuario)
        ->get(route('proyectos.tareas.index', $this->proyecto))
        ->assertOk();
})->with(['observador', 'cliente']);

it('el detalle trae historial, notas y transiciones disponibles', function () {
    $tarea = Tarea::factory()->for($this->proyecto)->create();

    $this->actingAs($this->lider)->put(route('tareas.estado.update', $tarea), ['estado' => 'en_proceso']);

    $this->actingAs($this->lider)->post(route('tareas.notas.store', $tarea), [
        'contenido' => 'Desplegado en pruebas',
        'visibilidad' => 'cliente',
    ]);

    $props = $this->actingAs($this->lider)
        ->get(route('tareas.show', $tarea))
        ->viewData('page')['props'];

    expect($props['historial'])->toHaveCount(1)
        ->and($props['historial'][0]['nuevo'])->toBe('en_proceso')
        ->and($props['notas'])->toHaveCount(1)
        ->and($props['notas'][0]['contenido'])->toBe('Desplegado en pruebas')
        ->and(array_column($props['transiciones'], 'valor'))->toBe(['en_espera', 'terminada', 'cancelada']);
});

it('no muestra al cliente las notas internas del detalle', function () {
    $cliente = usuarioCon(RolGlobal::Invitado);
    asignarAProyecto($cliente, $this->proyecto, RolProyecto::Cliente);

    $tarea = Tarea::factory()->for($this->proyecto)->create();

    NotaTarea::factory()->for($tarea)->create(['contenido' => 'Margen real del periodo']);
    NotaTarea::factory()->for($tarea)->visibleParaCliente()->create(['contenido' => 'Desplegado en pruebas']);

    $props = $this->actingAs($cliente)->get(route('tareas.show', $tarea))->viewData('page')['props'];

    expect(collect($props['notas'])->pluck('contenido')->all())->toBe(['Desplegado en pruebas']);
});

it('una nota nace interna salvo que se marque lo contrario', function () {
    $tarea = Tarea::factory()->for($this->proyecto)->create();

    $this->actingAs($this->lider)->post(route('tareas.notas.store', $tarea), [
        'contenido' => 'Una nota cualquiera',
        'visibilidad' => 'interna',
    ]);

    expect($tarea->notas()->sole()->visibilidad->value)->toBe('interna');
});
