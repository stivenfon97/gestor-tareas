<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\EventoHistorial;
use App\Models\Proyecto;
use App\Models\ProyectoUsuario;
use App\Services\RegistrarHistorial;

/**
 * Registra en el historial **del proyecto** las altas, cambios de rol y bajas
 * de su membresía.
 *
 * Funciona porque `Proyecto::usuarios()` y `User::proyectos()` declaran
 * `->using(ProyectoUsuario::class)`: con un pivote propio, `attach`,
 * `updateExistingPivot` y `detach` crean, guardan y borran instancias del
 * modelo y disparan sus eventos. Sin `using()` irían por query builder y esto
 * no se enteraría.
 */
class MiembroDeProyectoObserver
{
    public function __construct(private readonly RegistrarHistorial $registrar) {}

    public function created(ProyectoUsuario $miembro): void
    {
        $this->escribir($miembro, EventoHistorial::MiembroAgregado, null, $this->valores($miembro));
    }

    public function updated(ProyectoUsuario $miembro): void
    {
        if (! $miembro->wasChanged('rol')) {
            return;
        }

        $this->escribir(
            $miembro,
            EventoHistorial::MiembroActualizado,
            ['usuario_id' => $miembro->usuario_id, 'rol' => $miembro->getPrevious()['rol'] ?? null],
            $this->valores($miembro),
        );
    }

    public function deleted(ProyectoUsuario $miembro): void
    {
        $this->escribir($miembro, EventoHistorial::MiembroRetirado, $this->valores($miembro), null);
    }

    /**
     * El rol en crudo (el `value`), no el enum del cast: así se guarda todo
     * valor del historial.
     *
     * @return array{usuario_id: int, rol: mixed}
     */
    private function valores(ProyectoUsuario $miembro): array
    {
        return [
            'usuario_id' => $miembro->usuario_id,
            'rol' => $miembro->getAttributes()['rol'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $anteriores
     * @param  array<string, mixed>|null  $nuevos
     */
    private function escribir(ProyectoUsuario $miembro, EventoHistorial $evento, ?array $anteriores, ?array $nuevos): void
    {
        $proyecto = Proyecto::withTrashed()->find($miembro->proyecto_id);

        if ($proyecto === null) {
            return;
        }

        $this->registrar->escribir($proyecto, $evento, ['usuario_id', 'rol'], $anteriores, $nuevos);
    }
}
