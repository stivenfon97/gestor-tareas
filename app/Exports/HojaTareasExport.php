<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\EstadoTarea;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithFreezePane;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Hoja «Tareas» del reporte completo: todo lo trabajado en el periodo,
 * canceladas incluidas -el periodo se facturó por el trabajo hecho, y el
 * trabajo descartado también consumió tiempo.
 *
 * La columna «Notas» es un resumen condensado -las notas de la tarea
 * separadas por «·»-, no el detalle. Ese vive en la hoja «Notas», con fecha y
 * autor de cada una: son dos granularidades del mismo dato, no una
 * repetición.
 */
final class HojaTareasExport implements FromArray, WithColumnFormatting, WithEvents, WithFreezePane, WithHeadings, WithTitle
{
    private const COL_ESTADO = 'E';

    private const COL_DESCRIPCION = 'G';

    private const COL_FECHA_INICIO = 'H';

    private const COL_FECHA_FIN = 'I';

    private const COL_NOTAS = 'K';

    /**
     * @param  array<int, array{codigo: string, tipo: string, modulo: string, tarea: string, estado: string, estado_etiqueta: string, prioridad: string, descripcion: string, fecha_inicio: ?string, fecha_fin: ?string, arrastrada: string, notas: string}>  $filas
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
        ];
    }

    public function columnFormats(): array
    {
        return [
            self::COL_FECHA_INICIO => NumberFormat::FORMAT_DATE_YYYYMMDD,
            self::COL_FECHA_FIN => NumberFormat::FORMAT_DATE_YYYYMMDD,
        ];
    }

    public function array(): array
    {
        return array_map(fn (array $fila): array => [
            $fila['codigo'],
            $fila['tipo'],
            $fila['modulo'],
            $fila['tarea'],
            $fila['estado_etiqueta'],
            $fila['prioridad'],
            $fila['descripcion'],
            FormatoDeHoja::fechaExcel($fila['fecha_inicio']),
            FormatoDeHoja::fechaExcel($fila['fecha_fin']),
            $fila['arrastrada'],
            $fila['notas'],
        ], $this->filas);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $evento): void {
                $sheet = $evento->sheet->getDelegate();
                $ultimaFila = count($this->filas) + 1;

                FormatoDeHoja::bordesNegros($sheet, "A1:K{$ultimaFila}");
                FormatoDeHoja::encabezadoNegrita($sheet, 'A1:K1');

                FormatoDeHoja::anchoAutomatico($sheet, 'A', __('reportes.tareas.codigo'), array_column($this->filas, 'codigo'), 14);
                FormatoDeHoja::anchoAutomatico($sheet, 'B', __('reportes.tareas.tipo'), array_column($this->filas, 'tipo'), 16);
                FormatoDeHoja::anchoAutomatico($sheet, 'C', __('reportes.tareas.modulo'), array_column($this->filas, 'modulo'), 30);
                FormatoDeHoja::anchoAutomatico($sheet, 'D', __('reportes.tareas.tarea'), array_column($this->filas, 'tarea'), 40);
                FormatoDeHoja::anchoAutomatico($sheet, self::COL_ESTADO, __('reportes.tareas.estado'), array_column($this->filas, 'estado_etiqueta'), 16);
                FormatoDeHoja::anchoAutomatico($sheet, 'F', __('reportes.tareas.prioridad'), array_column($this->filas, 'prioridad'), 12);
                FormatoDeHoja::columnaEnvuelta($sheet, self::COL_DESCRIPCION, 50);
                $sheet->getColumnDimension(self::COL_FECHA_INICIO)->setWidth(13);
                $sheet->getColumnDimension(self::COL_FECHA_FIN)->setWidth(13);
                FormatoDeHoja::anchoAutomatico($sheet, 'J', __('reportes.tareas.arrastrada'), array_column($this->filas, 'arrastrada'), 12);
                FormatoDeHoja::columnaEnvuelta($sheet, self::COL_NOTAS, 40);

                foreach ($this->filas as $indice => $fila) {
                    $estado = EstadoTarea::from($fila['estado']);
                    FormatoDeHoja::rellenoDeEstado($sheet, self::COL_ESTADO.($indice + 2), PaletaEstadoExcel::relleno($estado));
                }
            },
        ];
    }
}
