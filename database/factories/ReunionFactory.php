<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TipoReunion;
use App\Enums\Visibilidad;
use App\Models\Proyecto;
use App\Models\Reunion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reunion>
 */
class ReunionFactory extends Factory
{
    protected $model = Reunion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $parrafos = [
            fake()->sentence(16),
            fake()->sentence(20),
        ];

        return [
            'proyecto_id' => Proyecto::factory(),
            'fecha' => fake()->dateTimeBetween('-5 months', 'now'),
            'tipo' => fake()->randomElement(TipoReunion::cases()),
            'titulo' => fake()->randomElement([
                'Revisión de avance del periodo',
                'Definición del flujo de facturación',
                'Comité de priorización',
                'Seguimiento de incidencias en producción',
            ]),
            'contenido_json' => self::documentoTipTap($parrafos),
            'contenido_html' => '<p>'.implode('</p><p>', $parrafos).'</p>',
            'acuerdos' => fake()->optional(0.7)->sentence(12),
            'visibilidad' => Visibilidad::Interna,
            'creado_por' => User::factory(),
        ];
    }

    public function visibleParaCliente(): static
    {
        return $this->state(fn (array $attributes): array => [
            'visibilidad' => Visibilidad::Cliente,
        ]);
    }

    /**
     * Documento TipTap mínimo pero válido: `doc` con párrafos de texto.
     *
     * @param  array<int, string>  $parrafos
     * @return array{type: string, content: array<int, array<string, mixed>>}
     */
    public static function documentoTipTap(array $parrafos): array
    {
        return [
            'type' => 'doc',
            'content' => array_map(fn (string $texto): array => [
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => $texto]],
            ], $parrafos),
        ];
    }
}
