<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstadoPeriodo;
use App\Models\Periodo;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Periodo>
 */
class PeriodoFactory extends Factory
{
    protected $model = Periodo::class;

    /** El consecutivo es único por proyecto; una secuencia propia basta y no se agota. */
    protected static int $secuencia = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicio = Carbon::instance(fake()->dateTimeBetween('-8 months', '-1 month'));

        return [
            'proyecto_id' => Proyecto::factory(),
            'consecutivo' => ++self::$secuencia,
            'fecha_inicio' => $inicio,
            // Un periodo abierto no tiene fecha de fin: se llena al cerrar.
            'fecha_fin' => null,
            'estado' => EstadoPeriodo::Abierto,
            'notas' => fake()->optional(0.3)->sentence(10),
        ];
    }

    public function cerrado(): static
    {
        return $this->state(function (array $attributes): array {
            $inicio = Carbon::parse($attributes['fecha_inicio']);

            return [
                'fecha_fin' => $inicio->copy()->addDays(fake()->numberBetween(20, 45)),
                'estado' => EstadoPeriodo::Cerrado,
            ];
        });
    }

    /**
     * Abierto pero con la fecha de fin prevista ya pasada. No es un error: es un
     * estado válido y frecuente.
     */
    public function vencido(): static
    {
        return $this->state(fn (array $attributes): array => [
            'fecha_fin' => Carbon::parse($attributes['fecha_inicio'])->copy()->addDays(30),
            'estado' => EstadoPeriodo::Abierto,
        ]);
    }
}
