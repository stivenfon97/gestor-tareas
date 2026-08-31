<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Modulo;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Modulo>
 */
class ModuloFactory extends Factory
{
    protected $model = Modulo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proyecto_id' => Proyecto::factory(),
            'parent_id' => null,
            'nombre' => fake()->randomElement([
                'Clientes', 'Facturación', 'Reportes', 'Usuarios y permisos',
                'Inventario', 'Notificaciones', 'Configuración', 'Auditoría',
            ]),
            'descripcion' => fake()->optional(0.5)->sentence(10),
            'orden' => fake()->numberBetween(0, 20),
        ];
    }

    /**
     * Submódulo colgado de otro módulo del mismo proyecto.
     */
    public function hijoDe(Modulo $padre): static
    {
        return $this->state(fn (array $attributes): array => [
            'proyecto_id' => $padre->proyecto_id,
            'parent_id' => $padre->id,
        ]);
    }
}
