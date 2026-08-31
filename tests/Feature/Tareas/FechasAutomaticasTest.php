<?php

declare(strict_types=1);

use App\Enums\EstadoTarea;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Services\CambiarEstadoTarea;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);

    $this->cambiar = app(CambiarEstadoTarea::class);
});

it('fija fecha_inicio la primera vez que entra en en_proceso', function () {
    Carbon::setTestNow('2026-03-10 09:00:00');

    $tarea = Tarea::factory()->for($this->proyecto)->create(['fecha_inicio' => null]);

    ($this->cambiar)($tarea, EstadoTarea::EnProceso);

    expect($tarea->fresh()->fecha_inicio?->toDateString())->toBe('2026-03-10');
});

it('no sobrescribe fecha_inicio en una segunda entrada a en_proceso', function () {
    Carbon::setTestNow('2026-03-10 09:00:00');

    $tarea = Tarea::factory()->for($this->proyecto)->create(['fecha_inicio' => null]);

    ($this->cambiar)($tarea, EstadoTarea::EnProceso);

    // Se bloquea, se desbloquea y se retoma tres semanas después.
    Carbon::setTestNow('2026-03-31 09:00:00');

    ($this->cambiar)($tarea, EstadoTarea::EnEspera, ['motivo_espera' => 'Falta el acceso.']);
    ($this->cambiar)($tarea, EstadoTarea::EnProceso);

    // La fecha de inicio real es la primera: volver no es empezar de nuevo.
    expect($tarea->fresh()->fecha_inicio?->toDateString())->toBe('2026-03-10');
});

it('actualiza fecha_fin al volver a terminada tras una revisión fallida', function () {
    Carbon::setTestNow('2026-03-10 09:00:00');

    $tarea = Tarea::factory()->for($this->proyecto)->create(['fecha_inicio' => null, 'fecha_fin' => null]);

    ($this->cambiar)($tarea, EstadoTarea::EnProceso);
    ($this->cambiar)($tarea, EstadoTarea::Terminada);

    expect($tarea->fresh()->fecha_fin?->toDateString())->toBe('2026-03-10');

    // Falló la revisión antes de desplegar y hubo que retocar.
    Carbon::setTestNow('2026-03-18 16:00:00');

    ($this->cambiar)($tarea, EstadoTarea::EnProceso);
    ($this->cambiar)($tarea, EstadoTarea::Terminada);

    // La fecha buena es la del segundo intento: es la que ve el cliente.
    expect($tarea->fresh()->fecha_fin?->toDateString())->toBe('2026-03-18')
        // Y el inicio sigue siendo el primero.
        ->and($tarea->fresh()->fecha_inicio?->toDateString())->toBe('2026-03-10');
});

it('no toca las fechas al entregar ni al cancelar', function () {
    Carbon::setTestNow('2026-03-10 09:00:00');

    $tarea = Tarea::factory()->for($this->proyecto)->create(['fecha_inicio' => null, 'fecha_fin' => null]);

    ($this->cambiar)($tarea, EstadoTarea::EnProceso);
    ($this->cambiar)($tarea, EstadoTarea::Terminada);

    Carbon::setTestNow('2026-04-01 09:00:00');

    ($this->cambiar)($tarea, EstadoTarea::Entregada);

    expect($tarea->fresh()->fecha_inicio?->toDateString())->toBe('2026-03-10')
        ->and($tarea->fresh()->fecha_fin?->toDateString())->toBe('2026-03-10');
});

it('rellena las fechas también por HTTP, no solo llamando al servicio', function () {
    Carbon::setTestNow('2026-05-04 11:00:00');

    $tarea = Tarea::factory()->for($this->proyecto)->create(['fecha_inicio' => null]);

    $this->actingAs($this->lider)
        ->put(route('tareas.estado.update', $tarea), ['estado' => 'en_proceso'])
        ->assertRedirect();

    expect($tarea->fresh()->fecha_inicio?->toDateString())->toBe('2026-05-04');
});

it('devuelve las fechas ya resueltas en el resultado del servicio', function () {
    Carbon::setTestNow('2026-05-04 11:00:00');

    $tarea = Tarea::factory()->for($this->proyecto)->create(['fecha_inicio' => null]);

    $resultado = ($this->cambiar)($tarea, EstadoTarea::EnProceso);

    expect($resultado['estado'])->toBe('en_proceso')
        ->and($resultado['fecha_inicio'])->toBe('2026-05-04')
        ->and(array_column($resultado['transiciones'], 'valor'))
        ->toBe(['en_espera', 'terminada', 'cancelada']);
});

it('no cambia nada cuando el destino es el mismo estado', function () {
    Carbon::setTestNow('2026-05-04 11:00:00');

    $tarea = Tarea::factory()->for($this->proyecto)->create(['fecha_inicio' => null]);

    $resultado = ($this->cambiar)($tarea, EstadoTarea::Pendiente);

    expect($resultado['estado'])->toBe('pendiente')
        ->and($tarea->fresh()->fecha_inicio)->toBeNull()
        ->and($tarea->historial()->count())->toBe(0);
});
