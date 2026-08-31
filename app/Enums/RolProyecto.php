<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Rol de un usuario dentro de un proyecto concreto.
 *
 * Se resuelve **siempre desde el pivote `proyecto_usuario`**, nunca desde el
 * equipo: el equipo es solo un atajo de asignación masiva.
 */
enum RolProyecto: string
{
    case Lider = 'lider';
    case Colaborador = 'colaborador';
    case Observador = 'observador';
    case Cliente = 'cliente';

    public function etiqueta(): string
    {
        return 'enums.rol_proyecto.'.$this->value;
    }

    public function token(): string
    {
        return 'rol-'.str_replace('_', '-', $this->value);
    }

    /**
     * Solo el líder administra el proyecto en sí: módulos, periodos, pagos y
     * cierre.
     */
    public function gestionaElProyecto(): bool
    {
        return $this === self::Lider;
    }

    /**
     * Quién puede crear y mover trabajo dentro del proyecto.
     */
    public function escribeTrabajo(): bool
    {
        return match ($this) {
            self::Lider, self::Colaborador => true,
            self::Observador, self::Cliente => false,
        };
    }

    /**
     * Quién ve lo interno. El cliente solo ve lo marcado
     * `Visibilidad::Cliente`, y nunca los pagos.
     */
    public function veLoInterno(): bool
    {
        return $this !== self::Cliente;
    }
}
