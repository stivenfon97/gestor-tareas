<?php

declare(strict_types=1);

use App\Enums\EstadoTarea;
use App\Exceptions\TransicionNoPermitida;
use App\Models\Tarea;

/**
 * Devuelve una tarea ya situada en el estado indicado, recorriendo la matriz
 * paso a paso para no falsear el punto de partida.
 */
function tareaEn(EstadoTarea $estado): Tarea
{
    $tarea = Tarea::factory()->create();

    $camino = match ($estado) {
        EstadoTarea::Pendiente => [],
        EstadoTarea::EnProceso => [EstadoTarea::EnProceso],
        EstadoTarea::EnEspera => [EstadoTarea::EnEspera],
        EstadoTarea::Terminada => [EstadoTarea::EnProceso, EstadoTarea::Terminada],
        EstadoTarea::Entregada => [EstadoTarea::EnProceso, EstadoTarea::Terminada, EstadoTarea::Entregada],
        EstadoTarea::Cancelada => [EstadoTarea::Cancelada],
    };

    foreach ($camino as $paso) {
        $tarea->update(atributosDeTransicion($paso));
    }

    return $tarea->fresh();
}

/**
 * @return array<string, mixed>
 */
function atributosDeTransicion(EstadoTarea $destino): array
{
    return match ($destino) {
        EstadoTarea::EnEspera => ['estado' => $destino, 'motivo_espera' => 'Falta el ejemplo del archivo a importar.'],
        EstadoTarea::Cancelada => ['estado' => $destino, 'motivo_cancelacion' => 'El cliente desistió.'],
        default => ['estado' => $destino],
    };
}

it('acepta las once transiciones válidas de la matriz', function () {
    $aceptadas = 0;

    foreach (EstadoTarea::cases() as $origen) {
        foreach ($origen->transicionesPermitidas() as $destino) {
            $tarea = tareaEn($origen);

            $tarea->update(atributosDeTransicion($destino));

            expect($tarea->fresh()->estado)->toBe($destino);
            $aceptadas++;
        }
    }

    expect($aceptadas)->toBe(11);
});

it('rechaza toda transición que la matriz no permite', function () {
    $rechazadas = 0;

    foreach (EstadoTarea::cases() as $origen) {
        foreach (EstadoTarea::cases() as $destino) {
            if ($origen === $destino || $origen->puedeTransicionarA($destino)) {
                continue;
            }

            $tarea = tareaEn($origen);

            expect(fn () => $tarea->update(atributosDeTransicion($destino)))
                ->toThrow(TransicionNoPermitida::class);

            expect($tarea->fresh()->estado)->toBe($origen);
            $rechazadas++;
        }
    }

    // 6 estados × 5 destinos distintos = 30 pares, menos las 11 permitidas.
    expect($rechazadas)->toBe(19);
});

it('no deja salir de entregada hacia ningún estado', function () {
    $tarea = tareaEn(EstadoTarea::Entregada);

    foreach (EstadoTarea::cases() as $destino) {
        if ($destino === EstadoTarea::Entregada) {
            continue;
        }

        expect(fn () => $tarea->update(atributosDeTransicion($destino)))
            ->toThrow(TransicionNoPermitida::class);
    }

    expect($tarea->fresh()->estado)->toBe(EstadoTarea::Entregada);
});

it('no deja salir de cancelada hacia ningún estado', function () {
    $tarea = tareaEn(EstadoTarea::Cancelada);

    foreach (EstadoTarea::cases() as $destino) {
        if ($destino === EstadoTarea::Cancelada) {
            continue;
        }

        expect(fn () => $tarea->update(atributosDeTransicion($destino)))
            ->toThrow(TransicionNoPermitida::class);
    }

    expect($tarea->fresh()->estado)->toBe(EstadoTarea::Cancelada);
});

it('rechaza pasar de terminada a en_espera: no hay espera de despliegue', function () {
    $tarea = tareaEn(EstadoTarea::Terminada);

    expect(fn () => $tarea->update([
        'estado' => EstadoTarea::EnEspera,
        'motivo_espera' => 'Esperando ventana de despliegue.',
    ]))->toThrow(TransicionNoPermitida::class);
});

it('guardar la tarea sin tocar el estado no dispara la validación', function () {
    $tarea = tareaEn(EstadoTarea::Entregada);

    $tarea->update(['nombre' => 'Título corregido tras la entrega']);

    expect($tarea->fresh()->nombre)->toBe('Título corregido tras la entrega')
        ->and($tarea->fresh()->estado)->toBe(EstadoTarea::Entregada);
});
