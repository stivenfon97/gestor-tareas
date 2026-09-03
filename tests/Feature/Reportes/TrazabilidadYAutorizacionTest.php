<?php

declare(strict_types=1);

use App\Actions\GenerarReporte;
use App\Enums\EstadoTarea;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Enums\TipoReporte;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Reporte;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create(['prefijo_tarea' => 'MAQ']);
    $this->periodo = Periodo::factory()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 3]);

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);

    $this->observador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->observador, $this->proyecto, RolProyecto::Observador);

    $this->cliente = usuarioCon(RolGlobal::Invitado);
    asignarAProyecto($this->cliente, $this->proyecto, RolProyecto::Cliente);
});

it('generar crea exactamente una fila en reportes; descargar el histórico no crea ninguna', function () {
    Tarea::factory()->for($this->proyecto)->create(['periodo_id' => $this->periodo->id, 'orden' => 1]);

    $this->actingAs($this->lider)->post(route('proyectos.reportes.store', $this->proyecto), [
        'periodo_id' => $this->periodo->id,
        'tipo' => 'cliente',
    ])->assertRedirect();

    expect(Reporte::query()->count())->toBe(1);

    $reporte = Reporte::query()->sole();

    $this->actingAs($this->lider)->get(route('reportes.descargar', $reporte))->assertOk();
    $this->actingAs($this->lider)->get(route('reportes.descargar', $reporte))->assertOk();

    expect(Reporte::query()->count())->toBe(1);
});

it('resumen_estados coincide con el conteo real de tareas del periodo', function () {
    Tarea::factory()->for($this->proyecto)->create(['periodo_id' => $this->periodo->id, 'estado' => EstadoTarea::Pendiente, 'orden' => 1]);
    Tarea::factory()->for($this->proyecto)->create(['periodo_id' => $this->periodo->id, 'estado' => EstadoTarea::Pendiente, 'orden' => 2]);
    Tarea::factory()->for($this->proyecto)->cancelada()->create(['periodo_id' => $this->periodo->id, 'orden' => 3]);

    $reporte = app(GenerarReporte::class)($this->periodo, TipoReporte::Completo, $this->lider);

    expect($reporte->resumen_estados)->toBe([
        'pendiente' => 2,
        'en_proceso' => 0,
        'en_espera' => 0,
        'terminada' => 0,
        'entregada' => 0,
        'cancelada' => 1,
    ])->and($reporte->total_tareas)->toBe(3);
});

it('el cliente recibe 403 al intentar generar y al abrir el listado', function () {
    $this->actingAs($this->cliente)->post(route('proyectos.reportes.store', $this->proyecto), [
        'periodo_id' => $this->periodo->id,
        'tipo' => 'cliente',
    ])->assertForbidden();

    $this->actingAs($this->cliente)->get(route('proyectos.reportes.index', $this->proyecto))->assertForbidden();

    expect(Reporte::query()->count())->toBe(0);
});

it('el observador ve el listado pero recibe 403 al intentar generar', function () {
    $this->actingAs($this->observador)->post(route('proyectos.reportes.store', $this->proyecto), [
        'periodo_id' => $this->periodo->id,
        'tipo' => 'cliente',
    ])->assertForbidden();

    $this->actingAs($this->observador)->get(route('proyectos.reportes.index', $this->proyecto))->assertOk();

    expect(Reporte::query()->count())->toBe(0);
});

it('el nombre de archivo lleva el sufijo del tipo, obligatorio para no colisionar entre los dos reportes', function () {
    Tarea::factory()->for($this->proyecto)->create(['periodo_id' => $this->periodo->id, 'orden' => 1]);

    $completo = app(GenerarReporte::class)($this->periodo, TipoReporte::Completo, $this->lider);
    $cliente = app(GenerarReporte::class)($this->periodo, TipoReporte::Cliente, $this->lider);

    $hoy = now()->toDateString();

    expect($completo->nombre_archivo)->toBe("MAQ_P3_{$hoy}_completo.xlsx")
        ->and($cliente->nombre_archivo)->toBe("MAQ_P3_{$hoy}_cliente.xlsx")
        ->and($completo->nombre_archivo)->not->toBe($cliente->nombre_archivo);
});

it('el listado de reportes no serializa ningún dato de pago, ni para quien sí puede generar', function () {
    Pago::factory()->for($this->periodo)->create(['monto_usd' => '999.99', 'referencia' => 'REF-SECRETA']);
    Tarea::factory()->for($this->proyecto)->create(['periodo_id' => $this->periodo->id, 'orden' => 1]);
    app(GenerarReporte::class)($this->periodo, TipoReporte::Completo, $this->lider);

    $respuesta = $this->actingAs($this->lider)->get(route('proyectos.reportes.index', $this->proyecto));
    $respuesta->assertOk();

    $json = json_encode($respuesta->viewData('page')['props']);

    expect($json)->not->toContain('999.99')
        ->and($json)->not->toContain('REF-SECRETA')
        ->and($json)->not->toContain('monto_usd')
        ->and($json)->not->toContain('tasa_cambio')
        ->and($json)->not->toContain('snapshot');
});
