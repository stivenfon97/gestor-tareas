<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Visibilidad;
use App\Models\NotaTarea;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotaTarea>
 */
class NotaTareaFactory extends Factory
{
    protected $model = NotaTarea::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tarea_id' => Tarea::factory(),
            'usuario_id' => User::factory(),
            'contenido' => fake()->randomElement([
                'Reproducido en el ambiente de pruebas con el cliente 1043.',
                'Queda pendiente confirmar el formato de la fecha con contabilidad.',
                'Se desplegó en pruebas; falta la validación del cliente.',
                'El error solo aparece cuando el usuario no tiene proyecto asignado.',
            ]),
            // Todo nace interno.
            'visibilidad' => Visibilidad::Interna,
        ];
    }

    public function visibleParaCliente(): static
    {
        return $this->state(fn (array $attributes): array => [
            'visibilidad' => Visibilidad::Cliente,
        ]);
    }
}
