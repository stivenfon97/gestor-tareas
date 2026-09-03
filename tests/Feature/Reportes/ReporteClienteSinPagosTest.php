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
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;

/*
 * La prueba más importante del módulo. No basta con comprobar que la hoja de
 * pagos no existe: recorre el archivo generado, celda por celda, buscando
 * cualquier rastro de dinero. Es la única que impide una filtración real.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create(['prefijo_tarea' => 'MAQ']);
    $this->periodo = Periodo::factory()->create(['proyecto_id' => $this->proyecto->id, 'consecutivo' => 7]);

    Pago::factory()->for($this->periodo)->create([
        'monto_usd' => '3527.90',
        'tasa_cambio' => '4123.4567',
        'monto_cop' => '14545872.30',
        'referencia' => 'TRF-ULTRASECRETO-9001',
        'notas' => 'Pago parcial, pendiente el saldo restante en el siguiente periodo.',
    ]);

    Tarea::factory()->for($this->proyecto)->create([
        'periodo_id' => $this->periodo->id,
        'estado' => EstadoTarea::Entregada,
        'orden' => 1,
    ]);
    Tarea::factory()->for($this->proyecto)->cancelada('Se resolvió por configuración.')->create([
        'periodo_id' => $this->periodo->id,
        'orden' => 2,
    ]);

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);
});

it('el reporte de cliente no contiene ningún rastro de datos de pago en ninguna celda de ninguna hoja', function () {
    $reporte = app(GenerarReporte::class)($this->periodo, TipoReporte::Cliente, $this->lider);

    $libro = leerExcelDescargado($this->actingAs($this->lider)->get(route('reportes.descargar', $reporte)));

    // Si cualquiera de estas cadenas aparece en cualquier celda, hay una
    // filtración: son valores que solo pueden venir del pago registrado.
    $rastrosDePago = [
        '3527', '3527.90', '4123', '4123.4567', '14545872', '14545872.30',
        'TRF-ULTRASECRETO-9001', 'Pago parcial',
    ];

    foreach ($libro->getAllSheets() as $hoja) {
        foreach ($hoja->getRowIterator() as $filaExcel) {
            foreach ($filaExcel->getCellIterator() as $celda) {
                $valor = (string) $celda->getFormattedValue();

                foreach ($rastrosDePago as $rastro) {
                    expect($valor)->not->toContain($rastro);
                }
            }
        }
    }
});

it('el reporte de cliente tiene exactamente una hoja, con las cuatro columnas exactas', function () {
    $reporte = app(GenerarReporte::class)($this->periodo, TipoReporte::Cliente, $this->lider);

    $libro = leerExcelDescargado($this->actingAs($this->lider)->get(route('reportes.descargar', $reporte)));

    expect($libro->getSheetCount())->toBe(1);

    $hoja = $libro->getActiveSheet();

    expect([
        $hoja->getCell('A1')->getValue(),
        $hoja->getCell('B1')->getValue(),
        $hoja->getCell('C1')->getValue(),
        $hoja->getCell('D1')->getValue(),
    ])->toBe([
        __('reportes.cliente.tarea'),
        __('reportes.cliente.estado'),
        __('reportes.cliente.descripcion'),
        __('reportes.cliente.modulo'),
    ])->and($hoja->getHighestColumn())->toBe('D');
});

it('el snapshot de tipo cliente no guarda resumen, notas ni pagos: el dato nunca entra en la fila', function () {
    $reporte = app(GenerarReporte::class)($this->periodo, TipoReporte::Cliente, $this->lider);

    expect($reporte->snapshot)->toHaveKey('tareas')
        ->and($reporte->snapshot)->not->toHaveKey('resumen')
        ->and($reporte->snapshot)->not->toHaveKey('notas')
        ->and($reporte->snapshot)->not->toHaveKey('pagos');
});

it('el reporte de cliente incluye igualmente las tareas canceladas, sin código ni prioridad', function () {
    $reporte = app(GenerarReporte::class)($this->periodo, TipoReporte::Cliente, $this->lider);

    $libro = leerExcelDescargado($this->actingAs($this->lider)->get(route('reportes.descargar', $reporte)));
    $hoja = $libro->getActiveSheet();

    $estados = [$hoja->getCell('B2')->getValue(), $hoja->getCell('B3')->getValue()];

    expect($estados)->toContain(__('enums.estado_tarea.cancelada'));
});
