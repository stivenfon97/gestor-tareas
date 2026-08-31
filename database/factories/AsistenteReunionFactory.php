<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AsistenteReunion;
use App\Models\Reunion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AsistenteReunion>
 */
class AsistenteReunionFactory extends Factory
{
    protected $model = AsistenteReunion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reunion_id' => Reunion::factory(),
            // Sin usuario: la gente del cliente no es usuaria del sistema.
            'usuario_id' => null,
            'nombre' => fake()->name(),
            'organizacion' => fake()->optional(0.8)->company(),
        ];
    }
}
