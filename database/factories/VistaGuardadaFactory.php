<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\VistaGuardada;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VistaGuardada>
 */
class VistaGuardadaFactory extends Factory
{
    protected $model = VistaGuardada::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'proyecto_id' => null,
            'nombre' => fake()->randomElement([
                'Mis pendientes', 'Bloqueadas', 'Del periodo actual', 'Correcciones abiertas',
            ]),
            'tipo' => fake()->randomElement(['tabla', 'kanban', 'lista', 'calendario']),
            'configuracion' => [
                'filtros' => ['estado' => ['pendiente', 'en_proceso']],
                'agrupacion' => 'modulo_id',
                'columnas' => ['codigo', 'nombre', 'estado', 'prioridad'],
            ],
            'es_predeterminada' => false,
        ];
    }

    public function predeterminada(): static
    {
        return $this->state(fn (array $attributes): array => ['es_predeterminada' => true]);
    }
}
