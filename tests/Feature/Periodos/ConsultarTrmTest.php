<?php

declare(strict_types=1);

use App\Enums\OrigenTasaCambio;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Services\ConsultarTrm;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/*
 * Ninguna de estas pruebas llama a la API real: `Http::fake()` la sustituye
 * siempre. La fuente es el conjunto de datos oficial de la TRM en
 * datos.gov.co (recurso 32sa-8pi3), verificado a mano antes de escribir el
 * servicio: publica la tasa por rangos de vigencia, no un valor por día
 * exacto -un viernes vale también para el fin de semana-, así que el
 * `$where` filtra por rango en lugar de por igualdad.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
});

it('devuelve la tasa vigente en la fecha pedida', function () {
    Http::fake([
        'www.datos.gov.co/*' => Http::response([
            ['valor' => '4091.1015', 'unidad' => 'COP', 'vigenciadesde' => '2026-08-12T00:00:00.000', 'vigenciahasta' => '2026-08-12T00:00:00.000'],
        ]),
    ]);

    $tasa = app(ConsultarTrm::class)(Carbon::parse('2026-08-12'));

    expect($tasa)->toBe('4091.1015');
});

it('no llama dos veces por la misma fecha: la segunda la sirve la caché', function () {
    Http::fake([
        'www.datos.gov.co/*' => Http::response([
            ['valor' => '4091.1015', 'unidad' => 'COP', 'vigenciadesde' => '2026-08-12T00:00:00.000', 'vigenciahasta' => '2026-08-12T00:00:00.000'],
        ]),
    ]);

    $consultar = app(ConsultarTrm::class);
    $primera = $consultar(Carbon::parse('2026-08-12'));
    $segunda = $consultar(Carbon::parse('2026-08-12'));

    expect($primera)->toBe('4091.1015')
        ->and($segunda)->toBe('4091.1015');
    Http::assertSentCount(1);
});

it('devuelve null sin romper nada cuando la API no responde', function () {
    Http::fake([
        'www.datos.gov.co/*' => fn () => throw new ConnectionException('tiempo agotado'),
    ]);

    $tasa = app(ConsultarTrm::class)(Carbon::parse('2026-08-12'));

    expect($tasa)->toBeNull();
});

it('devuelve null cuando la API responde vacío para esa fecha', function () {
    Http::fake([
        'www.datos.gov.co/*' => Http::response([]),
    ]);

    $tasa = app(ConsultarTrm::class)(Carbon::parse('2026-08-12'));

    expect($tasa)->toBeNull();
});

it('devuelve null cuando la API responde con un error del servidor', function () {
    Http::fake([
        'www.datos.gov.co/*' => Http::response('fuera de servicio', 503),
    ]);

    $tasa = app(ConsultarTrm::class)(Carbon::parse('2026-08-12'));

    expect($tasa)->toBeNull();
});

it('un fallo de la API no impide registrar el pago a mano', function () {
    Http::fake([
        'www.datos.gov.co/*' => fn () => throw new ConnectionException('tiempo agotado'),
    ]);

    $proyecto = Proyecto::factory()->create();
    $periodo = Periodo::factory()->cerrado()->create(['proyecto_id' => $proyecto->id, 'consecutivo' => 1]);
    $lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($lider, $proyecto, RolProyecto::Lider);

    $this->actingAs($lider)
        ->post(route('proyectos.pagos.store', $proyecto), [
            'periodo_id' => $periodo->id,
            'fecha_pago' => '2026-08-12',
            'monto_usd' => '1000.00',
            'tasa_cambio' => '4123.4567',
            'origen_tasa' => 'manual',
        ])
        ->assertRedirect();

    expect(Pago::query()->where('periodo_id', $periodo->id)->sole()->origen_tasa)
        ->toBe(OrigenTasaCambio::Manual);
});

it('el endpoint pagos/trm devuelve la tasa como JSON', function () {
    Http::fake([
        'www.datos.gov.co/*' => Http::response([
            ['valor' => '3184.0', 'unidad' => 'COP', 'vigenciadesde' => '2026-09-02T00:00:00.000', 'vigenciahasta' => '2026-09-02T00:00:00.000'],
        ]),
    ]);

    $usuario = usuarioCon(RolGlobal::Miembro);

    $this->actingAs($usuario)
        ->get(route('pagos.trm', ['fecha' => '2026-09-02']))
        ->assertOk()
        ->assertJson(['tasa_cambio' => '3184.0000']);
});

it('el endpoint pagos/trm no revienta con una fecha mal formada', function () {
    $usuario = usuarioCon(RolGlobal::Miembro);

    $this->actingAs($usuario)
        ->get(route('pagos.trm', ['fecha' => 'no-es-una-fecha']))
        ->assertOk()
        ->assertJson(['tasa_cambio' => null]);
});
