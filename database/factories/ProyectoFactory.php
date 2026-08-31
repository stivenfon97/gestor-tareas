<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstadoProyecto;
use App\Models\Cliente;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Proyecto>
 */
class ProyectoFactory extends Factory
{
    protected $model = Proyecto::class;

    /**
     * Secuencia propia en lugar de `fake()->unique()`: el catálogo de nombres
     * es corto y `unique()` sobre `randomElement` se queda sin valores en
     * cuanto una prueba crea siete proyectos.
     */
    protected static int $secuencia = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $secuencia = ++self::$secuencia;

        $nombre = fake()->randomElement([
            'Portal de Clientes', 'Gestor de Inventario', 'Facturación Electrónica',
            'Intranet Corporativa', 'App de Domicilios', 'Tablero de Indicadores',
        ]).' '.$secuencia;

        return [
            'cliente_id' => Cliente::factory(),
            'nombre' => $nombre,
            'slug' => Str::slug($nombre),
            'prefijo_tarea' => 'PR'.str_pad((string) $secuencia, 4, '0', STR_PAD_LEFT),
            'descripcion' => fake()->sentence(14),
            'estado' => EstadoProyecto::Activo,
        ];
    }

    public function pausado(): static
    {
        return $this->state(fn (array $attributes): array => ['estado' => EstadoProyecto::Pausado]);
    }

    public function finalizado(): static
    {
        return $this->state(fn (array $attributes): array => ['estado' => EstadoProyecto::Finalizado]);
    }
}
