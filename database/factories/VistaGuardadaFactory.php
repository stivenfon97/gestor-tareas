<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TipoDeVista;
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
            'nombre' => ucfirst($this->faker->words(2, true)),
            'tipo' => TipoDeVista::Tabla->value,
            'configuracion' => ['filter' => ['estado' => 'pendiente']],
            'es_predeterminada' => false,
        ];
    }

    public function predeterminada(): static
    {
        return $this->state(fn (): array => ['es_predeterminada' => true]);
    }

    public function deTipo(TipoDeVista $tipo): static
    {
        return $this->state(fn (): array => ['tipo' => $tipo->value]);
    }
}
