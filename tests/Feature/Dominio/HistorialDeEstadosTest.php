<?php

declare(strict_types=1);

use App\Enums\EstadoTarea;
use App\Models\Tarea;
use App\Models\User;

it('registra exactamente un movimiento por cambio de estado', function () {
    $tarea = Tarea::factory()->create();

    $tarea->update(['estado' => EstadoTarea::EnProceso]);

    expect($tarea->historial()->count())->toBe(1);

    $registro = $tarea->historial()->sole();

    expect($registro->estado_anterior)->toBe(EstadoTarea::Pendiente)
        ->and($registro->estado_nuevo)->toBe(EstadoTarea::EnProceso);
});

it('no registra nada al guardar la tarea sin cambiar el estado', function () {
    $tarea = Tarea::factory()->create();

    $tarea->update(['nombre' => 'Otro título', 'prioridad' => 'critica']);

    expect($tarea->historial()->count())->toBe(0);
});

it('registra al autor autenticado del cambio', function () {
    $usuario = User::factory()->create();
    $tarea = Tarea::factory()->create();

    $this->actingAs($usuario);
    $tarea->update(['estado' => EstadoTarea::EnProceso]);

    expect($tarea->historial()->sole()->usuario_id)->toBe($usuario->id);
});

it('deja el autor en nulo cuando el cambio no viene de una sesión', function () {
    $tarea = Tarea::factory()->create();

    $tarea->update(['estado' => EstadoTarea::EnProceso]);

    expect($tarea->historial()->sole()->usuario_id)->toBeNull();
});

it('conserva el historial completo del recorrido hasta entregada', function () {
    $tarea = Tarea::factory()->create();

    $tarea->update(['estado' => EstadoTarea::EnProceso]);
    $tarea->update(['estado' => EstadoTarea::Terminada]);
    $tarea->update(['estado' => EstadoTarea::Entregada]);

    expect($tarea->historial()->pluck('estado_nuevo')->all())->toBe([
        EstadoTarea::EnProceso,
        EstadoTarea::Terminada,
        EstadoTarea::Entregada,
    ]);
});

it('no escribe historial cuando la transición se rechaza', function () {
    $tarea = Tarea::factory()->create();

    try {
        $tarea->update(['estado' => EstadoTarea::Entregada]);
    } catch (Throwable) {
        // La transición prohibida se rechaza; lo que se comprueba es el efecto.
    }

    expect($tarea->historial()->count())->toBe(0);
});
