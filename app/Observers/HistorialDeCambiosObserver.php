<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\RegistrarHistorial;
use Illuminate\Database\Eloquent\Model;

/**
 * Observer genérico del historial de cambios. Lo registra el trait
 * `TieneHistorialDeCambios` en cada modelo auditado.
 *
 * Escribe en los eventos en pasado (`created`, `updated`…), no en los de
 * presente: el guardado aún puede fallar después de `updating` (interbloqueo,
 * restricción) y quedaría registrado un cambio que nunca ocurrió. Por la misma
 * razón, dentro de una transacción la fila del historial cae con el rollback.
 */
class HistorialDeCambiosObserver
{
    public function __construct(private readonly RegistrarHistorial $registrar) {}

    public function created(Model $registro): void
    {
        $this->registrar->creado($registro);
    }

    public function updated(Model $registro): void
    {
        $this->registrar->actualizado($registro);
    }

    public function deleted(Model $registro): void
    {
        $this->registrar->eliminado($registro);
    }

    public function restored(Model $registro): void
    {
        $this->registrar->restaurado($registro);
    }
}
