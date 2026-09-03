<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithFreezePane;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Hoja «Pagos» del reporte completo. Es la única razón por la que el
 * completo no puede ser el mismo archivo que el de cliente: aquí vive el
 * dinero.
 *
 * Cero o una fila -un periodo tiene como mucho un pago (D4.2)-, nunca más.
 */
final class HojaPagosExport implements FromArray, WithColumnFormatting, WithEvents, WithFreezePane, WithHeadings, WithTitle
{
    /**
     * @param  array<int, array{fecha: string, monto_usd: float, tasa_cambio: float, monto_cop: float, origen_tasa: string, referencia: string}>  $filas
     */
    public function __construct(private readonly array $filas) {}

    public function title(): string
    {
        return __('reportes.hojas.pagos');
    }

    public function freezePane(): string
    {
        return 'A2';
    }

    public function headings(): array
    {
        return [
            __('reportes.pagos.fecha'),
            __('reportes.pagos.monto_usd'),
            __('reportes.pagos.tasa_cambio'),
            __('reportes.pagos.monto_cop'),
            __('reportes.pagos.origen_tasa'),
            __('reportes.pagos.referencia'),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'B' => '#,##0.00',
            'C' => '#,##0.0000',
            'D' => '#,##0.00',
        ];
    }

    public function array(): array
    {
        return array_map(fn (array $fila): array => [
            FormatoDeHoja::fechaExcel($fila['fecha']),
            $fila['monto_usd'],
            $fila['tasa_cambio'],
            $fila['monto_cop'],
            $fila['origen_tasa'],
            $fila['referencia'],
        ], $this->filas);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $evento): void {
                $sheet = $evento->sheet->getDelegate();
                $ultimaFila = count($this->filas) + 1;

                FormatoDeHoja::bordesNegros($sheet, "A1:F{$ultimaFila}");
                FormatoDeHoja::encabezadoNegrita($sheet, 'A1:F1');
                $sheet->getColumnDimension('A')->setWidth(13);
                $sheet->getColumnDimension('B')->setWidth(16);
                $sheet->getColumnDimension('C')->setWidth(14);
                $sheet->getColumnDimension('D')->setWidth(18);
                FormatoDeHoja::anchoAutomatico($sheet, 'E', __('reportes.pagos.origen_tasa'), array_column($this->filas, 'origen_tasa'), 16);
                FormatoDeHoja::anchoAutomatico($sheet, 'F', __('reportes.pagos.referencia'), array_column($this->filas, 'referencia'), 24);
            },
        ];
    }
}
