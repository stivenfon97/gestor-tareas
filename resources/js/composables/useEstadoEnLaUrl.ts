import type { RequestPayload } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';
import { onBeforeUnmount } from 'vue';

/**
 * Núcleo de «el estado de una vista vive en la URL».
 *
 * Un listado cuyo estado solo vive en memoria no se puede compartir ni
 * recuperar tras recargar: «las tareas en espera del proyecto X» deja de ser un
 * enlace y pasa a ser una instrucción de siete pasos.
 *
 * El flujo es de un solo sentido a propósito:
 *
 * ```
 * URL ──► servidor ──► props ──► lo que se pinta
 *  ▲                                  │
 *  └──────── estas acciones ◄─────────┘
 * ```
 *
 * El composable **no guarda copia del estado**: lo lee de los getters, que
 * apuntan a las props del servidor. Así no hay dos fuentes de verdad que puedan
 * separarse cuando una petición falla o el usuario usa el botón «atrás».
 *
 * **Aquí solo viven los filtros, el retraso al escribir y la navegación.** Lo
 * que es de una forma concreta —el orden y la paginación de la tabla, el
 * criterio de agrupación del tablero— lo pone la capa de encima y llega por
 * `otros()`. El núcleo lo reemite sin interpretarlo: es lo que permite que un
 * cambio de filtro conserve el orden sin que el núcleo sepa qué es un orden.
 *
 * La fase 08 lo partió de `useEstadoDeTabla`. Antes el tablero habría tenido
 * que duplicar el retraso al escribir y la construcción de `filter[…]`, y a la
 * tercera copia esas cosas divergen y ya nadie las unifica.
 */
export type LecturaDeUrl = {
    /** Ruta de la vista (`index(proyecto).url`). */
    url: string;
    filtros: () => Record<string, string>;
    /** Parámetros propios de la vista, que se conservan al filtrar. */
    otros?: () => RequestPayload;
};

/** Milisegundos que se espera antes de consultar mientras se escribe. */
export const RETRASO_AL_ESCRIBIR = 300;

/**
 * Filtros con valor, listos para viajar como `filter[clave]=valor`.
 *
 * Los vacíos se descartan en vez de mandarse en blanco: una URL que solo lleva
 * lo que se cambió es legible, y `filter[estado]=` no significa nada.
 */
export const parametrosDeFiltros = (
    filtros: Record<string, string>,
): RequestPayload => {
    const activos = Object.fromEntries(
        Object.entries(filtros).filter(
            ([, valor]) => valor !== '' && valor !== null,
        ),
    );

    return Object.keys(activos).length > 0 ? { filter: activos } : {};
};

export function useEstadoEnLaUrl(lectura: LecturaDeUrl) {
    let temporizador: ReturnType<typeof setTimeout> | undefined;

    onBeforeUnmount(() => clearTimeout(temporizador));

    const navegar = (parametros: RequestPayload, retrasar = false): void => {
        clearTimeout(temporizador);

        const ir = () =>
            router.get(lectura.url, parametros, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });

        if (retrasar) {
            temporizador = setTimeout(ir, RETRASO_AL_ESCRIBIR);

            return;
        }

        ir();
    };

    /** Lo que la vista aporta además de los filtros. */
    const otros = (): RequestPayload => lectura.otros?.() ?? {};

    /**
     * Navega con los filtros cambiados.
     *
     * `otros()` se vuelve a leer aquí, así que el orden y las columnas
     * sobreviven al filtro. Lo que **no** sobrevive es la página, y eso es
     * deliberado: quedarse en la página 7 después de filtrar deja la pantalla
     * vacía sin explicar por qué. La capa de encima lo consigue no incluyendo
     * `page` en `otros()`.
     */
    const fijarFiltros = (
        filtros: Record<string, string>,
        retrasar = false,
    ): void => {
        navegar({ ...otros(), ...parametrosDeFiltros(filtros) }, retrasar);
    };

    const aplicarFiltro = (
        clave: string,
        valor: string,
        retrasar = false,
    ): void => {
        fijarFiltros({ ...lectura.filtros(), [clave]: valor }, retrasar);
    };

    const limpiarFiltros = (): void => fijarFiltros({});

    /** Navegación con los filtros actuales más lo que se le añada. */
    const navegarConFiltros = (
        parametros: RequestPayload = {},
        retrasar = false,
    ): void => {
        navegar(
            { ...parametros, ...parametrosDeFiltros(lectura.filtros()) },
            retrasar,
        );
    };

    return {
        navegar,
        navegarConFiltros,
        aplicarFiltro,
        fijarFiltros,
        limpiarFiltros,
    };
}
