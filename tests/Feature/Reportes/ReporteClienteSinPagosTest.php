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

    $this->pago = Pago::factory()->for($this->periodo)->create([
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

    /*
     * Valores completos, nunca fragmentos cortos: un fragmento de tres o
     * cuatro caracteres puede aparecer por azar dentro del texto de relleno
     * que genera el seeder ("USD" cabe dentro de "quibusdam"), y ese día la
     * prueba falla por un falso positivo hasta que alguien la relaje —que es
     * como mueren las pruebas de seguridad.
     *
     * Se prueban las dos formas en que un dato de pago podría colarse: el
     * valor crudo del cast `decimal` del modelo (sin separador de miles, como
     * saldría si alguien lo escribiera directo en una celda) y el valor tal
     * como lo formatea `HojaPagosExport::columnFormats()` (`#,##0.00` /
     * `#,##0.0000`, con separador de miles), que es como se vería si la hoja
     * de pagos se pintara aquí por error. Comprobar solo la forma cruda deja
     * un punto ciego real: "3,527.90" no contiene la subcadena "3527.90".
     */
    $rastrosDePago = [
        $this->pago->monto_usd,
        $this->pago->tasa_cambio,
        $this->pago->monto_cop,
        number_format((float) $this->pago->monto_usd, 2),
        number_format((float) $this->pago->tasa_cambio, 4),
        number_format((float) $this->pago->monto_cop, 2),
        $this->pago->referencia,
        $this->pago->notas,
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
