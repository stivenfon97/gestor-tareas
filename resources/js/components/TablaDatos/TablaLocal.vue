<script setup lang="ts" generic="T extends Record<string, unknown>">
/**
 * La misma tabla que `TablaDatos`, para listas **incrustadas** en una ficha:
 * los proyectos de un cliente, los miembros de un proyecto o de un equipo.
 *
 * Esas listas son cortas, llegan enteras con la página y comparten pantalla
 * con otras cosas, así que no tiene sentido que cada orden o filtro sea una
 * visita al servidor ni que su estado ocupe la URL de la ficha. Aquí búsqueda,
 * filtros, orden y paginación son locales; el aspecto —barra, cabeceras,
 * badges, pie— es exactamente el de `TablaDatos`, con sus mismas piezas.
 *
 * **Un listado principal de una pantalla no usa esto**: usa `TablaDatos`, con
 * el estado en la URL y la paginación en el servidor.
 *
 * Los filtros de opciones comparan la opción con `columna.valor(fila)`, así
 * que la columna tiene que devolver el valor crudo (`'activo'`), no el texto.
 */
import { X } from '@lucide/vue';
import { useMediaQuery } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import EstadoVacio from '@/components/EstadoVacio.vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import BuscadorDeTabla from './BuscadorDeTabla.vue';
import CabeceraDeColumna from './CabeceraDeColumna.vue';
import FiltroFacetado from './FiltroFacetado.vue';
import PaginacionDeTabla from './PaginacionDeTabla.vue';
import TarjetaDeFila from './TarjetaDeFila.vue';
import type {
    ColumnaDeTabla,
    OpcionDeFiltro,
    OrdenDeColumna,
    TextoVacio,
} from './tipos';

const props = withDefaults(
    defineProps<{
        filas: T[];
        columnas: ColumnaDeTabla<T>[];
        clave: (fila: T) => string | number;
        etiqueta: string;
        vacio: TextoVacio;
        /** Marcador de la búsqueda; sin él no hay caja de búsqueda. */
        busqueda?: string | null;
        porPagina?: number;
    }>(),
    {
        busqueda: null,
        porPagina: 15,
    },
);

const esMovil = useMediaQuery('(max-width: 767.98px)');

const texto = ref('');
const filtros = ref<Record<string, string>>({});
const orden = ref<OrdenDeColumna[]>([]);
const pagina = ref(1);
const tamano = ref(props.porPagina);

const valorDe = (columna: ColumnaDeTabla<T>, fila: T): string =>
    String(columna.valor?.(fila) ?? '');

const filtrosFacetados = computed(() =>
    props.columnas.flatMap((columna) =>
        columna.filtro?.tipo === 'opciones'
            ? [
                  {
                      id: columna.id,
                      etiqueta: columna.etiqueta,
                      opciones: columna.filtro.opciones as OpcionDeFiltro[],
                  },
              ]
            : [],
    ),
);

const hayFiltrosActivos = computed(
    () =>
        texto.value !== '' ||
        Object.values(filtros.value).some((valor) => valor !== ''),
);

const filtradas = computed(() => {
    const termino = texto.value.trim().toLocaleLowerCase();

    return props.filas.filter((fila) => {
        const cumpleBusqueda =
            termino === '' ||
            props.columnas.some((columna) =>
                valorDe(columna, fila).toLocaleLowerCase().includes(termino),
            );

        const cumpleFiltros = Object.entries(filtros.value).every(
            ([id, valor]) => {
                const columna = props.columnas.find((otra) => otra.id === id);

                return (
                    valor === '' ||
                    columna === undefined ||
                    valorDe(columna, fila) === valor
                );
            },
        );

        return cumpleBusqueda && cumpleFiltros;
    });
});

const comparar = (a: unknown, b: unknown): number => {
    if (typeof a === 'number' && typeof b === 'number') {
        return a - b;
    }

    return String(a ?? '').localeCompare(String(b ?? ''), undefined, {
        numeric: true,
        sensitivity: 'base',
    });
};

const ordenadas = computed(() => {
    if (orden.value.length === 0) {
        return filtradas.value;
    }

    return [...filtradas.value].sort((a, b) => {
        for (const entrada of orden.value) {
            const columna = props.columnas.find(
                (otra) => otra.ordenPor === entrada.campo,
            );
            const resultado = comparar(
                columna?.valor?.(a),
                columna?.valor?.(b),
            );

            if (resultado !== 0) {
                return entrada.direccion === 'asc' ? resultado : -resultado;
            }
        }

        return 0;
    });
});

const paginacion = computed(() => ({
    current_page: pagina.value,
    last_page: Math.max(1, Math.ceil(ordenadas.value.length / tamano.value)),
    per_page: tamano.value,
    total: ordenadas.value.length,
}));

const visibles = computed(() =>
    ordenadas.value.slice(
        (pagina.value - 1) * tamano.value,
        pagina.value * tamano.value,
    ),
);

// Cualquier cambio que no sea de página vuelve a la primera: quedarse en la
// cuarta tras filtrar deja la tabla vacía sin explicar por qué.
watch([texto, filtros, tamano], () => {
    pagina.value = 1;
});

watch(
    () => props.filas.length,
    () => {
        pagina.value = Math.min(pagina.value, paginacion.value.last_page);
    },
);

const alternarOrden = (campo: string, acumular: boolean): void => {
    const existente = orden.value.find((entrada) => entrada.campo === campo);
    const direccion =
        existente === undefined
            ? 'asc'
            : existente.direccion === 'asc'
              ? 'desc'
              : null;

    const resto = acumular
        ? orden.value.filter((entrada) => entrada.campo !== campo)
        : [];

    orden.value = direccion === null ? resto : [...resto, { campo, direccion }];
};

const limpiar = (): void => {
    texto.value = '';
    filtros.value = {};
};

const principales = computed(() => {
    const declaradas = props.columnas.filter((columna) => columna.principal);

    return declaradas.length > 0
        ? declaradas
        : props.columnas.filter((columna) => columna.fija).slice(0, 1);
});

const prioritarias = computed(() =>
    props.columnas.filter(
        (columna) => columna.prioritaria && !columna.principal,
    ),
);

const restantes = computed(() =>
    props.columnas.filter(
        (columna) =>
            !columna.prioritaria && !principales.value.includes(columna),
    ),
);

const estiloDe = (columna: ColumnaDeTabla<T>): Record<string, string> =>
    columna.ajustada
        ? { width: '1%' }
        : columna.anchoMinimo
          ? { minWidth: columna.anchoMinimo }
          : {};
</script>

<template>
    <div
        class="flex min-w-0 flex-col overflow-hidden rounded-md border border-borde-fuerte bg-panel"
    >
        <div
            v-if="props.busqueda || filtrosFacetados.length > 0"
            class="flex flex-wrap items-center gap-corto border-b border-borde bg-panel-cabecera px-celda py-corto"
            data-barra-de-tabla
        >
            <BuscadorDeTabla
                v-if="props.busqueda"
                :valor="texto"
                :marcador="props.busqueda"
                :etiqueta="props.busqueda"
                @cambiar="(valor: string) => (texto = valor)"
            />

            <FiltroFacetado
                v-for="filtro in filtrosFacetados"
                :key="filtro.id"
                :etiqueta="filtro.etiqueta"
                :opciones="filtro.opciones"
                :valor="filtros[filtro.id] ?? ''"
                @cambiar="
                    (valor: string) =>
                        (filtros = { ...filtros, [filtro.id]: valor })
                "
            />

            <Button
                v-if="hayFiltrosActivos"
                variant="ghost"
                size="sm"
                class="text-texto-suave"
                @click="limpiar"
            >
                <X aria-hidden="true" />
                {{ $t('comun.tabla.limpiar_filtros') }}
            </Button>
        </div>

        <div v-if="esMovil" class="min-w-0">
            <TarjetaDeFila
                v-for="fila in visibles"
                :key="props.clave(fila)"
                :fila="fila"
                :principales="principales"
                :prioritarias="prioritarias"
                :restantes="restantes"
                :seleccionable="false"
                :seleccionada="false"
                :etiqueta-seleccion="$t('comun.tabla.seleccionar_fila')"
                :ver-mas="$t('comun.tabla.ver_mas')"
            >
                <template
                    v-for="(_, nombre) in $slots"
                    :key="nombre"
                    #[nombre]="datos"
                >
                    <slot :name="nombre" v-bind="datos ?? {}" />
                </template>
            </TarjetaDeFila>
        </div>

        <div v-else class="min-w-0 overflow-x-auto">
            <table class="w-full border-collapse text-cuerpo">
                <caption class="sr-only">
                    {{
                        props.etiqueta
                    }}
                </caption>
                <thead class="bg-panel-cabecera">
                    <tr class="text-left">
                        <th
                            v-for="columna in props.columnas"
                            :key="columna.id"
                            scope="col"
                            :style="estiloDe(columna)"
                            class="px-celda py-corto text-micro font-medium whitespace-nowrap text-texto-suave"
                        >
                            <div
                                :class="
                                    cn(
                                        'flex items-center',
                                        columna.alineacion === 'derecha' &&
                                            'justify-end',
                                    )
                                "
                            >
                                <CabeceraDeColumna
                                    :columna="
                                        columna as ColumnaDeTabla<unknown>
                                    "
                                    :orden="orden"
                                    @alternar="
                                        (acumular: boolean) =>
                                            alternarOrden(
                                                columna.ordenPor!,
                                                acumular,
                                            )
                                    "
                                />
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="fila in visibles"
                        :key="props.clave(fila)"
                        class="h-fila-comoda border-t border-borde hover:bg-fila-hover"
                    >
                        <td
                            v-for="columna in props.columnas"
                            :key="columna.id"
                            :style="estiloDe(columna)"
                            :class="
                                cn(
                                    'px-celda py-corto align-middle',
                                    columna.ajustada && 'whitespace-nowrap',
                                    columna.alineacion === 'derecha' &&
                                        'text-right',
                                    columna.mono && 'font-mono text-fino',
                                )
                            "
                        >
                            <slot
                                :name="`celda-${columna.id}`"
                                :fila="fila"
                                :valor="columna.valor?.(fila) ?? null"
                            >
                                {{ columna.valor?.(fila) ?? '' }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="visibles.length === 0" class="px-celda py-corto">
            <EstadoVacio
                v-if="hayFiltrosActivos"
                :titulo="$t('comun.tabla.sin_resultados_titulo')"
                :cuerpo="$t('comun.tabla.sin_resultados_cuerpo')"
            >
                <Button variant="outline" size="sm" @click="limpiar">
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

        <PaginacionDeTabla
            :paginacion="paginacion"
            :compacta="esMovil"
            @ir-a-pagina="(numero: number) => (pagina = numero)"
            @cambiar-por-pagina="(numero: number) => (tamano = numero)"
        />
    </div>
</template>
