<?php

declare(strict_types=1);

use App\Actions\GenerarReporte;
use App\Enums\EstadoTarea;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Enums\TipoReporte;
use App\Exports\PaletaEstadoExcel;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Border;

/*
 * El formato no es decoración: es lo que hace que el archivo se pueda leer
 * en un lector de hojas de cálculo real. Estas pruebas abren el `.xlsx`
 * generado y comprueban el archivo, no el código que lo produjo.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create(['prefijo_tarea' => 'MAQ']);
    $this->periodo = Periodo::factory()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 7]);
    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);
});

it('el completo tiene cuatro hojas con los títulos y encabezados esperados', function () {
    Tarea::factory()->for($this->proyecto)->create([
        'periodo_id' => $this->periodo->id,
        'estado' => EstadoTarea::Pendiente,
        'orden' => 1,
    ]);
    Pago::factory()->for($this->periodo)->create();

    $reporte = app(GenerarReporte::class)($this->periodo, TipoReporte::Completo, $this->lider);
    $libro = leerExcelDescargado($this->actingAs($this->lider)->get(route('reportes.descargar', $reporte)));

    expect($libro->getSheetCount())->toBe(4)
        ->and($libro->getSheetNames())->toBe([
            __('reportes.hojas.resumen'),
            __('reportes.hojas.tareas'),
            __('reportes.hojas.notas'),
            __('reportes.hojas.pagos'),
        ]);

    $hojaTareas = $libro->getSheetByName(__('reportes.hojas.tareas'));

    expect(array_map(
        fn (int $columna): mixed => $hojaTareas->getCell([$columna, 1])->getValue(),
        range(1, 11),
    ))->toBe([
        __('reportes.tareas.codigo'),
        __('reportes.tareas.tipo'),
        __('reportes.tareas.modulo'),
        __('reportes.tareas.tarea'),
        __('reportes.tareas.estado'),
        __('reportes.tareas.prioridad'),
        __('reportes.tareas.descripcion'),
        __('reportes.tareas.fecha_inicio'),
        __('reportes.tareas.fecha_fin'),
        __('reportes.tareas.arrastrada'),
        __('reportes.tareas.notas'),
    ]);

    // Encabezado congelado: la fila 1 sigue visible al bajar por la hoja.
    expect($hojaTareas->getFreezePane())->toBe('A2');
});

it('el completo incluye las tareas canceladas', function () {
    Tarea::factory()->for($this->proyecto)->cancelada('El cliente desistió.')->create([
        'periodo_id' => $this->periodo->id,
        'orden' => 1,
    ]);

    $reporte = app(GenerarReporte::class)($this->periodo, TipoReporte::Completo, $this->lider);
    $libro = leerExcelDescargado($this->actingAs($this->lider)->get(route('reportes.descargar', $reporte)));

    $hojaTareas = $libro->getSheetByName(__('reportes.hojas.tareas'));

    expect($hojaTareas->getCell('E2')->getValue())->toBe(__('enums.estado_tarea.cancelada'));
});

it('la cuadrícula negra cubre las celdas con datos y el relleno de una celda de estado corresponde al estado', function () {
    Tarea::factory()->for($this->proyecto)->create([
        'periodo_id' => $this->periodo->id,
        'estado' => EstadoTarea::Terminada,
        'orden' => 1,
    ]);

    $reporte = app(GenerarReporte::class)($this->periodo, TipoReporte::Completo, $this->lider);
    $libro = leerExcelDescargado($this->actingAs($this->lider)->get(route('reportes.descargar', $reporte)));

    $hojaTareas = $libro->getSheetByName(__('reportes.hojas.tareas'));

    // Cuadrícula: cualquier celda con datos lleva los cuatro bordes finos y negros.
    $bordes = $hojaTareas->getStyle('A1')->getBorders();
    expect($bordes->getTop()->getBorderStyle())->toBe(Border::BORDER_THIN)
        ->and($bordes->getTop()->getColor()->getRGB())->toBe('000000')
        ->and($bordes->getBottom()->getBorderStyle())->toBe(Border::BORDER_THIN)
        ->and($bordes->getLeft()->getBorderStyle())->toBe(Border::BORDER_THIN)
        ->and($bordes->getRight()->getBorderStyle())->toBe(Border::BORDER_THIN);

    // Relleno de la celda de estado (columna E, fila 2: la única tarea).
    $celdaEstado = $hojaTareas->getStyle('E2');
    expect($celdaEstado->getFill()->getStartColor()->getRGB())->toBe(PaletaEstadoExcel::relleno(EstadoTarea::Terminada))
        ->and($celdaEstado->getFont()->getColor()->getRGB())->toBe('000000');
});

it('las fechas se guardan como fecha y los montos como número, no como texto', function () {
    Tarea::factory()->for($this->proyecto)->create([
        'periodo_id' => $this->periodo->id,
        'estado' => EstadoTarea::EnProceso,
        'fecha_inicio' => '2026-03-14',
        'orden' => 1,
    ]);
    Pago::factory()->for($this->periodo)->create(['monto_usd' => '1500.50', 'tasa_cambio' => '4123.4567']);

    $reporte = app(GenerarReporte::class)($this->periodo, TipoReporte::Completo, $this->lider);
    $libro = leerExcelDescargado($this->actingAs($this->lider)->get(route('reportes.descargar', $reporte)));

    $hojaTareas = $libro->getSheetByName(__('reportes.hojas.tareas'));
    $celdaFecha = $hojaTareas->getCell('H2');

    expect(is_numeric($celdaFecha->getValue()))->toBeTrue()
        ->and(Date::isDateTime($celdaFecha))->toBeTrue()
        ->and(Date::excelToDateTimeObject($celdaFecha->getValue())->format('Y-m-d'))->toBe('2026-03-14');

    $hojaPagos = $libro->getSheetByName(__('reportes.hojas.pagos'));
    $celdaMonto = $hojaPagos->getCell('B2');

    expect(is_numeric($celdaMonto->getValue()))->toBeTrue()
        ->and((float) $celdaMonto->getValue())->toBe(1500.5)
        ->and(Date::isDateTime($hojaPagos->getCell('A2')))->toBeTrue();
});

it('una descripción larga no ensancha la columna más allá del máximo porque lleva wrapText', function () {
    Tarea::factory()->for($this->proyecto)->create([
        'periodo_id' => $this->periodo->id,
        'descripcion' => str_repeat('Una descripción de verdad, larga, con detalle técnico real. ', 20),
        'orden' => 1,
    ]);

    $reporte = app(GenerarReporte::class)($this->periodo, TipoReporte::Completo, $this->lider);
    $libro = leerExcelDescargado($this->actingAs($this->lider)->get(route('reportes.descargar', $reporte)));

    $hojaTareas = $libro->getSheetByName(__('reportes.hojas.tareas'));

    expect($hojaTareas->getColumnDimension('G')->getWidth())->toBe(50.0)
        ->and($hojaTareas->getStyle('G')->getAlignment()->getWrapText())->toBeTrue();
});

it('el reporte de un periodo sin tareas se genera sin reventar y produce un archivo legible', function () {
    $reporte = app(GenerarReporte::class)($this->periodo, TipoReporte::Completo, $this->lider);

    expect($reporte->total_tareas)->toBe(0);

    $libro = leerExcelDescargado($this->actingAs($this->lider)->get(route('reportes.descargar', $reporte)));

    expect($libro->getSheetCount())->toBe(4);

    $hojaTareas = $libro->getSheetByName(__('reportes.hojas.tareas'));
    // Solo el encabezado: nada por debajo.
    expect($hojaTareas->getHighestRow())->toBe(1);
});
