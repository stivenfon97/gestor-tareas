<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrigenTasaCambio;
use App\Models\Pago;
use App\Models\Periodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    protected $model = Pago::class;

    /**
     * `monto_cop` no se define aquí: lo calcula `PagoObserver` a partir del
     * monto en dólares y la tasa.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'periodo_id' => Periodo::factory(),
            'fecha_pago' => fake()->dateTimeBetween('-6 months', 'now'),
            'monto_usd' => fake()->randomFloat(2, 800, 4500),
            'tasa_cambio' => fake()->randomFloat(4, 3850, 4350),
            'origen_tasa' => OrigenTasaCambio::Api,
            'referencia' => fake()->optional(0.7)->bothify('TRF-####-????'),
            'notas' => fake()->optional(0.2)->sentence(8),
        ];
    }

    public function conTasaManual(): static
    {
        return $this->state(fn (array $attributes): array => [
            'origen_tasa' => OrigenTasaCambio::Manual,
        ]);
    }
}
