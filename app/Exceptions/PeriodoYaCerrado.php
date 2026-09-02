<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Periodo;
use DomainException;

/**
 * Se lanza al intentar cerrar un periodo que ya está `cerrado`, o al intentar
 * asignarle o trasladarle tareas.
 *
 * El cierre es siempre manual (D4.3) y un periodo cerrado no admite tareas
 * nuevas ni traslados hacia él (D4.9): esta excepción es la cerradura de las
 * dos cosas.
 */
final class PeriodoYaCerrado extends DomainException
{
    public static function alCerrar(Periodo $periodo): self
    {
        return new self(sprintf(
            'El periodo %d del proyecto %d ya está cerrado.',
            $periodo->consecutivo,
            $periodo->proyecto_id,
        ));
    }
}
