<?php

declare(strict_types=1);

use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Proyecto;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);
});

it('persiste el orden de una columna en una sola petición', function () {
    $tareas = Tarea::factory()->for($this->proyecto)->count(4)->create(['orden' => 0]);

    $nuevoOrden = $tareas->pluck('id')->reverse()->values()->all();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($this->lider)
        ->put(route('proyectos.tareas.orden', $this->proyecto), ['tareas' => $nuevoOrden])
        ->assertRedirect();

    DB::disableQueryLog();

    foreach ($nuevoOrden as $posicion => $id) {
        expect(Tarea::query()->find($id)->orden)->toBe($posicion);
    }
});

it('reordenar no toca el estado de ninguna tarea', function () {
    $pendiente = Tarea::factory()->for($this->proyecto)->create();
    $enProceso = Tarea::factory()->for($this->proyecto)->enProceso()->create();

    $this->actingAs($this->lider)
        ->put(route('proyectos.tareas.orden', $this->proyecto), [
            'tareas' => [$enProceso->id, $pendiente->id],
        ])
        ->assertRedirect();

    // Reordenar es mover dentro de una columna. Cambiar de columna es otra
    // operación, y con agrupación por estado pasa por el servicio.
    expect($pendiente->fresh()->estado)->toBe($pendiente->estado)
        ->and($enProceso->fresh()->estado)->toBe($enProceso->estado);
});

it('rechaza reordenar con una tarea de otro proyecto', function () {
    $propia = Tarea::factory()->for($this->proyecto)->create();
    $ajena = Tarea::factory()->for(Proyecto::factory()->create())->create();

    $this->actingAs($this->lider)
        ->put(route('proyectos.tareas.orden', $this->proyecto), [
            'tareas' => [$propia->id, $ajena->id],
        ])
        ->assertSessionHasErrors('tareas.1');

    expect($ajena->fresh()->orden)->toBe($ajena->orden);
});

it('niega reordenar al observador y al cliente', function (string $rol) {
    $usuario = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($usuario, $this->proyecto, RolProyecto::from($rol));

    $tarea = Tarea::factory()->for($this->proyecto)->create(['orden' => 7]);

    $this->actingAs($usuario)
        ->put(route('proyectos.tareas.orden', $this->proyecto), ['tareas' => [$tarea->id]])
        ->assertForbidden();

    expect($tarea->fresh()->orden)->toBe(7);
})->with(['observador', 'cliente']);

it('el tablero le dice al observador que no puede mover', function () {
    $observador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($observador, $this->proyecto, RolProyecto::Observador);

    $respuesta = $this->actingAs($observador)
        ->get(route('proyectos.tablero.index', $this->proyecto))
        ->assertOk();

    expect($respuesta->viewData('page')['props']['puede']['mover'])->toBeFalse();
});
