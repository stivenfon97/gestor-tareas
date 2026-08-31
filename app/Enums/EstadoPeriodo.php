<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Situación de un periodo facturable. El cierre es siempre manual: el sistema
 * nunca cierra un periodo solo al llegar la fecha de fin.
 */
enum EstadoPeriodo: string
{
    case Abierto = 'abierto';
    case Cerrado = 'cerrado';

    public function etiqueta(): string
    {
        return 'enums.estado_periodo.'.$this->value;
    }

    public function token(): string
    {
        return 'estado-periodo-'.str_replace('_', '-', $this->value);
    }
}
