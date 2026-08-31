<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Equipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipo>
 */
class EquipoFactory extends Factory
{
    protected $model = Equipo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->randomElement([
                'Desarrollo', 'Soporte', 'Diseño', 'Infraestructura', 'Dirección',
            ]),
            'descripcion' => fake()->optional(0.6)->sentence(8),
        ];
    }
}
