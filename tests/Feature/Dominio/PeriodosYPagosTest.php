<?php

declare(strict_types=1);

use App\Actions\AbrirPeriodo;
use App\Enums\EstadoPeriodo;
use App\Exceptions\PeriodoAbiertoExistente;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

it('un periodo admite un solo pago, y lo impide la base de datos', function () {
    $periodo = Periodo::factory()->cerrado()->create();

    Pago::factory()->for($periodo)->create();

    expect(fn () => Pago::factory()->for($periodo)->create())
        ->toThrow(UniqueConstraintViolationException::class);

    expect($periodo->pago()->count())->toBe(1);
});

it('calcula monto_cop multiplicando el monto en dólares por la tasa', function () {
    $pago = Pago::factory()->create([
        'monto_usd' => '1234.56',
        'tasa_cambio' => '4123.4567',
    ]);

    expect($pago->monto_cop)->toBe('5090654.70');
});

it('redondea el último centavo hacia arriba en lugar de truncarlo', function () {
    $pago = Pago::factory()->create([
        'monto_usd' => '1.00',
        'tasa_cambio' => '0.1250',
    ]);

    expect($pago->monto_cop)->toBe('0.13');
});

it('no recalcula monto_cop al leer: el histórico no cambia si se corrige la tasa', function () {
    $pago = Pago::factory()->create([
        'monto_usd' => '1000.00',
        'tasa_cambio' => '4000.0000',
    ]);

    expect($pago->monto_cop)->toBe('4000000.00');

    // Se corrige la tasa por fuera del modelo, sin pasar por el observer.
    DB::table('pagos')->where('id', $pago->id)->update(['tasa_cambio' => '9999.0000']);

    expect($pago->fresh()->monto_cop)->toBe('4000000.00');
});

it('recalcula monto_cop cuando el pago se guarda de nuevo', function () {
    $pago = Pago::factory()->create([
        'monto_usd' => '1000.00',
        'tasa_cambio' => '4000.0000',
    ]);

    $pago->update(['tasa_cambio' => '4100.0000']);

    expect($pago->fresh()->monto_cop)->toBe('4100000.00');
});

it('un periodo abierto no tiene fecha de fin', function () {
    $periodo = Periodo::factory()->create();

    expect($periodo->estado)->toBe(EstadoPeriodo::Abierto)
        ->and($periodo->fecha_fin)->toBeNull()
        ->and($periodo->duracionEnDias())->toBeNull()
        ->and($periodo->estaVencido())->toBeFalse();
});

it('marca como vencido el periodo abierto cuya fecha de fin ya pasó', function () {
    $periodo = Periodo::factory()->create([
        'fecha_inicio' => Carbon::now()->subDays(60),
        'fecha_fin' => Carbon::now()->subDays(10),
    ]);

    expect($periodo->estaVencido())->toBeTrue();
});

it('no marca como vencido un periodo ya cerrado', function () {
    $periodo = Periodo::factory()->create([
        'fecha_inicio' => Carbon::now()->subDays(60),
        'fecha_fin' => Carbon::now()->subDays(10),
        'estado' => EstadoPeriodo::Cerrado,
    ]);

    expect($periodo->estaVencido())->toBeFalse();
});

it('cuenta la duración incluyendo los dos extremos', function () {
    $periodo = Periodo::factory()->create([
        'fecha_inicio' => Carbon::parse('2026-03-01'),
        'fecha_fin' => Carbon::parse('2026-03-31'),
    ]);

    expect($periodo->duracionEnDias())->toBe(31);
});

it('el consecutivo es único dentro del proyecto', function () {
    $periodo = Periodo::factory()->create(['consecutivo' => 7]);

    expect(fn () => Periodo::factory()->create([
        'proyecto_id' => $periodo->proyecto_id,
        'consecutivo' => 7,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

it('un proyecto no admite un segundo periodo abierto, y lo impide la base de datos', function () {
    // Distinto consecutivo a propósito: lo que se comprueba aquí es la
    // columna generada `proyecto_si_abierto`, no el unique de consecutivo.
    $abierto = Periodo::factory()->create(['consecutivo' => 1]);

    expect(fn () => Periodo::factory()->create([
        'proyecto_id' => $abierto->proyecto_id,
        'consecutivo' => 2,
    ]))->toThrow(UniqueConstraintViolationException::class);

    expect($abierto->proyecto->periodos()->abiertos()->count())->toBe(1);
});

it('un proyecto sí admite varios periodos cerrados y uno abierto a la vez', function () {
    $proyecto = Proyecto::factory()->create();

    Periodo::factory()->cerrado()->create(['proyecto_id' => $proyecto->id, 'consecutivo' => 1]);
    Periodo::factory()->cerrado()->create(['proyecto_id' => $proyecto->id, 'consecutivo' => 2]);
    $abierto = Periodo::factory()->create(['proyecto_id' => $proyecto->id, 'consecutivo' => 3]);

    expect($proyecto->periodos()->count())->toBe(3)
        ->and($proyecto->periodoAbierto()->first()->is($abierto))->toBeTrue();
});

it('AbrirPeriodo asigna el consecutivo siguiente al máximo existente', function () {
    $proyecto = Proyecto::factory()->create();
    Periodo::factory()->cerrado()->create(['proyecto_id' => $proyecto->id, 'consecutivo' => 1]);
    Periodo::factory()->cerrado()->create(['proyecto_id' => $proyecto->id, 'consecutivo' => 2]);

    $abrir = app(AbrirPeriodo::class);
    $nuevo = $abrir($proyecto, ['fecha_inicio' => '2026-06-01', 'notas' => null]);

    expect($nuevo->consecutivo)->toBe(3)
        ->and($nuevo->estado)->toBe(EstadoPeriodo::Abierto)
        ->and($nuevo->fecha_fin)->toBeNull();

    // Repetido en secuencia: cada apertura ve la anterior ya persistida, que
    // es justo lo que el bloqueo de la fila del proyecto garantiza incluso
    // bajo creación concurrente -las dos peticiones se serializan sobre la
    // misma fila en lugar de leer el mismo `max()` a la vez-. La suite corre
    // sobre SQLite en memoria, con una sola conexión: dos transacciones
    // concurrentes de verdad no se pueden ejercitar aquí, igual que
    // `ContadorTarea::siguienteCodigo()` tampoco lo hace en esta suite.
    Periodo::whereKey($nuevo->id)->update(['estado' => EstadoPeriodo::Cerrado, 'fecha_fin' => '2026-06-30']);
    $siguiente = $abrir($proyecto->fresh(), ['fecha_inicio' => '2026-07-01', 'notas' => null]);

    expect($siguiente->consecutivo)->toBe(4);
});

it('AbrirPeriodo rechaza abrir un segundo periodo mientras uno sigue abierto', function () {
    $proyecto = Proyecto::factory()->create();
    Periodo::factory()->create(['proyecto_id' => $proyecto->id, 'consecutivo' => 1]);

    $abrir = app(AbrirPeriodo::class);

    expect(fn () => $abrir($proyecto, ['fecha_inicio' => '2026-06-01', 'notas' => null]))
        ->toThrow(PeriodoAbiertoExistente::class);

    expect($proyecto->periodos()->count())->toBe(1);
});
