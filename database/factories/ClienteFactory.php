<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombre = fake()->randomElement([
            'Distribuidora El Roble', 'Inversiones Andina', 'Clínica Santa Marta',
            'Transportes Guaviare', 'Textiles Bonanza', 'Agrícola La Esperanza',
        ]).' '.fake()->randomElement(['S.A.S.', 'Ltda.', 'S.A.']);

        return [
            'nombre' => $nombre,
            'contacto_nombre' => fake()->name(),
            'contacto_email' => fake()->unique()->companyEmail(),
            'contacto_telefono' => '+57 3'.fake()->numerify('## ### ####'),
            'notas' => fake()->optional(0.4)->sentence(12),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes): array => ['activo' => false]);
    }
}
