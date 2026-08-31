import type { RequestPayload } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';
import { onBeforeUnmount } from 'vue';
import type { Direccion, OrdenDeColumna } from '@/components/TablaDatos/tipos';

/**
 * Sincroniza el estado de un listado —orden, página, filtros y columnas
 * visibles— **con la URL**.
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
 * No sabe nada de tablas: el Kanban de la fase 08 comparte filtros y orden con
 * el listado aunque no comparta la forma, y usa esto igual.
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
    filtros?: Record<string, string>;
    columnas?: string[] | null;
    pagina?: number;
    porPagina?: number;
};

/** Milisegundos que se espera antes de consultar mientras se escribe. */
const RETRASO_AL_ESCRIBIR = 300;

export function useEstadoDeTabla(lectura: LecturaDeEstado) {
    let temporizador: ReturnType<typeof setTimeout> | undefined;

    onBeforeUnmount(() => clearTimeout(temporizador));

    const construirParametros = (cambios: Cambios): RequestPayload => {
        const orden = cambios.orden ?? lectura.orden();
        const filtros = cambios.filtros ?? lectura.filtros();
        const columnas =
            cambios.columnas !== undefined
                ? cambios.columnas
                : lectura.columnas();
        const porPagina = cambios.porPagina ?? lectura.porPagina();

        const activos = Object.fromEntries(
            Object.entries(filtros).filter(
                ([, valor]) => valor !== '' && valor !== null,
            ),
        );

        const parametros: RequestPayload = {};

        if (Object.keys(activos).length > 0) {
            parametros.filter = activos;
        }

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

        if (porPagina !== 30) {
            parametros.por_pagina = porPagina;
        }

        // Cualquier cambio que no sea de página vuelve a la primera: quedarse en
        // la página 7 después de filtrar deja la pantalla vacía sin explicar por qué.
        const pagina = cambios.pagina ?? 1;

        if (pagina > 1) {
            parametros.page = pagina;
        }

        return parametros;
    };

    const navegar = (cambios: Cambios, retrasar = false): void => {
        clearTimeout(temporizador);

        const ir = () =>
            router.get(lectura.url, construirParametros(cambios), {
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

    const aplicarFiltro = (
        clave: string,
        valor: string,
        retrasar = false,
    ): void => {
        navegar(
            { filtros: { ...lectura.filtros(), [clave]: valor } },
            retrasar,
        );
    };

    const limpiarFiltros = (): void => navegar({ filtros: {} });

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
        aplicarFiltro,
        limpiarFiltros,
        fijarColumnas,
        irAPagina,
        cambiarPorPagina,
    };
}
