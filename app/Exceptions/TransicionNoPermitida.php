<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\EstadoTarea;
use DomainException;

/**
 * Se lanza cuando alguien intenta un cambio de estado que la matriz no permite,
 * o que no trae el motivo que ese estado exige.
 *
 * Vive en el observer y no en el controlador a propósito: así ninguna vía de
 * escritura —comando de consola, acción masiva, seeder— puede saltársela.
 */
final class TransicionNoPermitida extends DomainException
{
    public static function entre(EstadoTarea $desde, EstadoTarea $hacia): self
    {
        return new self(sprintf(
            'La transición de "%s" a "%s" no está permitida.',
            $desde->value,
            $hacia->value,
        ));
    }

    public static function faltaMotivo(EstadoTarea $destino, string $campo): self
    {
        return new self(sprintf(
            'Pasar a "%s" exige "%s".',
            $destino->value,
            $campo,
        ));
    }
}
