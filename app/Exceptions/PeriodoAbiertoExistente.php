<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Proyecto;
use DomainException;

/**
 * Se lanza al intentar abrir un periodo en un proyecto que ya tiene uno
 * `abierto` (D4.8).
 *
 * `AbrirPeriodo` la lanza **dentro** de la transacción, después de bloquear la
 * fila del proyecto: es la red que atrapa la carrera que el `unique` de
 * `2026_09_02_120000_garantizar_un_periodo_abierto_por_proyecto` cerraría de
 * todos modos, pero con un mensaje legible en lugar de una excepción de la
 * base de datos.
 */
final class PeriodoAbiertoExistente extends DomainException
{
    public static function paraProyecto(Proyecto $proyecto): self
    {
        return new self(sprintf(
            'El proyecto "%s" ya tiene un periodo abierto.',
            $proyecto->nombre,
        ));
    }
}
