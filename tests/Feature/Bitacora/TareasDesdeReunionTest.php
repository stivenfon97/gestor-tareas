<?php

declare(strict_types=1);

use App\Enums\EstadoPeriodo;
use App\Enums\EstadoTarea;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Reunion;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;

/**
 * "Sales de una reunión con cinco acuerdos y los conviertes en tareas sin
 * cambiar de pantalla": la función que más valor tiene de la bitácora.
 * Reutiliza `ParsearTareaRapida` y `CrearTareasEnLote` de la fase 06.
 */
beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();
    $this->abierto = Periodo::factory()->for($this->proyecto)->create(['estado' => EstadoPeriodo::Abierto]);

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);

    $this->reunion = Reunion::factory()->for($this->proyecto)->create();
});

it('vincula tareas existentes del mismo proyecto a la reunión', function () {
    $tarea = Tarea::factory()->for($this->proyecto)->create();
    $deOtroProyecto = Tarea::factory()->create();

    $this->actingAs($this->lider)
        ->post(route('reuniones.tareas.vincular', $this->reunion), ['tarea_ids' => [$tarea->id]])
        ->assertRedirect();

    expect($this->reunion->tareas()->pluck('tareas.id')->all())->toBe([$tarea->id]);

    // Una tarea de otro proyecto ni siquiera pasa la validación.
    $this->actingAs($this->lider)
        ->post(route('reuniones.tareas.vincular', $this->reunion), ['tarea_ids' => [$deOtroProyecto->id]])
        ->assertInvalid(['tarea_ids.0']);
});

it('desvincula una tarea sin borrarla', function () {
    $tarea = Tarea::factory()->for($this->proyecto)->create();
    $this->reunion->tareas()->attach($tarea->id);

    $this->actingAs($this->lider)
        ->delete(route('reuniones.tareas.desvincular', [$this->reunion, $tarea]))
        ->assertRedirect();

    expect($this->reunion->tareas()->count())->toBe(0)
        ->and(Tarea::query()->find($tarea->id))->not->toBeNull();
});

it('crea tareas por captura rápida desde la reunión, vinculadas y en el periodo abierto', function () {
    $texto = "Corregir el cálculo de impuestos | pendiente\nDocumentar el flujo de aprobación";

    $this->actingAs($this->lider)
        ->post(route('reuniones.tareas.rapida.previsualizar', $this->reunion), ['texto' => $texto])
        ->assertRedirect()
        ->assertInertiaFlash('previsualizacion');

    // La previsualización no escribe nada.
    expect(Tarea::query()->count())->toBe(0);

    $envio = $this->actingAs($this->lider)->post(
        route('reuniones.tareas.rapida.crear', $this->reunion),
        [
            'filas' => [
                ['nombre' => 'Corregir el cálculo de impuestos', 'estado' => 'pendiente', 'tipo' => 'correccion', 'descripcion' => null, 'prioridad' => null, 'modulo_id' => null, 'modulo_nuevo' => null, 'motivo_espera' => null, 'motivo_cancelacion' => null],
                ['nombre' => 'Documentar el flujo de aprobación', 'estado' => 'pendiente', 'tipo' => 'desarrollo', 'descripcion' => null, 'prioridad' => null, 'modulo_id' => null, 'modulo_nuevo' => null, 'motivo_espera' => null, 'motivo_cancelacion' => null],
            ],
        ],
    );

    $envio->assertRedirect();

    expect(Tarea::query()->count())->toBe(2)
        ->and($this->reunion->fresh()->tareas)->toHaveCount(2);

    foreach (Tarea::query()->get() as $tarea) {
        expect($tarea->periodo_id)->toBe($this->abierto->id)
            ->and($tarea->estado)->toBe(EstadoTarea::Pendiente);
    }
});

it('exige el motivo cuando una fila de captura rápida desde la reunión llega en en_espera', function () {
    $this->actingAs($this->lider)
        ->post(route('reuniones.tareas.rapida.crear', $this->reunion), [
            'filas' => [[
                'nombre' => 'Tarea bloqueada', 'estado' => 'en_espera', 'tipo' => 'desarrollo',
                'descripcion' => null, 'prioridad' => null, 'modulo_id' => null, 'modulo_nuevo' => null,
                'motivo_espera' => null, 'motivo_cancelacion' => null,
            ]],
        ])
        ->assertInvalid(['filas.0.motivo_espera']);

    expect(Tarea::query()->count())->toBe(0);
});
