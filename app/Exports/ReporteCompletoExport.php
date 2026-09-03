<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Los cuatro hojas del reporte completo, siempre como archivo aparte del de
 * cliente. Nunca las combines en un mismo libro con la hoja de cliente: un
 * archivo que contiene los pagos acaba enviándose por error con un simple
 * reenvío.
 *
 * Implementa `Export` además de `WithMultipleSheets` -esta última no la
 * extiende- porque `Excel::download()` exige el marcador `Export` en su
 * firma.
 */
final class ReporteCompletoExport implements Export, WithMultipleSheets
{
    /**
     * @param  array{resumen: array<int, array<string, mixed>>, tareas: array<int, array<string, mixed>>, notas: array<int, array<string, mixed>>, pagos: array<int, array<string, mixed>>}  $snapshot
     */
    public function __construct(private readonly array $snapshot) {}

    /**
     * @return array<int, Export>
     */
    public function sheets(): array
    {
        return [
            new HojaResumenExport($this->snapshot['resumen']),
            new HojaTareasExport($this->snapshot['tareas']),
            new HojaNotasExport($this->snapshot['notas']),
            new HojaPagosExport($this->snapshot['pagos']),
        ];
    }
}
