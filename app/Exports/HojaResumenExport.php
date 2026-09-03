<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithFreezePane;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Hoja «Resumen» del reporte completo. Solo existe ahí: lleva el pago, y el
 * reporte de cliente no la lleva ni vacía.
 */
final class HojaResumenExport implements FromArray, WithEvents, WithFreezePane, WithHeadings, WithTitle
{
    /**
     * @param  array<int, array{etiqueta: string, valor: mixed, tipo: string}>  $filas
     */
    public function __construct(private readonly array $filas) {}

    public function title(): string
    {
        return __('reportes.hojas.resumen');
    }

    public function freezePane(): string
    {
        return 'A2';
    }

    public function headings(): array
    {
        return [__('reportes.resumen.campo'), __('reportes.resumen.valor')];
    }

    public function array(): array
    {
        return array_map(
            fn (array $fila): array => [
                $fila['etiqueta'],
                $fila['tipo'] === 'fecha' ? FormatoDeHoja::fechaExcel($fila['valor']) : $fila['valor'],
            ],
            $this->filas,
        );
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $evento): void {
                $sheet = $evento->sheet->getDelegate();
                $ultimaFila = count($this->filas) + 1;

                FormatoDeHoja::bordesNegros($sheet, "A1:B{$ultimaFila}");
                FormatoDeHoja::encabezadoNegrita($sheet, 'A1:B1');
                FormatoDeHoja::anchoAutomatico($sheet, 'A', __('reportes.resumen.campo'), array_column($this->filas, 'etiqueta'), 26);
                $sheet->getColumnDimension('B')->setWidth(26);

                // Hoja mixta -campo/valor-: el formato de fecha se aplica
                // celda a celda, no por columna, porque la columna B mezcla
                // texto, números y fechas según la fila.
                foreach ($this->filas as $indice => $fila) {
                    if ($fila['tipo'] === 'fecha' && $fila['valor'] !== null) {
                        $sheet->getStyle('B'.($indice + 2))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);
                    }
                }
            },
        ];
    }
}
