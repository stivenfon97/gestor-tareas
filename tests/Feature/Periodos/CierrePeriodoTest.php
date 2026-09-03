<?php

declare(strict_types=1);

use App\Actions\CerrarPeriodo;
use App\Enums\EstadoPeriodo;
use App\Enums\EstadoTarea;
use App\Enums\OrigenTasaCambio;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Exceptions\PeriodoYaCerrado;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Database\QueryException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();
    $this->periodo = Periodo::factory()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 1]);

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);

    $this->colaborador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->colaborador, $this->proyecto, RolProyecto::Colaborador);
});

it('la pantalla de cierre resume el periodo y propone el día siguiente para el nuevo', function () {
    Tarea::factory()->for($this->proyecto)->create(['periodo_id' => $this->periodo->id, 'estado' => EstadoTarea::Pendiente]);

    $this->actingAs($this->lider)
        ->get(route('periodos.cierre.create', $this->periodo))
        ->assertOk()
        ->assertInertia(fn (Assert $pagina) => $pagina
            ->component('periodos/Cierre')
            ->where('resumen.total', 1)
            ->has('pendientes', 1)
            ->where(
                'fechaInicioSiguientePropuesta',
                now()->addDay()->toDateString(),
            ));
});

it('una tarea terminada pero sin entregar se ofrece para trasladar (D4.13)', function () {
    // Terminada no es terminal (D2.6, D2.7 solo marcan entregada y cancelada):
    // el commit puede estar en la nube y el cliente sin confirmar la entrega
    // todavía, así que sigue siendo trabajo abierto.
    $terminada = Tarea::factory()->for($this->proyecto)->create([
        'periodo_id' => $this->periodo->id,
        'estado' => EstadoTarea::Terminada,
    ]);
    $entregada = Tarea::factory()->for($this->proyecto)->create([
        'periodo_id' => $this->periodo->id,
        'estado' => EstadoTarea::Entregada,
    ]);

    $this->actingAs($this->lider)
        ->get(route('periodos.cierre.create', $this->periodo))
        ->assertOk()
        ->assertInertia(fn (Assert $pagina) => $pagina
            ->component('periodos/Cierre')
            ->where(
                'pendientes',
                fn ($pendientes) => collect($pendientes)->pluck('id')->contains($terminada->id)
                    && ! collect($pendientes)->pluck('id')->contains($entregada->id),
            ));

    $this->actingAs($this->lider)
        ->post(route('periodos.cierre.store', $this->periodo), [
            'tareas_trasladar' => [$terminada->id],
            'fecha_inicio_siguiente' => '2026-02-01',
        ])
        ->assertRedirect();

    $siguiente = Periodo::query()->where('proyecto_id', $this->proyecto->id)->where('consecutivo', 2)->sole();

    expect($terminada->fresh()->periodo_id)->toBe($siguiente->id)
        ->and($terminada->fresh()->estado)->toBe(EstadoTarea::Terminada)
        ->and($entregada->fresh()->periodo_id)->toBe($this->periodo->id);
});

it('un colaborador recibe 403 al intentar cerrar', function () {
    $this->actingAs($this->colaborador)
        ->get(route('periodos.cierre.create', $this->periodo))
        ->assertForbidden();

    $this->actingAs($this->colaborador)
        ->post(route('periodos.cierre.store', $this->periodo), [
            'tareas_trasladar' => [],
            'fecha_inicio_siguiente' => '2026-02-01',
        ])
        ->assertForbidden();

    expect($this->periodo->fresh()->estado)->toBe(EstadoPeriodo::Abierto);
});

it('traslada exactamente las tareas seleccionadas, ni una más', function () {
    $trasladar = Tarea::factory()->for($this->proyecto)->count(17)->create([
        'periodo_id' => $this->periodo->id,
        'estado' => EstadoTarea::Pendiente,
    ]);
    $seQuedan = Tarea::factory()->for($this->proyecto)->count(3)->create([
        'periodo_id' => $this->periodo->id,
        'estado' => EstadoTarea::EnProceso,
    ]);

    $this->actingAs($this->lider)
        ->post(route('periodos.cierre.store', $this->periodo), [
            'tareas_trasladar' => $trasladar->pluck('id')->all(),
            'fecha_inicio_siguiente' => '2026-02-01',
        ])
        ->assertRedirect();

    $siguiente = Periodo::query()->where('proyecto_id', $this->proyecto->id)->where('consecutivo', 2)->sole();

    foreach ($trasladar as $tarea) {
        $fresca = $tarea->fresh();
        expect($fresca->periodo_id)->toBe($siguiente->id)
            ->and($fresca->periodo_origen_id)->toBe($this->periodo->id)
            // Conserva su estado: trasladar no es un cambio de estado.
            ->and($fresca->estado)->toBe(EstadoTarea::Pendiente);
    }

    foreach ($seQuedan as $tarea) {
        $fresca = $tarea->fresh();
        expect($fresca->periodo_id)->toBe($this->periodo->id)
            ->and($fresca->periodo_origen_id)->toBeNull();
    }
});

it('las tareas trasladadas no escriben en el historial de estados', function () {
    $tarea = Tarea::factory()->for($this->proyecto)->create([
        'periodo_id' => $this->periodo->id,
        'estado' => EstadoTarea::EnEspera,
        'motivo_espera' => 'Falta información del cliente.',
    ]);

    $this->actingAs($this->lider)
        ->post(route('periodos.cierre.store', $this->periodo), [
            'tareas_trasladar' => [$tarea->id],
            'fecha_inicio_siguiente' => '2026-02-01',
        ])
        ->assertRedirect();

    expect($tarea->historial()->count())->toBe(0)
        ->and($tarea->fresh()->motivo_espera)->toBe('Falta información del cliente.');
});

it('desmarcar una tarea la deja en el periodo que cierra', function () {
    $tarea = Tarea::factory()->for($this->proyecto)->create([
        'periodo_id' => $this->periodo->id,
        'estado' => EstadoTarea::Pendiente,
    ]);

    $this->actingAs($this->lider)
        ->post(route('periodos.cierre.store', $this->periodo), [
            'tareas_trasladar' => [],
            'fecha_inicio_siguiente' => '2026-02-01',
        ])
        ->assertRedirect();

    expect($tarea->fresh()->periodo_id)->toBe($this->periodo->id);
});

it('cierra el periodo, lo marca con la fecha de hoy y abre el siguiente', function () {
    Carbon\Carbon::setTestNow('2026-03-15');

    $this->actingAs($this->lider)
        ->post(route('periodos.cierre.store', $this->periodo), [
            'tareas_trasladar' => [],
            'fecha_inicio_siguiente' => '2026-03-16',
        ])
        ->assertRedirect();

    $cerrado = $this->periodo->fresh();
    $siguiente = Periodo::query()->where('proyecto_id', $this->proyecto->id)->where('consecutivo', 2)->sole();

    expect($cerrado->estado)->toBe(EstadoPeriodo::Cerrado)
        ->and($cerrado->fecha_fin->toDateString())->toBe('2026-03-15')
        ->and($siguiente->estado)->toBe(EstadoPeriodo::Abierto)
        ->and($siguiente->fecha_inicio->toDateString())->toBe('2026-03-16')
        ->and($siguiente->fecha_fin)->toBeNull();

    Carbon\Carbon::setTestNow();
});

it('cerrar sin registrar pago es válido', function () {
    $this->actingAs($this->lider)
        ->post(route('periodos.cierre.store', $this->periodo), [
            'tareas_trasladar' => [],
            'fecha_inicio_siguiente' => '2026-02-01',
        ])
        ->assertRedirect();

    expect(Pago::query()->where('periodo_id', $this->periodo->id)->exists())->toBeFalse();
});

it('registra el pago en la misma operación cuando se envía', function () {
    $this->actingAs($this->lider)
        ->post(route('periodos.cierre.store', $this->periodo), [
            'tareas_trasladar' => [],
            'fecha_inicio_siguiente' => '2026-02-01',
            'pago' => [
                'fecha_pago' => '2026-01-31',
                'monto_usd' => '1800.00',
                'tasa_cambio' => '4100.5000',
                'origen_tasa' => 'manual',
                'referencia' => 'TRF-0099',
            ],
        ])
        ->assertRedirect();

    $pago = Pago::query()->where('periodo_id', $this->periodo->id)->sole();

    expect($pago->monto_usd)->toBe('1800.00')
        ->and($pago->origen_tasa)->toBe(OrigenTasaCambio::Manual)
        ->and($pago->monto_cop)->toBe('7380900.00');
});

it('un fallo a mitad del cierre no deja nada aplicado', function () {
    // Se fuerza el fallo justo en el último paso -crear el pago- dejando que
    // el periodo ya tenga uno: la transacción llega a cerrar el periodo,
    // trasladar la tarea y abrir el siguiente antes de reventar contra el
    // `unique` de `pagos.periodo_id`.
    Pago::factory()->for($this->periodo)->create();

    $tarea = Tarea::factory()->for($this->proyecto)->create([
        'periodo_id' => $this->periodo->id,
        'estado' => EstadoTarea::Pendiente,
    ]);

    $cerrar = app(CerrarPeriodo::class);

    expect(fn () => $cerrar(
        $this->periodo,
        [$tarea->id],
        '2026-02-01',
        [
            'fecha_pago' => '2026-01-31',
            'monto_usd' => '100.00',
            'tasa_cambio' => '4000.0000',
            'origen_tasa' => 'manual',
            'referencia' => null,
            'notas' => null,
        ],
    ))->toThrow(QueryException::class);

    expect($this->periodo->fresh()->estado)->toBe(EstadoPeriodo::Abierto)
        ->and($this->periodo->fresh()->fecha_fin)->toBeNull()
        ->and(Periodo::query()->where('proyecto_id', $this->proyecto->id)->count())->toBe(1)
        ->and($tarea->fresh()->periodo_id)->toBe($this->periodo->id)
        ->and($tarea->fresh()->periodo_origen_id)->toBeNull()
        ->and(Pago::query()->where('periodo_id', $this->periodo->id)->count())->toBe(1);
});

it('cerrar un periodo ya cerrado falla en lugar de cerrarlo dos veces', function () {
    $cerrar = app(CerrarPeriodo::class);
    $cerrar($this->periodo, [], '2026-02-01');

    expect(fn () => $cerrar($this->periodo->fresh(), [], '2026-02-02'))
        ->toThrow(PeriodoYaCerrado::class);
});
