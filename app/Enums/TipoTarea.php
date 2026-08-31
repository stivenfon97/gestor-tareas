<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Naturaleza del trabajo de la tarea. Se fija al crear y no cambia.
 */
enum TipoTarea: string
{
    case Desarrollo = 'desarrollo';
    case Correccion = 'correccion';
    case Soporte = 'soporte';
    case Mejora = 'mejora';
    case Investigacion = 'investigacion';

    public function etiqueta(): string
    {
        return 'enums.tipo_tarea.'.$this->value;
    }

    public function token(): string
    {
        return 'tipo-'.str_replace('_', '-', $this->value);
    }

    /**
     * Prioridad con la que se precarga el formulario. Es un valor por defecto,
     * no una restricción: sigue siendo editable.
     */
    public function prioridadPorDefecto(): PrioridadTarea
    {
        return match ($this) {
            self::Correccion, self::Soporte => PrioridadTarea::Alta,
            default => PrioridadTarea::Media,
        };
    }
}
