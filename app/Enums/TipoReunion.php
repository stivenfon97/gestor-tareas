<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Naturaleza de un encuentro registrado en la bitácora.
 */
enum TipoReunion: string
{
    case Reunion = 'reunion';
    case Llamada = 'llamada';
    case Correo = 'correo';
    case Mensaje = 'mensaje';

    public function etiqueta(): string
    {
        return 'enums.tipo_reunion.'.$this->value;
    }

    public function token(): string
    {
        return 'tipo-reunion-'.str_replace('_', '-', $this->value);
    }
}
