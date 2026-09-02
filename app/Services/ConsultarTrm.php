<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Tasa Representativa del Mercado a una fecha, desde el conjunto de datos
 * oficial de `datos.gov.co` (recurso `32sa-8pi3`, gratuito y sin llave).
 *
 * **Ningún registro de un pago espera a esto.** Tiempo de espera corto, un
 * solo intento —sin `retry()`— y cualquier fallo (caída, tiempo agotado,
 * forma de respuesta inesperada) devuelve `null` en lugar de propagar una
 * excepción: el formulario de pago sigue funcionando con captura manual y
 * `origen_tasa` queda como `manual`.
 *
 * La API publica la TRM por rangos de vigencia, no un valor por día exacto:
 * un viernes vale también para el sábado y el domingo, así que se filtra con
 * `vigenciadesde <= fecha AND vigenciahasta >= fecha`, no con una igualdad.
 * Verificado a mano contra la API real antes de escribir esto: pedir por
 * `vigenciadesde=` igual a una fecha de fin de semana devuelve `[]`.
 */
final class ConsultarTrm
{
    private const URL = 'https://www.datos.gov.co/resource/32sa-8pi3.json';

    private const TIEMPO_ESPERA_SEGUNDOS = 3;

    /**
     * La TRM vigente en `$fecha`, como cadena con cuatro decimales, o `null`
     * si la API no respondió o no hay dato para esa fecha.
     */
    public function __invoke(Carbon $fecha): ?string
    {
        $clave = 'trm:'.$fecha->toDateString();

        // La TRM de un día pasado no cambia: una vez resuelta, no se vuelve a
        // pedir. No se cachea un fallo -ni un `null`-, porque un servicio caído
        // ahora mismo puede responder en el siguiente intento.
        $cacheada = Cache::get($clave);

        if ($cacheada !== null) {
            return $cacheada;
        }

        $valor = $this->consultar($fecha);

        if ($valor !== null) {
            Cache::forever($clave, $valor);
        }

        return $valor;
    }

    private function consultar(Carbon $fecha): ?string
    {
        $isoFecha = $fecha->toDateString().'T00:00:00.000';

        try {
            $respuesta = Http::timeout(self::TIEMPO_ESPERA_SEGUNDOS)
                ->connectTimeout(self::TIEMPO_ESPERA_SEGUNDOS)
                ->get(self::URL, [
                    '$where' => "vigenciadesde <= '{$isoFecha}' AND vigenciahasta >= '{$isoFecha}'",
                    '$order' => 'vigenciadesde DESC',
                    '$limit' => 1,
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if ($respuesta->failed()) {
            return null;
        }

        $filas = $respuesta->json();

        if (! is_array($filas) || $filas === [] || ! isset($filas[0]['valor'])) {
            return null;
        }

        $valor = $filas[0]['valor'];

        if (! is_numeric($valor)) {
            return null;
        }

        return number_format((float) $valor, 4, '.', '');
    }
}
