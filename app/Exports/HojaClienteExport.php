<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\EstadoTarea;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithFreezePane;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * El reporte de cliente entero: una sola hoja, cuatro columnas. Nada de
 * códigos internos, prioridad, fechas, notas ni -sobre todo- nada que venga
 * de pagos.
 *
 * Esta clase nunca recibe esas columnas: `GenerarReporte` construye el
 * snapshot de tipo `cliente` sin ellas, así que no hay nada que omitir aquí
 * -no existe la posibilidad de que se cuele un dato que la fila nunca tuvo.
 */
final class HojaClienteExport implements FromArray, WithEvents, WithFreezePane, WithHeadings, WithTitle
{
    /**
     * @param  array<int, array{tarea: string, estado: string, estado_etiqueta: string, descripcion: string, modulo: string}>  $filas
     */
    public function __construct(private readonly array $filas) {}

    public function title(): string
    {
        return __('reportes.hojas.tareas');
    }

    public function freezePane(): string
    {
        return 'A2';
    }

    public function headings(): array
    {
        return [
            __('reportes.cliente.tarea'),
            __('reportes.cliente.estado'),
            __('reportes.cliente.descripcion'),
            __('reportes.cliente.modulo'),
        ];
    }

    public function array(): array
    {
        return array_map(fn (array $fila): array => [
            $fila['tarea'],
            $fila['estado_etiqueta'],
            $fila['descripcion'],
            $fila['modulo'],
        ], $this->filas);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $evento): void {
                $sheet = $evento->sheet->getDelegate();
                $ultimaFila = count($this->filas) + 1;

                FormatoDeHoja::bordesNegros($sheet, "A1:D{$ultimaFila}");
                FormatoDeHoja::encabezadoNegrita($sheet, 'A1:D1');
                FormatoDeHoja::anchoAutomatico($sheet, 'A', __('reportes.cliente.tarea'), array_column($this->filas, 'tarea'), 40);
                FormatoDeHoja::anchoAutomatico($sheet, 'B', __('reportes.cliente.estado'), array_column($this->filas, 'estado_etiqueta'), 16);
                FormatoDeHoja::columnaEnvuelta($sheet, 'C', 55);
                FormatoDeHoja::anchoAutomatico($sheet, 'D', __('reportes.cliente.modulo'), array_column($this->filas, 'modulo'), 30);

                foreach ($this->filas as $indice => $fila) {
                    $estado = EstadoTarea::from($fila['estado']);
                    FormatoDeHoja::rellenoDeEstado($sheet, 'B'.($indice + 2), PaletaEstadoExcel::relleno($estado));
                }
            },
        ];
    }
}
