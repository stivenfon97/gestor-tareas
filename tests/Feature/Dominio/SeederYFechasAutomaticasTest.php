<?php

declare(strict_types=1);

use App\Enums\EstadoTarea;
use App\Models\Tarea;

/**
 * Impide que vuelva el fallo de la fase 09 (0.1): `DesarrolloSeeder` cambiaba
 * `estado` escribiendo sobre el modelo directamente en lugar de pasar por
 * `CambiarEstadoTarea`, así que el observer validaba la transición y escribía
 * el historial —de ahí que nadie lo notara— pero `fecha_inicio` y `fecha_fin`,
 * que solo rellena el servicio, nunca se aplicaban. Las 73 tareas sembradas
 * caían en "Sin fecha de fin" en el calendario, 42 de ellas ya `entregada`.
 *
 * El seeder es lento (siembra cuatro proyectos completos), así que esto va en
 * su propio archivo y no se repite en cada corrida de otra suite.
 */
it('el seeder de desarrollo deja las fechas automáticas puestas', function () {
    $this->seed();

    // Terminal sin fecha_fin: el estado dice que el trabajo se acabó y la
    // columna que debería decir cuándo está vacía.
    expect(
        Tarea::query()
            ->whereIn('estado', [EstadoTarea::Terminada, EstadoTarea::Entregada])
            ->whereNull('fecha_fin')
            ->exists(),
    )->toBeFalse();

    // En este seeder, `llevarA()` lleva `en_espera` siempre pasando por
    // `en_proceso` (nunca directo desde `pendiente`), así que todo estado
    // que no sea `pendiente` ni `cancelada` implica haber pasado por ahí.
    expect(
        Tarea::query()
            ->whereIn('estado', [
                EstadoTarea::EnProceso,
                EstadoTarea::EnEspera,
                EstadoTarea::Terminada,
                EstadoTarea::Entregada,
            ])
            ->whereNull('fecha_inicio')
            ->exists(),
    )->toBeFalse();
});
