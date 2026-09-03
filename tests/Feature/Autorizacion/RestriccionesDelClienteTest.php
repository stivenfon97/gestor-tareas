<?php

declare(strict_types=1);

use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\NotaTarea;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Reporte;
use App\Models\Reunion;
use App\Models\Tarea;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();

    $this->cliente = usuarioCon(RolGlobal::Invitado);
    asignarAProyecto($this->cliente, $this->proyecto, RolProyecto::Cliente);

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);
});

it('niega al cliente el listado de pagos', function () {
    $this->actingAs($this->cliente)
        ->get(route('proyectos.pagos.index', $this->proyecto))
        ->assertForbidden();
});

it('niega al cliente el detalle de un pago aunque escriba el id', function () {
    $periodo = Periodo::factory()->for($this->proyecto)->cerrado()->create();
    $pago = Pago::factory()->for($periodo)->create();

    $this->actingAs($this->cliente)
        ->get(route('pagos.show', $pago))
        ->assertForbidden();
});

it('niega al cliente la generación del reporte completo', function () {
    $periodo = Periodo::factory()->for($this->proyecto)->create();

    $this->actingAs($this->cliente)
        ->post(route('proyectos.reportes.store', $this->proyecto), [
            'periodo_id' => $periodo->id,
            'tipo' => 'completo',
        ])
        ->assertForbidden();

    expect(Reporte::query()->count())->toBe(0);
});

it('la policy de reportes niega la creación al cliente y la permite al líder', function () {
    expect($this->cliente->can('create', [Reporte::class, $this->proyecto]))->toBeFalse()
        ->and($this->lider->can('create', [Reporte::class, $this->proyecto]))->toBeTrue();
});

it('el líder sí ve los pagos', function () {
    $this->actingAs($this->lider)
        ->get(route('proyectos.pagos.index', $this->proyecto))
        ->assertOk();
});

it('no muestra al cliente las reuniones internas en el listado, no solo en el detalle', function () {
    $interna = Reunion::factory()->for($this->proyecto)->create(['titulo' => 'Comité interno de márgenes']);
    $compartida = Reunion::factory()->for($this->proyecto)->visibleParaCliente()->create(['titulo' => 'Revisión con el cliente']);

    $respuesta = $this->actingAs($this->cliente)
        ->get(route('proyectos.reuniones.index', $this->proyecto));

    $respuesta->assertOk();

    $titulos = collect($respuesta->viewData('page')['props']['reuniones'])->pluck('titulo')->all();

    expect($titulos)->toBe([$compartida->titulo])
        ->and($titulos)->not->toContain($interna->titulo);
});

it('niega al cliente el detalle de una reunión interna', function () {
    $interna = Reunion::factory()->for($this->proyecto)->create();

    $this->actingAs($this->cliente)
        ->get(route('reuniones.show', $interna))
        ->assertForbidden();
});

it('deja al cliente ver la reunión marcada como suya', function () {
    $compartida = Reunion::factory()->for($this->proyecto)->visibleParaCliente()->create();

    $this->actingAs($this->cliente)
        ->get(route('reuniones.show', $compartida))
        ->assertOk();
});

it('no muestra al cliente las notas internas en el listado', function () {
    $tarea = Tarea::factory()->for($this->proyecto)->create();

    $interna = NotaTarea::factory()->for($tarea)->create(['contenido' => 'Margen real del periodo']);
    $compartida = NotaTarea::factory()->for($tarea)->visibleParaCliente()->create(['contenido' => 'Desplegado en pruebas']);

    $respuesta = $this->actingAs($this->cliente)->get(route('tareas.show', $tarea));

    $respuesta->assertOk();

    $contenidos = collect($respuesta->viewData('page')['props']['notas'])->pluck('contenido')->all();

    expect($contenidos)->toBe([$compartida->contenido])
        ->and($contenidos)->not->toContain($interna->contenido);
});

it('niega al cliente un respaldo .sql aunque tenga el id correcto', function () {
    Storage::fake('local');

    $media = $this->proyecto
        ->addMediaFromString('-- dump de la base')
        ->usingFileName('respaldo.sql')
        ->toMediaCollection('respaldos_bd', 'local');

    $this->actingAs($this->cliente)
        ->get(route('archivos.descargar', $media))
        ->assertForbidden();
});

it('niega el respaldo .sql incluso a un colaborador interno', function () {
    Storage::fake('local');

    $colaborador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($colaborador, $this->proyecto, RolProyecto::Colaborador);

    $media = $this->proyecto
        ->addMediaFromString('-- dump de la base')
        ->usingFileName('respaldo.sql')
        ->toMediaCollection('respaldos_bd', 'local');

    $this->actingAs($colaborador)
        ->get(route('archivos.descargar', $media))
        ->assertForbidden();
});

it('deja al líder descargar el respaldo por la ruta controlada', function () {
    Storage::fake('local');

    $media = $this->proyecto
        ->addMediaFromString('-- dump de la base')
        ->usingFileName('respaldo.sql')
        ->toMediaCollection('respaldos_bd', 'local');

    $this->actingAs($this->lider)
        ->get(route('archivos.descargar', $media))
        ->assertOk();
});

it('niega al cliente crear o editar cualquier tarea', function () {
    $tarea = Tarea::factory()->for($this->proyecto)->create();

    expect($this->cliente->can('create', [Tarea::class, $this->proyecto]))->toBeFalse()
        ->and($this->cliente->can('update', $tarea))->toBeFalse()
        ->and($this->cliente->can('cambiarEstado', $tarea))->toBeFalse();
});

it('niega también al observador escribir tareas', function () {
    $observador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($observador, $this->proyecto, RolProyecto::Observador);

    $tarea = Tarea::factory()->for($this->proyecto)->create();

    expect($observador->can('update', $tarea))->toBeFalse()
        ->and($observador->can('cambiarEstado', $tarea))->toBeFalse()
        ->and($observador->can('create', [Tarea::class, $this->proyecto]))->toBeFalse();
});

it('el colaborador sí escribe tareas y cambia su estado', function () {
    $colaborador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($colaborador, $this->proyecto, RolProyecto::Colaborador);

    $tarea = Tarea::factory()->for($this->proyecto)->create();

    expect($colaborador->can('create', [Tarea::class, $this->proyecto]))->toBeTrue()
        ->and($colaborador->can('update', $tarea))->toBeTrue()
        ->and($colaborador->can('cambiarEstado', $tarea))->toBeTrue();
});

it('el cliente sí ve los periodos: son el marco de su reporte', function () {
    Periodo::factory()->for($this->proyecto)->create();

    $this->actingAs($this->cliente)
        ->get(route('proyectos.periodos.index', $this->proyecto))
        ->assertOk();
});

it('un usuario del cliente en otro proyecto no arrastra su restricción', function () {
    $otro = Proyecto::factory()->create();

    // Misma persona: cliente en un proyecto, colaborador en otro.
    $persona = User::factory()->create(['activo' => true]);
    $persona->syncRoles([RolGlobal::Miembro->value]);
    asignarAProyecto($persona, $this->proyecto, RolProyecto::Cliente);
    asignarAProyecto($persona, $otro, RolProyecto::Colaborador);

    $internaAqui = Reunion::factory()->for($this->proyecto)->create();
    $internaAlla = Reunion::factory()->for($otro)->create();

    $visibles = Reunion::query()->visiblesPara($persona->fresh())->pluck('id')->all();

    expect($visibles)->toContain($internaAlla->id)
        ->and($visibles)->not->toContain($internaAqui->id);
});

it('el observador ve los reportes emitidos pero no puede emitir uno nuevo', function () {
    $observador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($observador, $this->proyecto, RolProyecto::Observador);
    $periodo = Periodo::factory()->for($this->proyecto)->create();

    // Generar deja una fila en `reportes`: cuenta como escritura.
    expect($observador->can('viewAny', [Reporte::class, $this->proyecto]))->toBeTrue()
        ->and($observador->can('create', [Reporte::class, $this->proyecto]))->toBeFalse();

    $this->actingAs($observador)
        ->get(route('proyectos.reportes.index', $this->proyecto))
        ->assertOk();

    $this->actingAs($observador)
        ->post(route('proyectos.reportes.store', $this->proyecto), [
            'periodo_id' => $periodo->id,
            'tipo' => 'completo',
        ])
        ->assertForbidden();

    expect(Reporte::query()->count())->toBe(0);
});

it('el líder pasa la guarda de reportes y genera uno, ya no el 501 de guarda de la fase 04', function () {
    $periodo = Periodo::factory()->for($this->proyecto)->create();

    $this->actingAs($this->lider)
        ->post(route('proyectos.reportes.store', $this->proyecto), [
            'periodo_id' => $periodo->id,
            'tipo' => 'cliente',
        ])
        ->assertRedirect(route('proyectos.reportes.index', $this->proyecto));

    expect(Reporte::query()->count())->toBe(1);
});
