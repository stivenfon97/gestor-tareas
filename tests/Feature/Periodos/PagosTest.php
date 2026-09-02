<?php

declare(strict_types=1);

use App\Enums\OrigenTasaCambio;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use Database\Seeders\RolesYPermisosSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();
    $this->periodo = Periodo::factory()->cerrado()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 1]);

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);

    $this->cliente = usuarioCon(RolGlobal::Invitado);
    asignarAProyecto($this->cliente, $this->proyecto, RolProyecto::Cliente);
});

it('registra un pago para un periodo sin pago', function () {
    $this->actingAs($this->lider)
        ->post(route('proyectos.pagos.store', $this->proyecto), [
            'periodo_id' => $this->periodo->id,
            'fecha_pago' => '2026-02-05',
            'monto_usd' => '2000.00',
            'tasa_cambio' => '4050.5000',
            'origen_tasa' => 'manual',
            'referencia' => 'TRF-001',
        ])
        ->assertRedirect();

    $pago = Pago::query()->where('periodo_id', $this->periodo->id)->sole();

    expect($pago->monto_usd)->toBe('2000.00')
        ->and($pago->monto_cop)->toBe('8101000.00');
});

it('un segundo pago para el mismo periodo falla', function () {
    Pago::factory()->for($this->periodo)->create();

    $this->actingAs($this->lider)
        ->post(route('proyectos.pagos.store', $this->proyecto), [
            'periodo_id' => $this->periodo->id,
            'fecha_pago' => '2026-02-05',
            'monto_usd' => '500.00',
            'tasa_cambio' => '4000.0000',
            'origen_tasa' => 'manual',
        ])
        ->assertInvalid(['periodo_id']);

    expect(Pago::query()->where('periodo_id', $this->periodo->id)->count())->toBe(1);
});

it('un cliente no puede ver ni registrar pagos', function () {
    Pago::factory()->for($this->periodo)->create();

    $this->actingAs($this->cliente)
        ->get(route('proyectos.pagos.index', $this->proyecto))
        ->assertForbidden();

    // Un periodo sin pago todavía, para que el 403 se deba a la
    // autorización y no a que la validación ya rechazó el duplicado antes
    // de llegar a `Gate::authorize`.
    $otroPeriodo = Periodo::factory()->cerrado()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 2]);

    $this->actingAs($this->cliente)
        ->post(route('proyectos.pagos.store', $this->proyecto), [
            'periodo_id' => $otroPeriodo->id,
            'fecha_pago' => '2026-02-05',
            'monto_usd' => '500.00',
            'tasa_cambio' => '4000.0000',
            'origen_tasa' => 'manual',
        ])
        ->assertForbidden();

    expect(Pago::query()->where('periodo_id', $otroPeriodo->id)->exists())->toBeFalse();
});

it('actualiza un pago existente y recalcula monto_cop', function () {
    $pago = Pago::factory()->for($this->periodo)->create([
        'monto_usd' => '1000.00',
        'tasa_cambio' => '4000.0000',
        'origen_tasa' => OrigenTasaCambio::Api,
    ]);

    $this->actingAs($this->lider)
        ->put(route('pagos.update', $pago), [
            'fecha_pago' => $pago->fecha_pago->toDateString(),
            'monto_usd' => '1000.00',
            'tasa_cambio' => '4200.0000',
            'origen_tasa' => 'manual',
            'referencia' => $pago->referencia,
        ])
        ->assertRedirect();

    $pago->refresh();

    expect($pago->tasa_cambio)->toBe('4200.0000')
        ->and($pago->monto_cop)->toBe('4200000.00')
        ->and($pago->origen_tasa)->toBe(OrigenTasaCambio::Manual);
});

it('el índice de pagos no incluye periodos de otro proyecto', function () {
    $otro = Proyecto::factory()->create();
    $periodoAjeno = Periodo::factory()->cerrado()->create(['proyecto_id' => $otro->id, 'consecutivo' => 1]);
    Pago::factory()->for($periodoAjeno)->create();
    Pago::factory()->for($this->periodo)->create();

    $this->actingAs($this->lider)
        ->get(route('proyectos.pagos.index', $this->proyecto))
        ->assertOk()
        ->assertInertia(fn (Assert $pagina) => $pagina
            ->component('pagos/Index')
            ->has('pagos', 1));
});
