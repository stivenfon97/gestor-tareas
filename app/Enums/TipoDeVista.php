<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Las cuatro formas de ver las mismas tareas.
 *
 * Como `AgrupacionTablero`, es un eje de lectura y no un valor del dominio: no
 * lo pinta ningún chip de color, así que no expone `token()`. Devolver uno
 * inventado sería un token que nadie usa y que el tema tendría que mantener.
 *
 * Es el `tipo` de una vista guardada, y **una vista solo se aplica a su tipo**:
 * una configuración de tabla lleva columnas y tamaño de página, que en un
 * tablero no significan nada.
 */
enum TipoDeVista: string
{
    case Tabla = 'tabla';
    case Kanban = 'kanban';
    case Lista = 'lista';
    case Calendario = 'calendario';

    public function etiqueta(): string
    {
        return __('vistas.tipos.'.$this->value);
    }

    /** @return array<int, string> */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
