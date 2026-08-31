<?php

declare(strict_types=1);

use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Enums\RolProyecto;
use App\Enums\TipoTarea;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Models\User;

it('precarga la prioridad según el tipo cuando no se indica', function () {
    $proyecto = Proyecto::factory()->create();

    $correccion = Tarea::factory()->for($proyecto)->create([
        'tipo' => TipoTarea::Correccion,
        'prioridad' => null,
    ]);

    $desarrollo = Tarea::factory()->for($proyecto)->create([
        'tipo' => TipoTarea::Desarrollo,
        'prioridad' => null,
    ]);

    expect($correccion->prioridad)->toBe(PrioridadTarea::Alta)
        ->and($desarrollo->prioridad)->toBe(PrioridadTarea::Media);
});

it('la precarga es un valor por defecto y no una restricción', function () {
    $tarea = Tarea::factory()->create([
        'tipo' => TipoTarea::Correccion,
        'prioridad' => PrioridadTarea::Baja,
    ]);

    expect($tarea->prioridad)->toBe(PrioridadTarea::Baja);
});

it('nace pendiente cuando no se indica estado', function () {
    expect(Tarea::factory()->create()->estado)->toBe(EstadoTarea::Pendiente);
});

it('el scope activas excluye los estados terminales', function () {
    $proyecto = Proyecto::factory()->create();

    $viva = Tarea::factory()->for($proyecto)->create();
    $entregada = Tarea::factory()->for($proyecto)->create();
    $entregada->update(['estado' => EstadoTarea::EnProceso]);
    $entregada->update(['estado' => EstadoTarea::Terminada]);
    $entregada->update(['estado' => EstadoTarea::Entregada]);

    expect($proyecto->tareas()->activas()->pluck('id')->all())->toBe([$viva->id]);
});

it('el scope arrastradas devuelve solo las que conservan periodo de origen', function () {
    $proyecto = Proyecto::factory()->create();
    $origen = Periodo::factory()->for($proyecto)->create();

    Tarea::factory()->for($proyecto)->create();
    $arrastrada = Tarea::factory()->for($proyecto)->arrastrada($origen)->create();

    expect($proyecto->tareas()->arrastradas()->pluck('id')->all())->toBe([$arrastrada->id])
        ->and($arrastrada->estaArrastrada())->toBeTrue();
});

it('filtra por estado y por proyecto', function () {
    $uno = Proyecto::factory()->create();
    $otro = Proyecto::factory()->create();

    $enProceso = Tarea::factory()->for($uno)->create();
    $enProceso->update(['estado' => EstadoTarea::EnProceso]);

    Tarea::factory()->for($uno)->create();
    Tarea::factory()->for($otro)->create();

    expect(Tarea::query()->delProyecto($uno)->porEstado(EstadoTarea::EnProceso)->pluck('id')->all())
        ->toBe([$enProceso->id]);
});

it('resuelve el rol de un usuario desde el pivote del proyecto', function () {
    $proyecto = Proyecto::factory()->create();
    $usuario = User::factory()->create();

    $proyecto->usuarios()->attach($usuario, ['rol' => RolProyecto::Lider->value]);

    $participante = $proyecto->usuarios()->sole();

    expect($participante->pivot->rol)->toBe(RolProyecto::Lider);
});
