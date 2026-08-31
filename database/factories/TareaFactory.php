<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Enums\TipoTarea;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tarea>
 */
class TareaFactory extends Factory
{
    protected $model = Tarea::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tipo = fake()->randomElement(TipoTarea::cases());

        return [
            'proyecto_id' => Proyecto::factory(),
            'modulo_id' => null,
            'periodo_id' => null,
            'periodo_origen_id' => null,
            'nombre' => $this->tituloRealista($tipo),
            'descripcion' => fake()->paragraph(3),
            'estado' => EstadoTarea::Pendiente,
            'tipo' => $tipo,
            'prioridad' => $tipo->prioridadPorDefecto(),
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'orden' => fake()->numberBetween(0, 50),
            'creado_por' => User::factory(),
        ];
    }

    public function enProceso(): static
    {
        return $this->state(fn (array $attributes): array => [
            'estado' => EstadoTarea::EnProceso,
            'fecha_inicio' => fake()->dateTimeBetween('-3 weeks', '-2 days'),
        ]);
    }

    public function enEspera(?string $motivo = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'estado' => EstadoTarea::EnEspera,
            'motivo_espera' => $motivo ?? fake()->randomElement([
                'El cliente no ha entregado las credenciales del servidor de correo.',
                'Falta la definición de los campos obligatorios de la factura.',
                'Pendiente de que el proveedor habilite el ambiente de pruebas.',
                'No hay ejemplo del archivo que hay que importar.',
            ]),
            'fecha_inicio' => fake()->dateTimeBetween('-4 weeks', '-1 week'),
        ]);
    }

    public function terminada(): static
    {
        return $this->state(fn (array $attributes): array => [
            'estado' => EstadoTarea::Terminada,
            'fecha_inicio' => fake()->dateTimeBetween('-6 weeks', '-3 weeks'),
            'fecha_fin' => fake()->dateTimeBetween('-2 weeks', 'now'),
        ]);
    }

    public function entregada(): static
    {
        return $this->state(fn (array $attributes): array => [
            'estado' => EstadoTarea::Entregada,
            'fecha_inicio' => fake()->dateTimeBetween('-6 months', '-4 months'),
            'fecha_fin' => fake()->dateTimeBetween('-4 months', '-2 months'),
        ]);
    }

    public function cancelada(?string $motivo = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'estado' => EstadoTarea::Cancelada,
            'motivo_cancelacion' => $motivo ?? fake()->randomElement([
                'El cliente desistió del módulo en la reunión del comité.',
                'Se resolvió por configuración; no requiere desarrollo.',
                'Duplicada: el trabajo ya estaba cubierto por otra tarea.',
            ]),
        ]);
    }

    /**
     * Tarea no terminada que pasó al siguiente periodo. Conserva
     * `periodo_origen_id`.
     */
    public function arrastrada(?Periodo $origen = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'periodo_origen_id' => $origen?->id ?? Periodo::factory(),
        ]);
    }

    public function deCorreccion(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tipo' => TipoTarea::Correccion,
            'prioridad' => TipoTarea::Correccion->prioridadPorDefecto(),
            'nombre' => $this->tituloRealista(TipoTarea::Correccion),
        ]);
    }

    public function deSoporte(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tipo' => TipoTarea::Soporte,
            'prioridad' => TipoTarea::Soporte->prioridadPorDefecto(),
            'nombre' => $this->tituloRealista(TipoTarea::Soporte),
        ]);
    }

    public function critica(): static
    {
        return $this->state(fn (array $attributes): array => [
            'prioridad' => PrioridadTarea::Critica,
        ]);
    }

    /**
     * Títulos que se parecen a los de un tablero real: un `lorem ipsum` esconde
     * los problemas de interfaz que un título largo y concreto revela.
     */
    protected function tituloRealista(TipoTarea $tipo): string
    {
        $titulos = match ($tipo) {
            TipoTarea::Desarrollo => [
                'Pantalla de creación de facturas con líneas de detalle',
                'Exportar el listado de clientes a Excel',
                'Filtro por rango de fechas en el tablero',
                'Importación masiva de productos desde CSV',
                'Autenticación con doble factor para administradores',
                'Historial de cambios por documento',
            ],
            TipoTarea::Correccion => [
                'El total de la factura no cuadra cuando hay descuento por línea',
                'El filtro de fechas ignora el último día del rango',
                'La exportación se cae si el cliente no tiene NIT',
                'El PDF sale sin logo en Safari',
            ],
            TipoTarea::Soporte => [
                'Restaurar el respaldo del 12 de marzo en el ambiente de pruebas',
                'Un usuario no puede entrar tras el cambio de contraseña',
                'Revisar por qué no llegan los correos de notificación',
            ],
            TipoTarea::Mejora => [
                'Recordar los filtros del listado entre sesiones',
                'Acelerar la carga del tablero con más de 5.000 tareas',
                'Permitir reordenar columnas arrastrando',
            ],
            TipoTarea::Investigacion => [
                'Evaluar si conviene migrar los adjuntos a almacenamiento externo',
                'Comparar pasarelas de pago para la facturación en línea',
            ],
        };

        return fake()->randomElement($titulos);
    }
}
