<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Proyecto;
use DomainException;

/**
 * Se lanza al intentar cambiar `prefijo_tarea` de un proyecto que ya emitió
 * códigos de tarea.
 *
 * Vive en el observer y no en el formulario a propósito: así ninguna vía de
 * escritura —comando de consola, acción masiva, seeder, `tinker`— puede
 * saltársela. Un código como `MAQ-0042` ya viajó al cliente dentro de un
 * reporte; renombrar el prefijo lo dejaría sin correspondencia con nada.
 */
final class PrefijoInmutable extends DomainException
{
    public static function paraProyecto(Proyecto $proyecto, string $nuevo): self
    {
        return new self(sprintf(
            'El prefijo de "%s" no se puede cambiar de "%s" a "%s": ya hay códigos de tarea emitidos.',
            $proyecto->nombre,
            (string) $proyecto->getOriginal('prefijo_tarea'),
            $nuevo,
        ));
    }
}
