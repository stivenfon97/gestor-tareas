<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\Modulo;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Tarea;
use Illuminate\Database\QueryException;

it('borrar un proyecto arrastra sus módulos', function () {
    $proyecto = Proyecto::factory()->create();
    $modulo = Modulo::factory()->for($proyecto)->create();

    $proyecto->forceDelete();

    expect(Modulo::query()->whereKey($modulo->id)->exists())->toBeFalse();
});

it('borrar un módulo deja las tareas sin módulo, no las borra', function () {
    $proyecto = Proyecto::factory()->create();
    $modulo = Modulo::factory()->for($proyecto)->create();
    $tarea = Tarea::factory()->for($proyecto)->create(['modulo_id' => $modulo->id]);

    $modulo->delete();

    $tarea = $tarea->fresh();

    expect($tarea)->not->toBeNull()
        ->and($tarea->modulo_id)->toBeNull();
});

it('borrar un periodo deja las tareas sin periodo', function () {
    $proyecto = Proyecto::factory()->create();
    $periodo = Periodo::factory()->for($proyecto)->create();
    $tarea = Tarea::factory()->for($proyecto)->create([
        'periodo_id' => $periodo->id,
        'periodo_origen_id' => $periodo->id,
    ]);

    $periodo->delete();

    $tarea = $tarea->fresh();

    expect($tarea->periodo_id)->toBeNull()
        ->and($tarea->periodo_origen_id)->toBeNull();
});

it('impide borrar un cliente que todavía tiene proyectos', function () {
    $cliente = Cliente::factory()->create();
    Proyecto::factory()->for($cliente)->create();

    expect(fn () => $cliente->delete())->toThrow(QueryException::class);

    expect(Cliente::query()->whereKey($cliente->id)->exists())->toBeTrue();
});

it('el borrado de un proyecto es suave por defecto y conserva sus tareas', function () {
    $proyecto = Proyecto::factory()->create();
    $tarea = Tarea::factory()->for($proyecto)->create();

    $proyecto->delete();

    expect(Proyecto::query()->whereKey($proyecto->id)->exists())->toBeFalse()
        ->and(Proyecto::withTrashed()->whereKey($proyecto->id)->exists())->toBeTrue()
        ->and(Tarea::query()->whereKey($tarea->id)->exists())->toBeTrue();
});
