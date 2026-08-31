<?php

declare(strict_types=1);

use App\Enums\EstadoTarea;
use App\Exceptions\TransicionNoPermitida;
use App\Models\Tarea;

it('rechaza pasar a en_espera sin motivo_espera', function () {
    $tarea = Tarea::factory()->create();

    expect(fn () => $tarea->update(['estado' => EstadoTarea::EnEspera]))
        ->toThrow(TransicionNoPermitida::class);

    expect($tarea->fresh()->estado)->toBe(EstadoTarea::Pendiente);
});

it('rechaza pasar a cancelada sin motivo_cancelacion', function () {
    $tarea = Tarea::factory()->create();

    expect(fn () => $tarea->update(['estado' => EstadoTarea::Cancelada]))
        ->toThrow(TransicionNoPermitida::class);

    expect($tarea->fresh()->estado)->toBe(EstadoTarea::Pendiente);
});

it('no acepta motivo_espera como justificación de una cancelación', function () {
    $tarea = Tarea::factory()->create(['motivo_espera' => 'Falta información.']);

    expect(fn () => $tarea->update(['estado' => EstadoTarea::Cancelada]))
        ->toThrow(TransicionNoPermitida::class);
});

it('limpia motivo_espera al salir de en_espera pero lo conserva en el historial', function () {
    $tarea = Tarea::factory()->create();

    $tarea->update([
        'estado' => EstadoTarea::EnEspera,
        'motivo_espera' => 'Falta el ejemplo del archivo a importar.',
    ]);

    $tarea->update(['estado' => EstadoTarea::EnProceso]);

    expect($tarea->fresh()->motivo_espera)->toBeNull();

    $registro = $tarea->historial()->where('estado_nuevo', EstadoTarea::EnEspera)->sole();

    expect($registro->comentario)->toBe('Falta el ejemplo del archivo a importar.');
});

it('rechaza crear una tarea directamente en en_espera sin motivo', function () {
    expect(fn () => Tarea::factory()->create(['estado' => EstadoTarea::EnEspera]))
        ->toThrow(TransicionNoPermitida::class);
});
