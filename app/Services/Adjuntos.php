<?php

declare(strict_types=1);

namespace App\Services;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Convierte un adjunto de Medialibrary en el array que viaja a Inertia.
 *
 * **Nunca la URL de disco.** Todo adjunto se sirve por `archivos.descargar`
 * (`ArchivoController`), la única ruta que pasa por `MediaPolicy` antes de
 * devolver un byte — un enlace directo a `storage/` no pregunta quién lo abre.
 */
final class Adjuntos
{
    /**
     * Límites de tamaño por colección, en bytes (D8.1). Es el sitio 4 de los
     * cuatro que la skill `archivos-y-adjuntos` documenta: `acceptsFile()` en
     * cada modelo y las reglas de validación de cada Form Request leen de
     * aquí, para que cambiar un límite no obligue a buscarlo en tres sitios.
     * Los otros dos —`docker/php/php.ini` y `docker/nginx/default.conf`— ya
     * están puestos a 300 M, por encima del techo de aquí.
     */
    public const LIMITE_IMAGENES = 5 * 1024 * 1024;

    public const LIMITE_DOCUMENTOS = 20 * 1024 * 1024;

    public const LIMITE_RESPALDOS = 256 * 1024 * 1024;

    /**
     * Misma lista para `acceptsMimeTypes()` (modelo) y `mimetypes:` (Form
     * Request): una sola fuente para el tipo real permitido por colección.
     *
     * @var array<int, string>
     */
    public const MIME_IMAGENES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /** @var array<int, string> */
    public const MIME_DOCUMENTOS = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        'application/zip',
    ];

    /** @var array<int, string> */
    public const MIME_RESPALDOS = ['application/sql', 'text/plain', 'application/x-sql', 'application/gzip', 'application/zip'];

    /**
     * Reglas de validación de Laravel para subir a una colección — el sitio 3
     * de los cuatro. `mimetypes:` lee el contenido real del archivo
     * (`finfo`), nunca la extensión: es la diferencia entre "rechaza un .sql
     * que en realidad es un .php" y "confía en lo que el nombre dice ser".
     *
     * @return array<int, string>
     */
    public static function reglasDeArchivo(string $coleccion): array
    {
        [$mimes, $limite] = match ($coleccion) {
            'imagenes' => [self::MIME_IMAGENES, self::LIMITE_IMAGENES],
            'documentos' => [self::MIME_DOCUMENTOS, self::LIMITE_DOCUMENTOS],
            'respaldos_bd' => [self::MIME_RESPALDOS, self::LIMITE_RESPALDOS],
            default => throw new \InvalidArgumentException("Colección desconocida: {$coleccion}"),
        };

        return [
            'required',
            'file',
            'mimetypes:'.implode(',', $mimes),
            'max:'.intdiv($limite, 1024),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function comoArray(Media $media): array
    {
        $tieneMiniatura = $media->collection_name === 'imagenes' && $media->hasGeneratedConversion('miniatura');

        return [
            'id' => $media->getKey(),
            'nombre' => $media->file_name,
            'tamano' => $media->size,
            'mime' => $media->mime_type,
            'coleccion' => $media->collection_name,
            'url' => route('archivos.descargar', $media),
            'miniatura_url' => $tieneMiniatura
                ? route('archivos.descargar', ['media' => $media, 'conversion' => 'miniatura'])
                : null,
            'creado_en' => $media->created_at?->toDateTimeString(),
        ];
    }
}
