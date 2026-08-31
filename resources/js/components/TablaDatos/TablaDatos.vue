<script setup lang="ts" generic="T extends Record<string, unknown>">
import { ChevronLeft, ChevronRight, Rows2, Rows3, X } from '@lucide/vue';
import { getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import type {
    ColumnDef,
    RowSelectionState,
    VisibilityState,
} from '@tanstack/vue-table';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import { Button } from '@/components/ui/button';
import { useDensidad } from '@/composables/useDensidad';
import { useEstadoDeTabla } from '@/composables/useEstadoDeTabla';
import { cn } from '@/lib/utils';
import CabeceraDeColumna from './CabeceraDeColumna.vue';
import EsqueletoDeTabla from './EsqueletoDeTabla.vue';
import EstadoVacio from './EstadoVacio.vue';
import FiltroDeColumna from './FiltroDeColumna.vue';
import MenuColumnas from './MenuColumnas.vue';
import type {
    AccionEnLote,
    ColumnaDeTabla,
    OrdenDeColumna,
    PaginacionDeTabla,
    TextoVacio,
} from './tipos';

/**
 * Tabla de datos genérica sobre TanStack Table v8.
 *
 * **No conoce ningún dominio.** Columnas, filtros y acciones se declaran desde
 * fuera; aquí dentro no hay ni una referencia a tareas, clientes o proyectos.
 * Si un listado necesita sobrescribir estos estilos desde fuera, la API está
 * mal diseñada: se arregla la API, no se parchea el listado.
 *
 * Tampoco trae tema propio. Consume los tokens de `app.css` y nada más.
 *
 * El estado —orden, página, filtros y columnas visibles— **vive en la URL**,
 * no en memoria: es lo que permite compartir un enlace a un listado filtrado y
 * recuperarlo tras recargar. La densidad es la excepción, y está razonada en
 * `useDensidad`.
 */
const props = withDefaults(
    defineProps<{
        /** Ruta del listado. La tabla navega a ella con los parámetros nuevos. */
        url: string;
        filas: T[];
        columnas: ColumnaDeTabla<T>[];
        /** Identificador estable de cada fila. */
        clave: (fila: T) => string | number;

        /** Estado actual, tal y como lo devolvió el servidor. */
        orden: OrdenDeColumna[];
        filtros: Record<string, string>;
        columnasVisibles: string[] | null;
        paginacion: PaginacionDeTabla;

        /** Rótulo accesible de la tabla. Va al `<caption>`. */
        etiqueta: string;
        /** Texto del vacío «todavía no hay datos». El otro vacío es genérico. */
        vacio: TextoVacio;

        seleccionable?: boolean;
        accionesEnLote?: AccionEnLote[];
        cargando?: boolean;
        error?: string | null;
        /** Longitud CSS. Va a `style`, nunca a una clase. */
        altoMaximo?: string;
    }>(),
    {
        seleccionable: false,
        accionesEnLote: () => [],
        cargando: false,
        error: null,
        altoMaximo: '70vh',
    },
);

const emit = defineEmits<{
    abrir: [fila: T];
    enLote: [accion: string, claves: Array<string | number>];
}>();

/* ---------------------------------------------------------------- densidad */

const { densidad, fijarDensidad } = useDensidad();

// Clases enteras y estáticas: el escáner de Tailwind lee el código como texto y
// una clase compuesta en ejecución no se genera.
const altoDeFila = computed(() =>
    densidad.value === 'compacta'
        ? 'h-fila-compacta py-ajustado'
        : 'h-fila-comoda py-corto',
);

/* --------------------------------------------------------- estado en la URL */

const columnasPorDefecto = computed(() =>
    props.columnas
        .filter((columna) => !columna.ocultaPorDefecto)
        .map((columna) => columna.id),
);

const visiblesPedidas = computed(
    () => props.columnasVisibles ?? columnasPorDefecto.value,
);

const estado = useEstadoDeTabla({
    get url() {
        return props.url;
    },
    orden: () => props.orden,
    filtros: () => props.filtros,
    columnas: () => props.columnasVisibles,
    porPagina: () => props.paginacion.per_page,
});

/* ------------------------------------------------------------- TanStack */

const visibilidad = ref<VisibilityState>({});
const seleccion = ref<RowSelectionState>({});

watch(
    [() => props.columnas, visiblesPedidas],
    () => {
        visibilidad.value = Object.fromEntries(
            props.columnas.map((columna) => [
                columna.id,
                // Una columna fija no se puede ocultar ni manipulando la URL.
                columna.fija === true ||
                    visiblesPedidas.value.includes(columna.id),
            ]),
        );
    },
    { immediate: true, deep: true },
);

// Una página nueva es un conjunto de filas nuevo: conservar la selección
// llevaría a aplicar una acción en lote a filas que ya no se ven.
watch(
    () => props.filas,
    () => {
        seleccion.value = {};
    },
);

const columnasTanstack = computed((): ColumnDef<T>[] =>
    props.columnas.map((columna) => ({
        id: columna.id,
        accessorFn: (fila: T) => columna.valor?.(fila) ?? null,
        enableHiding: columna.fija !== true,
        meta: { columna },
    })),
);

const tabla = useVueTable({
    get data() {
        return props.filas;
    },
    get columns() {
        return columnasTanstack.value;
    },
    getCoreRowModel: getCoreRowModel(),
    // El servidor ordena, filtra y pagina. TanStack aquí es el modelo de
    // columnas y de selección, no un motor de datos en el cliente.
    manualSorting: true,
    manualFiltering: true,
    manualPagination: true,
    getRowId: (fila) => String(props.clave(fila)),
    enableRowSelection: () => props.seleccionable,
    state: {
        get columnVisibility() {
            return visibilidad.value;
        },
        get rowSelection() {
            return seleccion.value;
        },
    },
    onColumnVisibilityChange: (actualizador) => {
        visibilidad.value =
            typeof actualizador === 'function'
                ? actualizador(visibilidad.value)
                : actualizador;
    },
    onRowSelectionChange: (actualizador) => {
        seleccion.value =
            typeof actualizador === 'function'
                ? actualizador(seleccion.value)
                : actualizador;
    },
});

const columnasVisiblesEnOrden = computed(() =>
    tabla
        .getVisibleLeafColumns()
        .map(
            (columna) =>
                (columna.columnDef.meta as { columna: ColumnaDeTabla<T> })
                    .columna,
        ),
);

const filasSeleccionadas = computed(() =>
    tabla.getSelectedRowModel().rows.map((fila) => props.clave(fila.original)),
);

/* ------------------------------------------------- anclaje de columnas */

/**
 * Desplazamiento izquierdo de cada columna anclada, medido del DOM.
 *
 * Calcularlo a mano exigiría que cada columna anclada declarara su ancho
 * exacto, y un ancho declarado que no coincide con el real deja un hueco o un
 * solape justo en la costura, que es donde más se nota.
 */
const cabecera = ref<HTMLTableRowElement | null>(null);
const desplazamientos = ref<Record<string, number>>({});
let observador: ResizeObserver | undefined;

const medirAnclajes = (): void => {
    const fila = cabecera.value;

    if (fila === null) {
        return;
    }

    const medidas: Record<string, number> = {};

    Array.from(fila.children).forEach((celda) => {
        const id = (celda as HTMLElement).dataset.columna;

        if (id !== undefined) {
            medidas[id] = (celda as HTMLElement).offsetLeft;
        }
    });

    desplazamientos.value = medidas;
};

onMounted(() => {
    medirAnclajes();

    if (typeof ResizeObserver !== 'undefined' && cabecera.value !== null) {
        observador = new ResizeObserver(() => medirAnclajes());
        observador.observe(cabecera.value);
    }
});

onBeforeUnmount(() => observador?.disconnect());

watch(columnasVisiblesEnOrden, () => nextTick(medirAnclajes));

const estiloAnclado = (columna: ColumnaDeTabla<T>): Record<string, string> => {
    const estilos: Record<string, string> = {};

    if (columna.anchoMinimo !== undefined) {
        estilos.minWidth = columna.anchoMinimo;
    }

    if (columna.anclada === true) {
        estilos.left = `${desplazamientos.value[columna.id] ?? 0}px`;
    }

    return estilos;
};

/* --------------------------------------------------- navegación con teclado */

const elementosDeFila = ref<HTMLTableRowElement[]>([]);
const filaActiva = ref(0);

const enfocarFila = (indice: number): void => {
    const acotado = Math.max(0, Math.min(indice, props.filas.length - 1));

    filaActiva.value = acotado;
    elementosDeFila.value[acotado]?.focus();
};

/**
 * Tabulador itinerante: solo la fila activa es alcanzable con Tab, y dentro de
 * la tabla se navega con las flechas. Con `tabindex="0"` en las treinta filas,
 * llegar al pie de página costaría treinta pulsaciones.
 */
const alPulsarTecla = (
    evento: KeyboardEvent,
    indice: number,
    fila: T,
): void => {
    const teclas: Record<string, () => void> = {
        ArrowDown: () => enfocarFila(indice + 1),
        ArrowUp: () => enfocarFila(indice - 1),
        Home: () => enfocarFila(0),
        End: () => enfocarFila(props.filas.length - 1),
        Enter: () => emit('abrir', fila),
    };

    const accion = teclas[evento.key];

    if (accion === undefined) {
        return;
    }

    // Solo se secuestra la tecla si la pulsación venía de la fila y no de un
    // control de dentro: Intro dentro de un campo de filtro es otra cosa.
    if (evento.target !== evento.currentTarget) {
        return;
    }

    evento.preventDefault();
    accion();
};

/* ------------------------------------------------------------ vacíos y pie */

const hayFiltrosActivos = computed(() =>
    Object.values(props.filtros).some(
        (valor) => valor !== '' && valor !== null,
    ),
);

const totalFiltros = computed(
    () =>
        Object.values(props.filtros).filter(
            (valor) => valor !== '' && valor !== null,
        ).length,
);

const sinFilas = computed(() => props.filas.length === 0 && !props.cargando);

const alternarColumna = (id: string, visible: boolean): void => {
    const visibles = props.columnas
        .filter((columna) =>
            columna.id === id
                ? visible
                : visiblesPedidas.value.includes(columna.id),
        )
        .map((columna) => columna.id);

    estado.fijarColumnas(visibles, columnasPorDefecto.value);
};
</script>

<template>
    <div class="flex flex-col gap-corto">
        <!-- Barra de herramientas -->
        <div class="flex flex-wrap items-center justify-between gap-corto">
            <div class="flex flex-wrap items-center gap-corto">
                <slot name="acciones" />

                <Button
                    v-if="hayFiltrosActivos"
                    variant="ghost"
                    size="sm"
                    class="gap-fino text-texto-suave"
                    @click="estado.limpiarFiltros()"
                >
                    <X class="size-3.5" aria-hidden="true" />
                    {{ $t('comun.tabla.limpiar_filtros') }}
                    <span class="text-fino">
                        ({{
                            $t('comun.tabla.filtros_activos', {
                                total: String(totalFiltros),
                            })
                        }})
                    </span>
                </Button>
            </div>

            <div class="flex items-center gap-corto">
                <Button
                    v-if="props.orden.length > 1"
                    variant="ghost"
                    size="sm"
                    class="text-texto-suave"
                    @click="estado.limpiarOrden()"
                >
                    {{ $t('comun.tabla.limpiar_orden') }}
                </Button>

                <MenuColumnas
                    :columnas="props.columnas as ColumnaDeTabla<unknown>[]"
                    :visibles="visiblesPedidas"
                    @alternar="alternarColumna"
                    @restablecer="
                        estado.fijarColumnas(
                            columnasPorDefecto,
                            columnasPorDefecto,
                        )
                    "
                />

                <Button
                    variant="outline"
                    size="sm"
                    class="gap-fino"
                    :aria-pressed="densidad === 'compacta'"
                    :title="$t('comun.tabla.densidad')"
                    @click="
                        fijarDensidad(
                            densidad === 'compacta' ? 'comoda' : 'compacta',
                        )
                    "
                >
                    <component
                        :is="densidad === 'compacta' ? Rows3 : Rows2"
                        class="size-4"
                        aria-hidden="true"
                    />
                    <span class="sr-only">
                        {{
                            densidad === 'compacta'
                                ? $t('comun.tabla.densidad_compacta')
                                : $t('comun.tabla.densidad_comoda')
                        }}
                    </span>
                </Button>
            </div>
        </div>

        <!-- Acciones en lote. Solo aparece con algo seleccionado. -->
        <div
            v-if="props.seleccionable && filasSeleccionadas.length > 0"
            class="flex flex-wrap items-center gap-corto rounded-md border border-borde bg-panel-cabecera px-celda py-corto"
        >
            <span class="text-cuerpo text-texto">
                {{
                    $t('comun.tabla.seleccionadas', {
                        total: String(filasSeleccionadas.length),
                    })
                }}
            </span>
            <Button
                v-for="accion in props.accionesEnLote"
                :key="accion.id"
                size="sm"
                :variant="accion.destructiva ? 'outline' : 'secondary'"
                @click="emit('enLote', accion.id, filasSeleccionadas)"
            >
                {{ accion.etiqueta }}
            </Button>
            <Button
                variant="ghost"
                size="sm"
                class="ml-auto text-texto-suave"
                @click="tabla.resetRowSelection()"
            >
                {{ $t('comun.tabla.limpiar_seleccion') }}
            </Button>
        </div>

        <!-- Panel de la tabla -->
        <div
            class="overflow-auto rounded-md border border-borde-fuerte bg-panel"
            :style="{ maxHeight: props.altoMaximo }"
        >
            <table class="w-full border-collapse text-cuerpo">
                <caption class="sr-only">
                    {{
                        props.etiqueta
                    }}
                    —
                    {{
                        $t('comun.tabla.ayuda_teclado')
                    }}
                    {{
                        $t('comun.tabla.orden_multiple_ayuda')
                    }}
                </caption>

                <thead class="sticky top-0 z-20 bg-panel-cabecera">
                    <tr ref="cabecera" class="bg-panel-cabecera text-left">
                        <th
                            v-if="props.seleccionable"
                            scope="col"
                            data-columna="__seleccion"
                            class="sticky left-0 z-10 w-9 bg-inherit px-celda"
                        >
                            <input
                                type="checkbox"
                                class="size-3.5 accent-marca"
                                :aria-label="
                                    $t('comun.tabla.seleccionar_pagina')
                                "
                                :checked="tabla.getIsAllPageRowsSelected()"
                                :indeterminate="
                                    tabla.getIsSomePageRowsSelected()
                                "
                                @change="tabla.toggleAllPageRowsSelected()"
                            />
                        </th>

                        <th
                            v-for="columna in columnasVisiblesEnOrden"
                            :key="columna.id"
                            scope="col"
                            :data-columna="columna.id"
                            :aria-sort="
                                props.orden.find(
                                    (entrada) =>
                                        entrada.campo === columna.ordenPor,
                                )
                                    ? props.orden.find(
                                          (entrada) =>
                                              entrada.campo ===
                                              columna.ordenPor,
                                      )!.direccion === 'asc'
                                        ? 'ascending'
                                        : 'descending'
                                    : 'none'
                            "
                            :style="estiloAnclado(columna)"
                            :class="
                                cn(
                                    'bg-inherit px-celda pt-corto pb-fino text-micro font-medium whitespace-nowrap text-texto-suave uppercase',
                                    columna.alineacion === 'derecha' &&
                                        'text-right',
                                    columna.anclada &&
                                        'sticky z-10 border-r border-borde-fuerte',
                                )
                            "
                        >
                            <CabeceraDeColumna
                                :columna="columna as ColumnaDeTabla<unknown>"
                                :orden="props.orden"
                                @alternar="
                                    (acumular: boolean) =>
                                        estado.alternarOrden(
                                            columna.ordenPor!,
                                            acumular,
                                        )
                                "
                            />
                        </th>
                    </tr>

                    <!-- Fila de filtros por columna, aparte de los globales. -->
                    <tr
                        v-if="
                            columnasVisiblesEnOrden.some(
                                (columna) => columna.filtro,
                            )
                        "
                        class="bg-panel-cabecera"
                    >
                        <th
                            v-if="props.seleccionable"
                            class="sticky left-0 z-10 bg-inherit"
                        />
                        <th
                            v-for="columna in columnasVisiblesEnOrden"
                            :key="columna.id"
                            :style="estiloAnclado(columna)"
                            :class="
                                cn(
                                    'bg-inherit px-celda pb-corto font-normal',
                                    columna.anclada &&
                                        'sticky z-10 border-r border-borde-fuerte',
                                )
                            "
                        >
                            <FiltroDeColumna
                                v-if="columna.filtro"
                                :filtro="columna.filtro"
                                :etiqueta="columna.etiqueta"
                                :valor="
                                    props.filtros[columna.filtro.clave] ?? ''
                                "
                                @cambiar="
                                    (valor: string, retrasar: boolean) =>
                                        estado.aplicarFiltro(
                                            columna.filtro!.clave,
                                            valor,
                                            retrasar,
                                        )
                                "
                            />
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <EsqueletoDeTabla
                        v-if="props.cargando"
                        :columnas="
                            columnasVisiblesEnOrden.length +
                            (props.seleccionable ? 1 : 0)
                        "
                        :filas="Math.min(props.paginacion.per_page, 8)"
                        :alto-de-fila="altoDeFila"
                    />

                    <tr
                        v-for="(fila, indice) in tabla.getRowModel().rows"
                        v-else
                        :key="fila.id"
                        ref="elementosDeFila"
                        :tabindex="indice === filaActiva ? 0 : -1"
                        :aria-selected="
                            props.seleccionable
                                ? fila.getIsSelected()
                                : undefined
                        "
                        :class="
                            cn(
                                'border-t border-borde bg-panel outline-none hover:bg-fila-hover focus-visible:bg-fila-hover',
                                fila.getIsSelected() && 'bg-fila-activa',
                                altoDeFila,
                            )
                        "
                        @focus="filaActiva = indice"
                        @keydown="alPulsarTecla($event, indice, fila.original)"
                        @dblclick="emit('abrir', fila.original)"
                    >
                        <td
                            v-if="props.seleccionable"
                            class="sticky left-0 z-10 w-9 bg-inherit px-celda"
                        >
                            <input
                                type="checkbox"
                                class="size-3.5 accent-marca"
                                :aria-label="$t('comun.tabla.seleccionar_fila')"
                                :checked="fila.getIsSelected()"
                                @change="fila.toggleSelected()"
                            />
                        </td>

                        <td
                            v-for="columna in columnasVisiblesEnOrden"
                            :key="columna.id"
                            :style="estiloAnclado(columna)"
                            :class="
                                cn(
                                    'bg-inherit px-celda align-middle',
                                    columna.alineacion === 'derecha' &&
                                        'text-right',
                                    columna.mono && 'font-mono text-fino',
                                    columna.anclada &&
                                        'sticky z-10 border-r border-borde-fuerte',
                                )
                            "
                        >
                            <slot
                                :name="`celda-${columna.id}`"
                                :fila="fila.original"
                                :valor="columna.valor?.(fila.original) ?? null"
                            >
                                {{ columna.valor?.(fila.original) ?? '' }}
                            </slot>
                        </td>
                    </tr>

                    <tr v-if="sinFilas">
                        <td
                            :colspan="
                                columnasVisiblesEnOrden.length +
                                (props.seleccionable ? 1 : 0)
                            "
                        >
                            <!-- Dos vacíos distintos con salidas distintas. Cuál
                                 de los dos es no se adivina: se sabe porque el
                                 servidor dijo si hay filtros activos. -->
                            <EstadoVacio
                                v-if="hayFiltrosActivos"
                                :titulo="
                                    $t('comun.tabla.sin_resultados_titulo')
                                "
                                :cuerpo="
                                    $t('comun.tabla.sin_resultados_cuerpo')
                                "
                            >
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="estado.limpiarFiltros()"
                                >
                                    {{ $t('comun.tabla.limpiar_filtros') }}
                                </Button>
                            </EstadoVacio>

                            <EstadoVacio
                                v-else
                                :titulo="props.vacio.titulo"
                                :cuerpo="props.vacio.cuerpo"
                            >
                                <slot name="vacio-acciones" />
                            </EstadoVacio>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- El error muestra el mensaje real del servidor: un «algo salió mal»
             le quita a un lector técnico lo único con lo que podría arreglarlo. -->
        <div
            v-if="props.error"
            class="flex flex-wrap items-center gap-corto rounded-md border border-estado-cancelada-borde bg-estado-cancelada-fondo px-celda py-corto text-cuerpo text-estado-cancelada-texto"
            role="alert"
        >
            <span class="font-medium">{{
                $t('comun.tabla.error_titulo')
            }}</span>
            <span>{{ props.error }}</span>
        </div>

        <!-- Pie: página X de Y, porque «cargar más» no dice cuánto queda. -->
        <div
            class="flex flex-wrap items-center justify-between gap-corto text-fino text-texto-suave"
        >
            <span>
                {{
                    $t('comun.tabla.total_resultados', {
                        total: String(props.paginacion.total),
                    })
                }}
            </span>

            <div class="flex items-center gap-corto">
                <label class="flex items-center gap-fino">
                    <span>{{ $t('comun.tabla.por_pagina') }}</span>
                    <select
                        class="h-6 rounded-sm border border-borde bg-panel px-fino text-fino text-texto"
                        :value="props.paginacion.per_page"
                        @change="
                            estado.cambiarPorPagina(
                                Number(
                                    ($event.target as HTMLSelectElement).value,
                                ),
                            )
                        "
                    >
                        <option :value="15">15</option>
                        <option :value="30">30</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </label>

                <span>
                    {{
                        $t('comun.tabla.pagina_de', {
                            actual: String(props.paginacion.current_page),
                            total: String(
                                Math.max(props.paginacion.last_page, 1),
                            ),
                        })
                    }}
                </span>

                <Button
                    variant="outline"
                    size="sm"
                    :disabled="props.paginacion.current_page <= 1"
                    :aria-label="$t('comun.anterior')"
                    @click="estado.irAPagina(props.paginacion.current_page - 1)"
                >
                    <ChevronLeft class="size-4" aria-hidden="true" />
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="
                        props.paginacion.current_page >=
                        props.paginacion.last_page
                    "
                    :aria-label="$t('comun.siguiente')"
                    @click="estado.irAPagina(props.paginacion.current_page + 1)"
                >
                    <ChevronRight class="size-4" aria-hidden="true" />
                </Button>
            </div>
        </div>
    </div>
</template>
