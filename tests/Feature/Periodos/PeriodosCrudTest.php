<?php

declare(strict_types=1);

use App\Enums\EstadoPeriodo;
use App\Enums\EstadoTarea;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);

    $this->colaborador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->colaborador, $this->proyecto, RolProyecto::Colaborador);

    $this->cliente = usuarioCon(RolGlobal::Invitado);
    asignarAProyecto($this->cliente, $this->proyecto, RolProyecto::Cliente);
});

it('el líder abre el primer periodo del proyecto con consecutivo 1', function () {
    $this->actingAs($this->lider)
        ->post(route('proyectos.periodos.store', $this->proyecto), [
            'fecha_inicio' => '2026-01-01',
            'notas' => 'Arranque del proyecto.',
        ])
        ->assertRedirect();

    $periodo = Periodo::query()->sole();

    expect($periodo->consecutivo)->toBe(1)
        ->and($periodo->estado)->toBe(EstadoPeriodo::Abierto)
        ->and($periodo->fecha_fin)->toBeNull()
        ->and($periodo->notas)->toBe('Arranque del proyecto.');
});

it('un colaborador no puede abrir un periodo', function () {
    $this->actingAs($this->colaborador)
        ->post(route('proyectos.periodos.store', $this->proyecto), [
            'fecha_inicio' => '2026-01-01',
        ])
        ->assertForbidden();

    expect(Periodo::query()->count())->toBe(0);
});

it('rechaza abrir un segundo periodo con un 422 legible, no una excepción de base de datos', function () {
    Periodo::factory()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 1]);

    $this->actingAs($this->lider)
        ->post(route('proyectos.periodos.store', $this->proyecto), [
            'fecha_inicio' => '2026-02-01',
        ])
        ->assertInvalid(['fecha_inicio']);

    expect(Periodo::query()->count())->toBe(1);
});

it('el índice lista los periodos del más reciente al más antiguo, con el pago si lo tienen', function () {
    $cerrado = Periodo::factory()->cerrado()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 1]);
    Pago::factory()->for($cerrado)->create(['monto_usd' => '1500.00']);
    Periodo::factory()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 2]);

    $this->actingAs($this->lider)
        ->get(route('proyectos.periodos.index', $this->proyecto))
        ->assertOk()
        ->assertInertia(fn (Assert $pagina) => $pagina
            ->component('periodos/Index')
            ->where('periodos.0.consecutivo', 2)
            ->where('periodos.1.consecutivo', 1)
            ->where('periodos.1.pago.monto_usd', '1500.00')
            ->where('puede.gestionar', true));
});

it('el detalle desglosa las tareas por estado y marca las arrastradas', function () {
    $periodo = Periodo::factory()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 1]);
    $origen = Periodo::factory()->cerrado()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 0]);

    Tarea::factory()->for($this->proyecto)->create(['periodo_id' => $periodo->id, 'estado' => EstadoTarea::Pendiente]);
    Tarea::factory()->for($this->proyecto)->create([
        'periodo_id' => $periodo->id,
        'periodo_origen_id' => $origen->id,
        'estado' => EstadoTarea::Pendiente,
    ]);

    $this->actingAs($this->lider)
        ->get(route('periodos.show', $periodo))
        ->assertOk()
        ->assertInertia(fn (Assert $pagina) => $pagina
            ->component('periodos/Show')
            ->where('periodo.consecutivo', 1)
            ->where('puede.cerrar', true)
            ->has('tareasPorEstado', 6));
});

it('el cliente ve el periodo pero no el pago', function () {
    $periodo = Periodo::factory()->cerrado()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 1]);
    Pago::factory()->for($periodo)->create();

    $this->actingAs($this->cliente)
        ->get(route('periodos.show', $periodo))
        ->assertOk()
        ->assertInertia(fn (Assert $pagina) => $pagina
            ->where('puede.verPagos', false)
            ->where('pago', null));
});

it('mientras está abierto se editan notas y la fecha de fin prevista', function () {
    $periodo = Periodo::factory()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 1]);

    $this->actingAs($this->lider)
        ->put(route('periodos.update', $periodo), [
            'notas' => 'Se revisa cada viernes.',
            'fecha_fin' => '2026-03-01',
        ])
        ->assertRedirect();

    $periodo->refresh();

    expect($periodo->notas)->toBe('Se revisa cada viernes.')
        ->and($periodo->fecha_fin->toDateString())->toBe('2026-03-01')
        // Sigue abierto: fijar una fecha de fin prevista no es cerrar.
        ->and($periodo->estado)->toBe(EstadoPeriodo::Abierto);
});

it('un periodo cerrado rechaza cambios de fecha', function () {
    $periodo = Periodo::factory()->cerrado()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 1]);
    $fechaFinOriginal = $periodo->fecha_fin;

    $this->actingAs($this->lider)
        ->put(route('periodos.update', $periodo), [
            'notas' => 'nota nueva',
            'fecha_inicio' => '2020-01-01',
        ])
        ->assertInvalid(['fecha_inicio']);

    expect($periodo->fresh()->fecha_fin->toDateString())->toBe($fechaFinOriginal->toDateString());
});

it('un periodo cerrado no admite tareas nuevas', function () {
    $cerrado = Periodo::factory()->cerrado()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 1]);

    $this->actingAs($this->lider)
        ->post(route('proyectos.tareas.store', $this->proyecto), [
            'nombre' => 'Tarea que no debería caber',
            'tipo' => 'desarrollo',
            'prioridad' => 'media',
            'periodo_id' => $cerrado->id,
        ])
        ->assertInvalid(['periodo_id']);

    expect(Tarea::query()->count())->toBe(0);
});

it('editar una tarea sin tocar su periodo no falla aunque ese periodo ya esté cerrado', function () {
    $cerrado = Periodo::factory()->cerrado()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 1]);
    $tarea = Tarea::factory()->for($this->proyecto)->create(['periodo_id' => $cerrado->id]);

    $this->actingAs($this->lider)
        ->put(route('tareas.update', $tarea), [
            'nombre' => $tarea->nombre,
            'prioridad' => $tarea->prioridad->value,
            'periodo_id' => $cerrado->id,
        ])
        ->assertRedirect();

    expect($tarea->fresh()->periodo_id)->toBe($cerrado->id);
});
