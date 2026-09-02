<script setup lang="ts" generic="T extends Record<string, unknown>">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import type {
    ColumnDef,
    RowSelectionState,
    VisibilityState,
} from '@tanstack/vue-table';
import { useMediaQuery } from '@vueuse/core';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import EstadoVacio from '@/components/EstadoVacio.vue';
import { Button } from '@/components/ui/button';
import { useDensidad } from '@/composables/useDensidad';
import { useEstadoDeTabla } from '@/composables/useEstadoDeTabla';
import { cn } from '@/lib/utils';
import CabeceraDeColumna from './CabeceraDeColumna.vue';
import EsqueletoDeTabla from './EsqueletoDeTabla.vue';
import FiltroDeCabecera from './FiltroDeCabecera.vue';
import LeyendaDeColumna from './LeyendaDeColumna.vue';
import MenuCompacto from './MenuCompacto.vue';
import PanelDeFiltros from './PanelDeFiltros.vue';
import TarjetaDeFila from './TarjetaDeFila.vue';
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

/* ------------------------------------------------------------------ tamaño */

/**
 * Por debajo de `md` la tabla **deja de ser una tabla** y se apila en tarjetas.
 *
 * Se decide con una media query y no con `hidden md:table`, porque pintar las
 * dos formas duplicaría cada fila en el DOM y el tabulador itinerante acabaría
 * moviéndose por filas que nadie ve.
 *
 * El corte es el mismo que el del menú lateral (`SidebarProvider`): 767.98px,
 * que es justo antes de donde empieza `md:` en Tailwind.
 */
const esMovil = useMediaQuery('(max-width: 767.98px)');

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

/* ------------------------------------------------- reparto de la tarjeta */

/**
 * Titular de la tarjeta. Lo declara el listado con `principal`; si no lo
 * declara se usa la columna que identifica la fila (`fija`), que es la que
 * lleva el enlace. Adivinarlo por posición daría «Tipo» en tareas.
 */
const columnasPrincipales = computed(() => {
    const declaradas = columnasVisiblesEnOrden.value.filter(
        (columna) => columna.principal,
    );

    if (declaradas.length > 0) {
        return declaradas;
    }

    // Sin declaración, la que identifica la fila (`fija`), que es la que lleva
    // el enlace. Adivinarla por posición daría «Tipo» en el listado de tareas.
    const reserva =
        columnasVisiblesEnOrden.value.find((columna) => columna.fija) ??
        columnasVisiblesEnOrden.value[0];

    return reserva === undefined ? [] : [reserva];
});

const esPrincipal = (columna: ColumnaDeTabla<T>): boolean =>
    columnasPrincipales.value.some((otra) => otra.id === columna.id);

const columnasPrioritarias = computed(() =>
    columnasVisiblesEnOrden.value.filter(
        (columna) => columna.prioritaria === true && !esPrincipal(columna),
    ),
);

/** Lo que queda va detrás de «ver más». Nada se pierde, solo se pliega. */
const columnasRestantes = computed(() =>
    columnasVisiblesEnOrden.value.filter(
        (columna) => columna.prioritaria !== true && !esPrincipal(columna),
    ),
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

    /*
     * `width: 1%` sobre una tabla de disposición automática es la forma de
     * decir «esta columna mide lo que mide su contenido»: el navegador reparte
     * el ancho sobrante entre las demás. Con `nowrap` en la celda, el resultado
     * es una columna pegada a su contenido.
     *
     * `anchoMinimo` se ignora aquí a propósito: un mínimo declarado volvería a
     * reservar ancho, que es exactamente lo que esta marca evita.
     */
    if (columna.ajustada === true) {
        estilos.width = '1%';
    } else if (columna.anchoMinimo !== undefined) {
        estilos.minWidth = columna.anchoMinimo;
    }

    if (columna.anclada === true) {
        estilos.left = `${desplazamientos.value[columna.id] ?? 0}px`;
    }

    return estilos;
};

/* ------------------------------------------- alto en filas enteras */

/**
 * El desplazamiento vertical vive **dentro** del panel, y eso es lo que permite
 * que la cabecera se quede fija: un `<thead>` pegajoso necesita un contenedor
 * que ruede, y el contenedor tiene que rodar también en horizontal por las
 * columnas ancladas.
 *
 * El precio era que el alto pedido (`70vh`) cae donde cae, casi nunca en la
 * costura entre dos filas, y la última fila visible aparecía **cortada por la
 * mitad**. No era un `overflow` mal puesto: era un límite en píxeles aplicado a
 * un contenido medido en filas.
 *
 * Aquí se recorta ese límite hasta el pie de la última fila que cabe entera. No
 * se puede calcular con `alto de fila × n`: una fila con el nombre en dos
 * líneas mide más que sus vecinas, así que se miden las filas de verdad.
 */
const contenedor = ref<HTMLElement | null>(null);
const rejilla = ref<HTMLTableElement | null>(null);
const altoAjustado = ref<string | null>(null);
let observadorAlto: ResizeObserver | undefined;

const ajustarAlto = (): void => {
    const caja = contenedor.value;

    if (caja === null || esMovil.value) {
        altoAjustado.value = null;

        return;
    }

    /*
     * El límite pedido se lee del estilo calculado y no de `clientHeight`: con
     * un `height` ya puesto, `clientHeight` devolvería el recorte anterior y
     * cada medición encogería la tabla un poco más.
     */
    const limite = Number.parseFloat(getComputedStyle(caja).maxHeight);

    if (!Number.isFinite(limite)) {
        altoAjustado.value = null;

        return;
    }

    const filas = Array.from(
        caja.querySelectorAll<HTMLTableRowElement>('tbody > tr'),
    );

    // Origen del contenido: el canto superior de la caja, corregido por lo que
    // ya esté desplazado. Con rectángulos y no con `offsetTop`, que depende de
    // qué ancestro esté posicionado.
    const origen = caja.getBoundingClientRect().top - caja.scrollTop;
    const total = (rejilla.value ?? caja).scrollHeight;

    if (total <= limite) {
        // Cabe entera: el panel se ajusta a su contenido y no hay nada que
        // recortar. Un listado de tres filas se ve corto, que es la verdad.
        altoAjustado.value = null;

        return;
    }

    let corte = 0;

    for (const fila of filas) {
        const fondo = fila.getBoundingClientRect().bottom - origen;

        if (fondo > limite) {
            break;
        }

        corte = fondo;
    }

    // Si ni la primera fila cabe, se deja el límite pedido: recortar a cero
    // dejaría una tabla sin filas.
    altoAjustado.value = corte > 0 ? `${Math.round(corte)}px` : null;
};

const observarAlto = (): void => {
    observadorAlto?.disconnect();
    observadorAlto = undefined;

    if (typeof ResizeObserver === 'undefined' || rejilla.value === null) {
        return;
    }

    // Se observa la tabla, no el contenedor: el contenedor es justo lo que
    // cambia de alto al ajustar, y observarlo sería un bucle.
    observadorAlto = new ResizeObserver(() => ajustarAlto());
    observadorAlto.observe(rejilla.value);
};

onMounted(() => {
    ajustarAlto();
    observarAlto();
});

onBeforeUnmount(() => observadorAlto?.disconnect());

watch([densidad, columnasVisiblesEnOrden], () => nextTick(ajustarAlto));

/* --------------------------------------------------- navegación con teclado */

const elementosDeFila = ref<HTMLTableRowElement[]>([]);
const filaActiva = ref(0);

const enfocarFila = (indice: number): void => {
    const acotado = Math.max(0, Math.min(indice, props.filas.length - 1));

    filaActiva.value = acotado;
    elementosDeFila.value[acotado]?.focus();
};

const FOCALIZABLES = 'a, button, input, select, textarea, [tabindex]';

/**
 * Saca del recorrido de Tab todo lo interactivo que hay **dentro** de una fila.
 *
 * Sin esto el tabulador itinerante no sirve de nada: con dos enlaces por fila y
 * treinta filas, llegar al pie de la tabla costaría sesenta pulsaciones y las
 * flechas serían un adorno. Con esto, Tab va de la cabecera a la primera fila y
 * de ahí al pie, y dentro de la tabla se navega con las flechas.
 *
 * Los enlaces siguen ahí para el ratón y para los lectores de pantalla: se
 * quitan del orden de tabulación, no del árbol de accesibilidad.
 */
const sacarDelTabulador = (): void => {
    elementosDeFila.value.forEach((fila) => {
        fila?.querySelectorAll<HTMLElement>(FOCALIZABLES).forEach(
            (elemento) => {
                elemento.tabIndex = -1;
            },
        );
    });
};

onMounted(sacarDelTabulador);

watch(
    () => props.filas,
    () => {
        // Una página nueva es un conjunto de filas nuevo: conservar la
        // selección llevaría a aplicar una acción en lote a filas que ya no se
        // ven.
        seleccion.value = {};
        filaActiva.value = 0;
        nextTick(() => {
            sacarDelTabulador();
            ajustarAlto();
        });
    },
);

watch(columnasVisiblesEnOrden, () => nextTick(sacarDelTabulador));

/*
 * Al cruzar el corte de `md` la tabla se monta o se desmonta entera: las
 * referencias a la cabecera y a las filas son otras. Sin esto, al pasar de
 * tarjetas a tabla las columnas ancladas se quedan pegadas a la izquierda —el
 * desplazamiento medido era el de una cabecera que ya no existe— y todos los
 * enlaces de las filas vuelven al recorrido de Tab.
 */
watch(esMovil, () =>
    nextTick(() => {
        observador?.disconnect();
        observador = undefined;

        if (esMovil.value) {
            observadorAlto?.disconnect();
            observadorAlto = undefined;
            altoAjustado.value = null;

            return;
        }

        medirAnclajes();
        sacarDelTabulador();
        ajustarAlto();
        observarAlto();

        if (typeof ResizeObserver !== 'undefined' && cabecera.value !== null) {
            observador = new ResizeObserver(() => medirAnclajes());
            observador.observe(cabecera.value);
        }
    }),
);

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
        // Espacio marca y desmarca, que es lo que hace en cualquier lista con
        // casillas. Sin esto la selección solo existiría para el ratón.
        ' ': () => {
            if (props.seleccionable) {
                tabla.getRowModel().rows[indice]?.toggleSelected();
            }
        },
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

/** Valor de `aria-sort` de una columna: lo que anuncia el lector de pantalla. */
const ordenDeColumna = (
    columna: ColumnaDeTabla<T>,
): 'ascending' | 'descending' | 'none' => {
    const entrada = props.orden.find(
        (registro) => registro.campo === columna.ordenPor,
    );

    if (entrada === undefined) {
        return 'none';
    }

    return entrada.direccion === 'asc' ? 'ascending' : 'descending';
};

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

/**
 * Con una sola página los controles de paginación no llevan a ninguna parte:
 * las dos flechas nacen deshabilitadas y «Página 1 de 1» informa de que no hay
 * nada más. El total de resultados sí dice algo, y ese se queda.
 */
const hayVariasPaginas = computed(() => props.paginacion.last_page > 1);

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
                :variant="accion.destructiva ? 'destructive' : 'outline'"
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

        <!-- En la tabla los filtros cuelgan de la cabecera de cada columna;
             con tarjetas no hay cabecera, así que van aquí. Sin esto no se
             puede filtrar desde el teléfono, que es justo donde más falta
             hace. -->
        <PanelDeFiltros
            v-if="esMovil"
            :columnas="columnasVisiblesEnOrden"
            :filtros="props.filtros"
            :total="totalFiltros"
            @aplicar="estado.aplicarFiltro"
            @limpiar="estado.limpiarFiltros()"
        />

        <!--
            Panel: cabecera, cuerpo y pie del mismo objeto. El pie va dentro del
            borde para que se lea como parte de la tabla y no como un texto
            suelto debajo.

            **Es la única frontera de la pantalla.** El área de contenido ya no
            pinta otro panel redondeado alrededor: dos cajas concéntricas sin
            jerarquía entre ellas hacían que la tabla se leyera como un objeto
            flotando dentro de otro objeto. Ver docs/05-identidad-visual.md §12.
        -->
        <div
            class="flex min-w-0 flex-col overflow-hidden rounded-md border border-borde-fuerte bg-panel"
        >
            <!--
                Barra de estado de la tabla: cuántos resultados hay y el menú
                que opera sobre ella.

                Va **arriba y dentro del panel**. En el pie funcionaba, pero los
                controles de una tabla se buscan arriba. Y no reconstruye la
                banda que retiró la 07d: aquella cruzaba la pantalla **fuera**
                del panel para alojar dos botones y nada más; esta va dentro del
                borde, lleva el total de resultados —que es información, no un
                control— y sustituye a la franja que antes ocupaba el pie.
            -->
            <div
                class="flex items-center justify-between gap-corto border-b border-borde bg-panel-cabecera px-celda py-corto text-fino text-texto-suave"
            >
                <div class="flex min-w-0 items-center gap-corto">
                    <MenuCompacto
                        :columnas="props.columnas as ColumnaDeTabla<unknown>[]"
                        :visibles="visiblesPedidas"
                        :densidad="densidad"
                        :hay-filtros-activos="hayFiltrosActivos"
                        :hay-orden-multiple="props.orden.length > 1"
                        @alternar-columna="alternarColumna"
                        @restablecer-columnas="
                            estado.fijarColumnas(
                                columnasPorDefecto,
                                columnasPorDefecto,
                            )
                        "
                        @fijar-densidad="fijarDensidad"
                        @limpiar-filtros="estado.limpiarFiltros()"
                        @limpiar-orden="estado.limpiarOrden()"
                    />

                    <span class="truncate">
                        {{
                            $t('comun.tabla.total_resultados', {
                                total: String(props.paginacion.total),
                            })
                        }}
                    </span>
                </div>

                <div class="flex shrink-0 items-center gap-corto">
                    <slot name="acciones" />
                </div>
            </div>

            <!-- Tarjetas apiladas: por debajo de `md` la tabla deja de ser tabla. -->
            <!-- Sin alto máximo en móvil: un contenedor con su propio scroll
             dentro de una página que también rueda es un pulgar peleándose con
             dos ejes, y corta la última tarjeta por la mitad. -->
            <div v-if="esMovil" class="min-w-0">
                <!-- Esqueleto propio: el de la tabla pinta `<tr>`, que fuera de
                 un `<table>` no es válido. -->
                <div v-if="props.cargando" aria-hidden="true">
                    <div
                        v-for="tarjeta in Math.min(
                            props.paginacion.per_page,
                            5,
                        )"
                        :key="tarjeta"
                        class="flex flex-col gap-corto border-t border-borde px-celda py-corto first:border-t-0"
                    >
                        <span
                            class="block h-2 w-1/2 animate-pulse rounded-full bg-borde"
                        />
                        <span
                            v-for="linea in columnasPrioritarias.length || 2"
                            :key="linea"
                            class="block h-2 animate-pulse rounded-full bg-borde"
                        />
                    </div>
                </div>

                <template v-else>
                    <TarjetaDeFila
                        v-for="fila in tabla.getRowModel().rows"
                        :key="fila.id"
                        :fila="fila.original"
                        :principales="columnasPrincipales"
                        :prioritarias="columnasPrioritarias"
                        :restantes="columnasRestantes"
                        :seleccionable="props.seleccionable"
                        :seleccionada="fila.getIsSelected()"
                        :etiqueta-seleccion="$t('comun.tabla.seleccionar_fila')"
                        :ver-mas="$t('comun.tabla.ver_mas')"
                        @alternar-seleccion="fila.toggleSelected()"
                    >
                        <!-- Las celdas las sigue pintando la página: la tarjeta
                         cambia la forma, no quién sabe pintar cada valor. -->
                        <template
                            v-for="(_, nombre) in $slots"
                            :key="nombre"
                            #[nombre]="datos"
                        >
                            <slot :name="nombre" v-bind="datos ?? {}" />
                        </template>
                    </TarjetaDeFila>

                    <div v-if="sinFilas" class="px-celda py-corto">
                        <EstadoVacio
                            v-if="hayFiltrosActivos"
                            :titulo="$t('comun.tabla.sin_resultados_titulo')"
                            :cuerpo="$t('comun.tabla.sin_resultados_cuerpo')"
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
                    </div>
                </template>
            </div>

            <!-- `height` recortado a la última fila entera; `max-height` sigue
                 siendo el límite pedido y es de donde se mide. -->
            <div
                v-else
                ref="contenedor"
                class="min-w-0 overflow-auto"
                :style="{
                    maxHeight: props.altoMaximo,
                    height: altoAjustado ?? undefined,
                }"
            >
                <table ref="rejilla" class="w-full border-collapse text-cuerpo">
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
                                :aria-sort="ordenDeColumna(columna)"
                                :style="estiloAnclado(columna)"
                                :class="
                                    cn(
                                        'bg-inherit px-celda py-corto text-micro font-medium whitespace-nowrap text-texto-suave',
                                        columna.anclada &&
                                            'sticky z-10 border-r border-borde-fuerte',
                                    )
                                "
                            >
                                <!-- Rótulo, leyenda y filtro en la misma línea:
                                     la cabecera es el sitio de todo lo que
                                     opera sobre la columna. -->
                                <div
                                    :class="
                                        cn(
                                            'flex items-center gap-fino',
                                            columna.alineacion === 'derecha' &&
                                                'justify-end',
                                        )
                                    "
                                >
                                    <CabeceraDeColumna
                                        :columna="
                                            columna as ColumnaDeTabla<unknown>
                                        "
                                        :orden="props.orden"
                                        @alternar="
                                            (acumular: boolean) =>
                                                estado.alternarOrden(
                                                    columna.ordenPor!,
                                                    acumular,
                                                )
                                        "
                                    />

                                    <!-- La leyenda la escribe la página: aquí
                                         dentro no se sabe qué significa un
                                         icono de esa columna. -->
                                    <LeyendaDeColumna
                                        v-if="$slots[`leyenda-${columna.id}`]"
                                        :etiqueta="columna.etiqueta"
                                    >
                                        <slot :name="`leyenda-${columna.id}`" />
                                    </LeyendaDeColumna>

                                    <FiltroDeCabecera
                                        v-if="columna.filtro"
                                        :filtro="columna.filtro"
                                        :etiqueta="columna.etiqueta"
                                        :valor="
                                            props.filtros[
                                                columna.filtro.clave
                                            ] ?? ''
                                        "
                                        @cambiar="
                                            (
                                                valor: string,
                                                retrasar: boolean,
                                            ) =>
                                                estado.aplicarFiltro(
                                                    columna.filtro!.clave,
                                                    valor,
                                                    retrasar,
                                                )
                                        "
                                    />
                                </div>
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
                            @keydown="
                                alPulsarTecla($event, indice, fila.original)
                            "
                            @dblclick="emit('abrir', fila.original)"
                        >
                            <td
                                v-if="props.seleccionable"
                                class="sticky left-0 z-10 w-9 bg-inherit px-celda"
                            >
                                <input
                                    type="checkbox"
                                    class="size-3.5 accent-marca"
                                    :aria-label="
                                        $t('comun.tabla.seleccionar_fila')
                                    "
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
                                        columna.ajustada && 'whitespace-nowrap',
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
                                    :valor="
                                        columna.valor?.(fila.original) ?? null
                                    "
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

            <!--
                Pie: solo paginación, y **solo cuando hay más de una página**.
                Con una sola, «Por página · Página 1 de 1 · ‹ ›» son cuatro
                controles que no llevan a ninguna parte: dos nacen
                deshabilitados y el tercero informa de que no hay nada más. El
                total de resultados, que sí dice algo, vive arriba.
            -->
            <div
                v-if="hayVariasPaginas"
                class="flex items-center justify-end gap-corto border-t border-borde bg-panel-cabecera px-celda py-corto text-fino text-texto-suave"
            >
                <div class="flex items-center gap-corto">
                    <!-- En móvil sobran: con el pulgar se pasa página, no se
                     elige un tamaño ni se lee «página 2 de 9». -->
                    <label v-if="!esMovil" class="flex items-center gap-fino">
                        <span>{{ $t('comun.tabla.por_pagina') }}</span>
                        <select
                            class="h-6 rounded-sm border border-borde bg-panel px-fino text-fino text-texto"
                            :value="props.paginacion.per_page"
                            @change="
                                estado.cambiarPorPagina(
                                    Number(
                                        ($event.target as HTMLSelectElement)
                                            .value,
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

                    <span v-if="!esMovil">
                        {{
                            $t('comun.tabla.pagina_de', {
                                actual: String(props.paginacion.current_page),
                                total: String(props.paginacion.last_page),
                            })
                        }}
                    </span>

                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="props.paginacion.current_page <= 1"
                        :aria-label="$t('comun.anterior')"
                        @click="
                            estado.irAPagina(props.paginacion.current_page - 1)
                        "
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
                        @click="
                            estado.irAPagina(props.paginacion.current_page + 1)
                        "
                    >
                        <ChevronRight class="size-4" aria-hidden="true" />
                    </Button>
                </div>
            </div>
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
    </div>
</template>
