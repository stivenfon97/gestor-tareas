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
 * Hoja «Notas» del reporte completo: el detalle fecha a fecha, con autor, de
 * las notas de todas las tareas del periodo. Complementa el resumen
 * condensado de la columna «Notas» en la hoja «Tareas».
 */
final class HojaNotasExport implements FromArray, WithColumnFormatting, WithEvents, WithFreezePane, WithHeadings, WithTitle
{
    /**
     * @param  array<int, array{codigo: string, fecha: ?string, autor: string, nota: string}>  $filas
     */
    public function __construct(private readonly array $filas) {}

    public function title(): string
    {
        return __('reportes.hojas.notas');
    }

    public function freezePane(): string
    {
        return 'A2';
    }

    public function headings(): array
    {
        return [
            __('reportes.notas.codigo'),
            __('reportes.notas.fecha'),
            __('reportes.notas.autor'),
            __('reportes.notas.nota'),
        ];
    }

    public function columnFormats(): array
    {
        return ['B' => NumberFormat::FORMAT_DATE_YYYYMMDD];
    }

    public function array(): array
    {
        return array_map(fn (array $fila): array => [
            $fila['codigo'],
            FormatoDeHoja::fechaExcel($fila['fecha']),
            $fila['autor'],
            $fila['nota'],
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
                FormatoDeHoja::anchoAutomatico($sheet, 'A', __('reportes.notas.codigo'), array_column($this->filas, 'codigo'), 14);
                $sheet->getColumnDimension('B')->setWidth(13);
                FormatoDeHoja::anchoAutomatico($sheet, 'C', __('reportes.notas.autor'), array_column($this->filas, 'autor'), 24);
                FormatoDeHoja::columnaEnvuelta($sheet, 'D', 55);
            },
        ];
    }
}
