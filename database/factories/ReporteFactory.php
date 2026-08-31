<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstadoTarea;
use App\Enums\TipoReporte;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Reporte>
 */
class ReporteFactory extends Factory
{
    protected $model = Reporte::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $snapshot = [[
            'codigo' => 'DEM-0001',
            'nombre' => 'Exportar el listado de clientes a Excel',
            'estado' => EstadoTarea::Entregada->value,
            'modulo' => 'Clientes',
        ]];

        return [
            'proyecto_id' => Proyecto::factory(),
            'periodo_id' => Periodo::factory(),
            'tipo' => TipoReporte::Completo,
            'nombre_archivo' => 'DEM_P1_2026-08-26_completo.xlsx',
            'filtros' => ['estado' => null, 'modulo_id' => null],
            'resumen_estados' => [EstadoTarea::Entregada->value => 1],
            'snapshot' => $snapshot,
            'total_tareas' => count($snapshot),
            'hash' => sha1(json_encode($snapshot, JSON_THROW_ON_ERROR)),
            'generado_por' => User::factory(),
            'generado_en' => Carbon::now(),
        ];
    }

    public function deCliente(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tipo' => TipoReporte::Cliente,
            'nombre_archivo' => str_replace('_completo.', '_cliente.', (string) $attributes['nombre_archivo']),
        ]);
    }
}
