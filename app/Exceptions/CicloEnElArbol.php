<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

/**
 * Se lanza al intentar colgar un nodo dentro de uno de sus propios
 * descendientes.
 *
 * El resultado sería una rama desenganchada del resto del árbol y una recursión
 * infinita en el componente que lo pinta. La comprobación vive en el servidor
 * porque el arrastre de la interfaz no es el único camino hasta aquí.
 */
final class CicloEnElArbol extends DomainException
{
    public static function en(string $modelo, int $id): self
    {
        return new self(sprintf(
            'Mover %s#%d ahí crearía un ciclo: un nodo no puede colgar de uno de sus descendientes.',
            class_basename($modelo),
            $id,
        ));
    }
}
