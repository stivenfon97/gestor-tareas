<?php

declare(strict_types=1);

namespace App\Exports;

use DateTimeImmutable;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Formato compartido por las cinco hojas de los dos reportes: cuadrícula
 * negra, encabezado en negrita, columnas envueltas con ancho acotado y ancho
 * automático con tope. Centralizado para no repetirlo hoja por hoja.
 */
final class FormatoDeHoja
{
    public static function bordesNegros(Worksheet $sheet, string $rango): void
    {
        $sheet->getStyle($rango)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }

    public static function encabezadoNegrita(Worksheet $sheet, string $rango): void
    {
        $sheet->getStyle($rango)->applyFromArray(['font' => ['bold' => true]]);
    }

    /**
     * Ancho fijo con salto de línea. Sin tope, una descripción de 400
     * caracteres estira la columna hasta el infinito; con `wrapText` y un
     * ancho fijo, crece hacia abajo en vez de hacia el lado.
     */
    public static function columnaEnvuelta(Worksheet $sheet, string $columna, int $ancho): void
    {
        $sheet->getColumnDimension($columna)->setWidth($ancho);
        $sheet->getStyle($columna)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    }

    /**
     * Ancho proporcional al valor más largo de la columna (encabezado
     * incluido), acotado a `$maximo`.
     *
     * No usa `ShouldAutoSize`: ese cálculo lo hace el escritor de PhpSpreadsheet
     * al guardar el archivo y no admite un tope, así que una columna corta con
     * un único valor largo se estira igual que una descripción sin
     * `wrapText`. Se calcula aquí, con los mismos valores que se escriben.
     *
     * @param  array<int, string>  $valores
     */
    public static function anchoAutomatico(Worksheet $sheet, string $columna, string $encabezado, array $valores, int $maximo = 32): void
    {
        $masLargo = array_reduce(
            $valores,
            fn (int $largo, string $valor): int => max($largo, mb_strlen($valor)),
            mb_strlen($encabezado),
        );

        $sheet->getColumnDimension($columna)->setWidth(min($masLargo + 2, $maximo));
    }

    /**
     * Relleno sólido pálido con texto negro. En Excel no hay modo oscuro: un
     * relleno pálido con texto claro es ilegible.
     */
    public static function rellenoDeEstado(Worksheet $sheet, string $celda, string $hex): void
    {
        $sheet->getStyle($celda)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $hex],
            ],
            'font' => ['color' => ['rgb' => '000000']],
        ]);
    }

    /**
     * Convierte una fecha ISO (`toDateString()`) al número de serie que Excel
     * necesita para tratarla como fecha real. Escribir el objeto `Carbon`
     * directo no basta: el binder de valores de maatwebsite/excel lo formatea
     * primero a cadena (`Y-m-d H:i:s`) y esa cadena se guarda como texto, no
     * como fecha -exactamente lo que este método evita.
     */
    public static function fechaExcel(?string $fechaIso): float|string
    {
        if ($fechaIso === null || $fechaIso === '') {
            return '';
        }

        return Date::PHPToExcel(new DateTimeImmutable($fechaIso));
    }
}
