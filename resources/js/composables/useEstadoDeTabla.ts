import type { Direccion, OrdenDeColumna } from '@/components/TablaDatos/tipos';
import { useEstadoEnLaUrl } from './useEstadoEnLaUrl';

/**
 * La capa de tabla sobre `useEstadoEnLaUrl`.
 *
 * Aquí vive lo que **solo** es de una tabla: el orden multicolumna, las
 * columnas visibles y la paginación. Los filtros, el retraso al escribir y la
 * navegación son del núcleo, que el tablero de la fase 08 usa igual sin heredar
 * nada de esto.
 *
 * Por qué se partió: el tablero comparte filtros con el listado pero no tiene
 * columnas ni páginas, y si hubiera copiado el retraso al escribir y la
 * construcción de `filter[…]` esas dos copias habrían divergido a la tercera
 * vista.
 */
export type LecturaDeEstado = {
    /** Ruta del listado (`index(proyecto).url`). */
    url: string;
    orden: () => OrdenDeColumna[];
    filtros: () => Record<string, string>;
    /** Columnas visibles; `null` significa «las de por defecto». */
    columnas: () => string[] | null;
    porPagina: () => number;
};

type Cambios = {
    orden?: OrdenDeColumna[];
    columnas?: string[] | null;
    pagina?: number;
    porPagina?: number;
};

/**
 * Los parámetros que aporta la tabla. Es un objeto plano y no `RequestPayload`
 * porque este último admite también `FormData`, que no tiene claves.
 */
type ParametrosPlanos = Record<string, string | number>;

/** Tamaño de página de fábrica. Coincide con `EstadoDeTabla::TAMANOS[0]`. */
const POR_PAGINA_POR_DEFECTO = 30;

export function useEstadoDeTabla(lectura: LecturaDeEstado) {
    /**
     * Lo propio de la tabla, listo para viajar.
     *
     * **`page` no está aquí a propósito.** Es lo que hace que cualquier cambio
     * que no sea de página vuelva a la primera: quedarse en la página 7 después
     * de filtrar deja la pantalla vacía sin explicar por qué.
     */
    const parametrosDeTabla = (cambios: Cambios = {}): ParametrosPlanos => {
        const orden = cambios.orden ?? lectura.orden();
        const columnas =
            cambios.columnas !== undefined
                ? cambios.columnas
                : lectura.columnas();
        const porPagina = cambios.porPagina ?? lectura.porPagina();

        const parametros: ParametrosPlanos = {};

        if (orden.length > 0) {
            // `sort=-prioridad,codigo`: el orden de la lista es la prioridad del
            // desempate, y spatie/laravel-query-builder lo respeta tal cual.
            parametros.sort = orden
                .map(({ campo, direccion }) =>
                    direccion === 'desc' ? `-${campo}` : campo,
                )
                .join(',');
        }

        if (columnas !== null) {
            parametros.columnas = columnas.join(',');
        }

        if (porPagina !== POR_PAGINA_POR_DEFECTO) {
            parametros.por_pagina = porPagina;
        }

        return parametros;
    };

    const nucleo = useEstadoEnLaUrl({
        get url() {
            return lectura.url;
        },
        filtros: () => lectura.filtros(),
        otros: () => parametrosDeTabla(),
    });

    /** Navega conservando los filtros actuales y cambiando lo de la tabla. */
    const navegar = (cambios: Cambios, retrasar = false): void => {
        const parametros = parametrosDeTabla(cambios);
        const pagina = cambios.pagina ?? 1;

        if (pagina > 1) {
            parametros.page = pagina;
        }

        nucleo.navegarConFiltros(parametros, retrasar);
    };

    /**
     * Alterna el orden de un campo.
     *
     * Sin `acumular` el campo pasa a ser el único orden y cicla
     * ascendente → descendente → sin orden. Con `acumular` (Mayús+clic) se
     * añade al final de la lista o se cicla dentro de ella, que es lo que hace
     * posible el orden multicolumna.
     */
    const alternarOrden = (campo: string, acumular = false): void => {
        const actual = lectura.orden();
        const existente = actual.find((entrada) => entrada.campo === campo);

        const siguiente = (
            direccion: Direccion | undefined,
        ): Direccion | null => {
            if (direccion === undefined) {
                return 'asc';
            }

            return direccion === 'asc' ? 'desc' : null;
        };

        const direccion = siguiente(existente?.direccion);

        if (!acumular) {
            navegar({
                orden: direccion === null ? [] : [{ campo, direccion }],
            });

            return;
        }

        const orden = actual.filter((entrada) => entrada.campo !== campo);

        if (direccion !== null) {
            const posicion = actual.findIndex(
                (entrada) => entrada.campo === campo,
            );

            // Al ciclar un campo que ya estaba se conserva su posición; uno
            // nuevo se añade al final, que es donde el usuario espera verlo.
            if (posicion === -1) {
                orden.push({ campo, direccion });
            } else {
                orden.splice(posicion, 0, { campo, direccion });
            }
        }

        navegar({ orden });
    };

    const quitarDelOrden = (campo: string): void => {
        navegar({
            orden: lectura.orden().filter((entrada) => entrada.campo !== campo),
        });
    };

    const limpiarOrden = (): void => navegar({ orden: [] });

    /**
     * @param visibles Lista completa de columnas visibles, en orden de declaración.
     * @param porDefecto Las que la página considera visibles de fábrica.
     */
    const fijarColumnas = (visibles: string[], porDefecto: string[]): void => {
        const iguales =
            visibles.length === porDefecto.length &&
            visibles.every((id, indice) => id === porDefecto[indice]);

        // Si coincide con lo de fábrica se quita el parámetro en vez de
        // repetirlo: una URL que solo lleva lo que se cambió es legible.
        navegar({ columnas: iguales ? null : visibles });
    };

    const irAPagina = (pagina: number): void => navegar({ pagina });

    const cambiarPorPagina = (porPagina: number): void =>
        navegar({ porPagina });

    return {
        alternarOrden,
        quitarDelOrden,
        limpiarOrden,
        aplicarFiltro: nucleo.aplicarFiltro,
        limpiarFiltros: nucleo.limpiarFiltros,
        fijarColumnas,
        irAPagina,
        cambiarPorPagina,
    };
}
