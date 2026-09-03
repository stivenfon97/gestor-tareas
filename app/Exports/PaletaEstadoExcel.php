<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\EstadoTarea;

/**
 * Paleta de rellenos de estado para el Excel. **No es la paleta de la
 * interfaz** (`resources/css/app.css`).
 *
 * Los tokens de pantalla están calibrados para claro y oscuro con seis
 * pasteles muy pálidos -todos alrededor de un 90% de luminosidad-, y en la
 * escala de grises de una impresora esa diferencia de luminosidad es la única
 * que sobrevive: el matiz desaparece. Los seis colapsarían en el mismo gris
 * casi blanco.
 *
 * Esta paleta fija en cambio un salto de luminosidad deliberado entre los
 * seis estados -de ~237 a ~168 sobre 255, calculado con la fórmula de luma
 * 0.299R+0.587G+0.114B- conservando el matiz de cada familia (ámbar, verde,
 * rojo, gris, morado, azul) para quien lo ve en pantalla. Impresos en blanco
 * y negro siguen leyéndose como seis grises distintos, no como seis copias
 * del mismo.
 */
final class PaletaEstadoExcel
{
    /**
     * @var array<string, string>
     */
    private const RELLENOS = [
        'pendiente' => 'F9EECD',
        'terminada' => 'C0EDD0',
        'cancelada' => 'F1C8C5',
        'en_espera' => 'C1BAB3',
        'entregada' => 'C2A0E3',
        'en_proceso' => '80AEEF',
    ];

    public static function relleno(EstadoTarea $estado): string
    {
        return self::RELLENOS[$estado->value];
    }
}
