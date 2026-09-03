export type Coleccion = 'imagenes' | 'documentos' | 'respaldos_bd';

/** Un adjunto tal como lo sirve `App\Services\Adjuntos::comoArray()`. */
export type Adjunto = {
    id: number;
    nombre: string;
    tamano: number;
    mime: string;
    coleccion: Coleccion;
    /** Siempre `archivos.descargar`. Nunca una URL de disco. */
    url: string;
    miniatura_url: string | null;
    creado_en: string | null;
};
