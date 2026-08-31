<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Pago;

class PagoObserver
{
    /**
     * Calcula y persiste `monto_cop`.
     *
     * Se guarda calculado y no derivado en tiempo de lectura para que corregir
     * mañana la tasa de un pago viejo no reescriba lo que ya se cobró.
     *
     * La multiplicación va por `bcmath` sobre cadenas: en coma flotante,
     * 1234.56 * 4123.4567 pierde centavos y el reporte deja de cuadrar.
     */
    public function saving(Pago $pago): void
    {
        $pago->monto_cop = self::redondearADosDecimales(
            bcmul((string) $pago->monto_usd, (string) $pago->tasa_cambio, 6),
        );
    }

    /**
     * Redondeo comercial (medio hacia arriba) sin pasar por float: `bcadd` con
     * escala 2 trunca, así que sumar medio centavo antes equivale a redondear.
     */
    protected static function redondearADosDecimales(string $valor): string
    {
        $mediaUnidad = str_starts_with($valor, '-') ? '-0.005' : '0.005';

        return bcadd($valor, $mediaUnidad, 2);
    }
}
