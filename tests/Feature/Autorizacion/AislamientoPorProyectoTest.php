<?php

declare(strict_types=1);

use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Modulo;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();
    $this->ajeno = usuarioCon(RolGlobal::Miembro);
});

it('niega el proyecto a quien no tiene fila en el pivote', function () {
    $this->actingAs($this->ajeno)
        ->get(route('proyectos.show', $this->proyecto))
        ->assertForbidden();
});

it('niega las tareas del proyecto a quien no participa', function () {
    $this->actingAs($this->ajeno)
        ->get(route('proyectos.tareas.index', $this->proyecto))
        ->assertForbidden();
});

it('niega los módulos del proyecto a quien no participa', function () {
    $this->actingAs($this->ajeno)
        ->get(route('proyectos.modulos.index', $this->proyecto))
        ->assertForbidden();
});

it('niega los periodos del proyecto a quien no participa', function () {
    $this->actingAs($this->ajeno)
        ->get(route('proyectos.periodos.index', $this->proyecto))
        ->assertForbidden();
});

it('niega una tarea concreta a quien no participa en su proyecto', function () {
    $tarea = Tarea::factory()->for($this->proyecto)->create();

    $this->actingAs($this->ajeno)
        ->get(route('tareas.show', $tarea))
        ->assertForbidden();
});

it('no deja ni una sola tarea del proyecto B en el listado del proyecto A', function () {
    $otro = Proyecto::factory()->create();

    $miembro = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($miembro, $this->proyecto, RolProyecto::Colaborador);

    $mias = Tarea::factory()->count(3)->for($this->proyecto)->create();
    Tarea::factory()->count(4)->for($otro)->create();

    $visibles = Tarea::query()->visiblesPara($miembro)->pluck('id')->all();

    sort($visibles);

    expect($visibles)->toBe($mias->pluck('id')->sort()->values()->all());
});

it('el listado de proyectos solo muestra los del usuario', function () {
    $otro = Proyecto::factory()->create();

    $miembro = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($miembro, $this->proyecto, RolProyecto::Colaborador);

    $respuesta = $this->actingAs($miembro)->get(route('proyectos.index'));

    $respuesta->assertOk();

    // El listado se pagina desde la fase 05: los proyectos viajan en `data`.
    $ids = collect($respuesta->viewData('page')['props']['proyectos']['data'])->pluck('id')->all();

    expect($ids)->toBe([$this->proyecto->id])
        ->and($ids)->not->toContain($otro->id);
});

it('deja pasar a quien sí participa', function () {
    $miembro = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($miembro, $this->proyecto, RolProyecto::Observador);

    Modulo::factory()->for($this->proyecto)->create();
    Periodo::factory()->for($this->proyecto)->create();

    $this->actingAs($miembro)->get(route('proyectos.show', $this->proyecto))->assertOk();
    $this->actingAs($miembro)->get(route('proyectos.modulos.index', $this->proyecto))->assertOk();
    $this->actingAs($miembro)->get(route('proyectos.periodos.index', $this->proyecto))->assertOk();
});

it('el administrador entra sin fila en el pivote', function () {
    $administrador = usuarioCon(RolGlobal::Administrador);

    $this->actingAs($administrador)
        ->get(route('proyectos.show', $this->proyecto))
        ->assertOk();
});
