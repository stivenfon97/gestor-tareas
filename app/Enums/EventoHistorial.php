<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Qué le pasó a un registro en una fila de `historial_cambios`.
 *
 * Los tres de miembro existen porque la membresía de un proyecto vive en el
 * pivote `proyecto_usuario` y se registra sobre el proyecto, no sobre una
 * fila del pivote que nadie va a abrir.
 */
enum EventoHistorial: string
{
    case Creado = 'creado';
    case Actualizado = 'actualizado';
    case Eliminado = 'eliminado';
    case Restaurado = 'restaurado';
    case MiembroAgregado = 'miembro_agregado';
    case MiembroActualizado = 'miembro_actualizado';
    case MiembroRetirado = 'miembro_retirado';

    public function etiqueta(): string
    {
        return 'enums.evento_historial.'.$this->value;
    }

    public function token(): string
    {
        return 'evento-historial-'.str_replace('_', '-', $this->value);
    }
}
