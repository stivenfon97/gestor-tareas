<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Situación de un proyecto. No condiciona el ciclo de vida de sus tareas.
 */
enum EstadoProyecto: string
{
    case Activo = 'activo';
    case Pausado = 'pausado';
    case Finalizado = 'finalizado';

    public function etiqueta(): string
    {
        return 'enums.estado_proyecto.'.$this->value;
    }

    public function token(): string
    {
        return 'estado-proyecto-'.str_replace('_', '-', $this->value);
    }
}
