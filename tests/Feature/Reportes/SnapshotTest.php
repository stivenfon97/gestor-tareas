<?php

declare(strict_types=1);

use App\Actions\GenerarReporte;
use App\Enums\EstadoTarea;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Enums\TipoReporte;
use App\Models\Modulo;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\DB;

/*
 * El snapshot es lo que hace posible la trazabilidad sin persistir el .xlsx:
 * guarda los valores tal como salieron al archivo, nunca identificadores.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create(['prefijo_tarea' => 'MAQ']);
    $this->periodo = Periodo::factory()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 4]);
    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);
});

it('renombrar un módulo y cambiar el estado de una tarea no altera la descarga histórica, y sí la actual', function () {
    $modulo = Modulo::factory()->for($this->proyecto)->create(['nombre' => 'Facturación']);
    $tarea = Tarea::factory()->for($this->proyecto)->create([
        'periodo_id' => $this->periodo->id,
        'modulo_id' => $modulo->id,
        'estado' => EstadoTarea::Pendiente,
        'orden' => 1,
    ]);

    $reporte = app(GenerarReporte::class)($this->periodo, TipoReporte::Completo, $this->lider);

    // Cambios posteriores a la generación, con `update()` de query builder para
    // no chocar con la matriz de transiciones: lo que se prueba aquí es si el
    // snapshot se mueve con ellos, no si la transición es válida.
    $modulo->update(['nombre' => 'Cuentas por Cobrar']);
    DB::table('tareas')->where('id', $tarea->id)->update(['estado' => EstadoTarea::EnProceso->value]);

    $libroHistorico = leerExcelDescargado($this->actingAs($this->lider)->get(route('reportes.descargar', $reporte)));
    $hojaHistorica = $libroHistorico->getSheetByName(__('reportes.hojas.tareas'));

    expect($hojaHistorica->getCell('C2')->getValue())->toBe('Facturación')
        ->and($hojaHistorica->getCell('E2')->getValue())->toBe(__('enums.estado_tarea.pendiente'));

    $reporteActual = app(GenerarReporte::class)($this->periodo, TipoReporte::Completo, $this->lider);
    $libroActual = leerExcelDescargado($this->actingAs($this->lider)->get(route('reportes.descargar', $reporteActual)));
    $hojaActual = $libroActual->getSheetByName(__('reportes.hojas.tareas'));

    expect($hojaActual->getCell('C2')->getValue())->toBe('Cuentas por Cobrar')
        ->and($hojaActual->getCell('E2')->getValue())->toBe(__('enums.estado_tarea.en_proceso'));

    // Y el registro histórico, releído de la base, sigue congelado.
    $reporte->refresh();
    expect($reporte->snapshot['tareas'][0]['modulo'])->toBe('Facturación')
        ->and($reporte->snapshot['tareas'][0]['estado'])->toBe('pendiente');
});

it('el hash se repite si los datos no cambian y cambia cuando sí', function () {
    Tarea::factory()->for($this->proyecto)->create(['periodo_id' => $this->periodo->id, 'orden' => 1]);

    $primero = app(GenerarReporte::class)($this->periodo, TipoReporte::Completo, $this->lider);
    $segundo = app(GenerarReporte::class)($this->periodo, TipoReporte::Completo, $this->lider);

    // Dos generaciones seguidas, sin cambios de por medio: dos filas -cada
    // generación crea una-, mismo contenido, mismo hash.
    expect($segundo->id)->not->toBe($primero->id)
        ->and($segundo->hash)->toBe($primero->hash);

    Tarea::factory()->for($this->proyecto)->create(['periodo_id' => $this->periodo->id, 'orden' => 2]);

    $tercero = app(GenerarReporte::class)($this->periodo, TipoReporte::Completo, $this->lider);

    expect($tercero->hash)->not->toBe($primero->hash);
});
