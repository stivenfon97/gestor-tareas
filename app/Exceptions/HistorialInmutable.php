<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

/**
 * Se lanza al intentar modificar o borrar una fila de `historial_cambios`.
 *
 * Un historial que se puede corregir no prueba nada: si un dato se registró
 * mal, se añade otra fila, nunca se reescribe la anterior.
 */
final class HistorialInmutable extends DomainException
{
    public static function alModificar(): self
    {
        return new self('El historial de cambios es de solo escritura: no se modifica ni se borra.');
    }
}
