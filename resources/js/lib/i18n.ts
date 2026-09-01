import {
    trans as transDelPaquete,
    transChoice as transChoiceDelPaquete,
} from 'laravel-vue-i18n';
import type { App } from 'vue';

/**
 * Traducción con modo estricto en desarrollo.
 *
 * `laravel-vue-i18n` devuelve **la clave misma** cuando no encuentra la
 * traducción. Eso convierte un fallo en algo que se pinta como texto normal:
 * la fase 07b existe porque `lang/php_es.json` nunca se cargó y las cinco
 * pantallas mostraron `nav.dashboard` durante dos fases sin que ninguna
 * verificación de servidor lo notara.
 *
 * En desarrollo se avisa por consola y se marca visualmente. En producción se
 * devuelve la clave tal cual: un aviso a gritos en pantalla es peor que un
 * texto feo delante de un cliente.
 */

/**
 * Una cadena con forma de clave: sin espacios y con al menos dos segmentos
 * separados por puntos (`nav.dashboard`, `comun.tabla.total_resultados`).
 *
 * Es el mismo criterio que usa la comprobación de humo sobre el texto visible,
 * y está aquí para que las dos lo compartan.
 */
export const esClaveDeTraduccion = (valor: string): boolean =>
    /^[a-z0-9_]+(\.[a-z0-9_]+)+$/i.test(valor.trim());

const estricto = import.meta.env.DEV;

/** Se avisa una vez por clave: en un listado, `$t` se llama por cada fila. */
const yaAvisadas = new Set<string>();

const avisar = (clave: string): void => {
    if (yaAvisadas.has(clave)) {
        return;
    }

    yaAvisadas.add(clave);

    console.error(
        `[i18n] Clave sin traducir: «${clave}». Añádela a lang/es y a lang/en en este mismo commit.`,
    );
};

/**
 * Marca visible en desarrollo. El delimitador `⟦⟧` no aparece en ningún texto
 * de la aplicación, así que es inconfundible en una captura y sigue siendo un
 * texto normal para la comprobación de humo, que lo detecta por la clave.
 */
const marcar = (clave: string): string => `⟦${clave}⟧`;

const revisar = (clave: string, traduccion: string): string => {
    // `traduccion === clave` es como el paquete dice «no la encontré». Que
    // además tenga forma de clave es lo que la distingue de un texto que se
    // pasó tal cual —el nombre de un proyecto, el título de una migaja—, que
    // devolver sin tocar es lo correcto.
    if (!estricto || traduccion !== clave || !esClaveDeTraduccion(clave)) {
        return traduccion;
    }

    avisar(clave);

    return marcar(clave);
};

export const trans = (
    clave: string,
    reemplazos?: Record<string, string>,
): string => revisar(clave, transDelPaquete(clave, reemplazos));

export const transChoice = (
    clave: string,
    numero: number,
    reemplazos?: Record<string, string>,
): string => revisar(clave, transChoiceDelPaquete(clave, numero, reemplazos));

/**
 * Sustituye `$t` y `$tChoice` por las versiones estrictas.
 *
 * Va **después** de `app.use(i18nVue)`: el plugin los escribe en
 * `globalProperties` al instalarse y esto los pisa.
 */
export const instalarModoEstricto = (app: App): void => {
    app.config.globalProperties.$t = trans;
    app.config.globalProperties.$tChoice = transChoice;
};
