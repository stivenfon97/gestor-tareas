<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\TipoTarea;
use DomainException;

/**
 * Se lanza cuando `tarea_origen_id` apunta a donde no debe.
 *
 * Vive en el observer y no solo en el formulario a propósito: el vínculo entre
 * una entrega y la corrección que generó es lo que hace auditable la regla D2.6,
 * y un vínculo torcido —a otro proyecto, a sí misma, en cadena circular— la
 * vuelve inútil justo cuando alguien la consulta.
 */
final class VinculoDeOrigenInvalido extends DomainException
{
    public static function tipoNoAdmiteOrigen(TipoTarea $tipo): self
    {
        return new self(sprintf(
            'Una tarea de tipo "%s" no puede tener tarea de origen: solo la tienen "correccion" y "soporte".',
            $tipo->value,
        ));
    }

    public static function deOtroProyecto(): self
    {
        return new self('La tarea de origen tiene que pertenecer al mismo proyecto.');
    }

    public static function aSiMisma(): self
    {
        return new self('Una tarea no puede ser su propio origen.');
    }

    public static function cadenaCircular(): self
    {
        return new self('El vínculo de origen crearía una cadena circular.');
    }
}
