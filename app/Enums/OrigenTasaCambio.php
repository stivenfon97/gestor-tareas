<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * De dónde salió la TRM aplicada a un pago. El valor queda siempre editable a
 * mano, así que el origen registra qué se hizo realmente.
 */
enum OrigenTasaCambio: string
{
    case Api = 'api';
    case Manual = 'manual';

    public function etiqueta(): string
    {
        return 'enums.origen_tasa_cambio.'.$this->value;
    }

    public function token(): string
    {
        return 'origen-tasa-'.str_replace('_', '-', $this->value);
    }
}
