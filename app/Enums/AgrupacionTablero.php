<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Por qué eje se agrupan las tareas en el tablero y en la lista.
 *
 * **No es un valor del dominio, es un eje de lectura**: no se persiste en
 * ninguna tabla, viaja en la URL y nada lo pinta como chip de color. Por eso no
 * expone `token()` como los enums de estado, tipo y prioridad: no hay un color
 * que le corresponda, y devolver uno inventado sería un token que nadie usa.
 *
 * La diferencia que importa está en `mueveEstado()`. Agrupado por estado,
 * soltar una tarjeta en otra columna **es un cambio de estado** y pasa por
 * `CambiarEstadoTarea` con toda su matriz detrás. Agrupado por cualquier otra
 * cosa es un cambio de campo normal. Parecen lo mismo y no lo son.
 */
enum AgrupacionTablero: string
{
    case Estado = 'estado';
    case Modulo = 'modulo';
    case Prioridad = 'prioridad';
    case Tipo = 'tipo';
    case Periodo = 'periodo';

    public function etiqueta(): string
    {
        return __('tablero.agrupacion.'.$this->value);
    }

    /** Columna de `tareas` por la que se parte el conjunto. */
    public function columna(): string
    {
        return match ($this) {
            self::Modulo => 'modulo_id',
            self::Periodo => 'periodo_id',
            default => $this->value,
        };
    }

    /**
     * Si mover una tarjeta entre columnas es un cambio de estado.
     *
     * Es la única agrupación en la que soltar valida contra la matriz de
     * transiciones, exige motivo en dos destinos y escribe historial.
     */
    public function mueveEstado(): bool
    {
        return $this === self::Estado;
    }

    /**
     * Los valores de un subconjunto.
     *
     * Cada vista admite los suyos: el tablero agrupa por estado, módulo,
     * prioridad y tipo; la lista, por módulo, periodo y estado. Agrupar el
     * tablero por periodo daría una columna por periodo cerrado del proyecto,
     * que no es un tablero sino un archivo.
     *
     * @param  array<int, self>  $casos
     * @return array<int, string>
     */
    public static function valores(array $casos): array
    {
        return array_column($casos, 'value');
    }

    /** Los cuatro ejes del tablero. @return array<int, self> */
    public static function delTablero(): array
    {
        return [self::Estado, self::Modulo, self::Prioridad, self::Tipo];
    }

    /** Los tres ejes de la lista agrupada. @return array<int, self> */
    public static function deLaLista(): array
    {
        return [self::Modulo, self::Periodo, self::Estado];
    }
}
