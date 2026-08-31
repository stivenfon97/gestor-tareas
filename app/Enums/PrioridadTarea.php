<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Urgencia relativa de una tarea. Cambia libremente con el tiempo.
 */
enum PrioridadTarea: string
{
    case Baja = 'baja';
    case Media = 'media';
    case Alta = 'alta';
    case Critica = 'critica';

    public function etiqueta(): string
    {
        return 'enums.prioridad_tarea.'.$this->value;
    }

    public function token(): string
    {
        return 'prioridad-'.str_replace('_', '-', $this->value);
    }

    /**
     * Orden de mayor a menor urgencia, para ordenar listados.
     */
    public function peso(): int
    {
        return match ($this) {
            self::Critica => 4,
            self::Alta => 3,
            self::Media => 2,
            self::Baja => 1,
        };
    }
}
