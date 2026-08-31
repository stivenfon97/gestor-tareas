<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Los dos reportes son archivos distintos y no se mezclan nunca.
 *
 * El `completo` es de uso interno e incluye pagos; el de `cliente` solo lleva
 * tarea, estado, descripción y módulo.
 */
enum TipoReporte: string
{
    case Completo = 'completo';
    case Cliente = 'cliente';

    public function etiqueta(): string
    {
        return 'enums.tipo_reporte.'.$this->value;
    }

    public function token(): string
    {
        return 'tipo-reporte-'.str_replace('_', '-', $this->value);
    }

    /**
     * Sufijo obligatorio del nombre del archivo. Sin él, los dos reportes de un
     * mismo periodo colisionan en la carpeta de descargas.
     */
    public function sufijoArchivo(): string
    {
        return $this->value;
    }
}
