<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Quién puede ver una nota, una reunión o un archivo.
 *
 * Todo nace `interna`. La exclusión se aplica **en la consulta**, no ocultando
 * columnas en la interfaz.
 */
enum Visibilidad: string
{
    case Interna = 'interna';
    case Cliente = 'cliente';

    public function etiqueta(): string
    {
        return 'enums.visibilidad.'.$this->value;
    }

    public function token(): string
    {
        return 'visibilidad-'.str_replace('_', '-', $this->value);
    }
}
