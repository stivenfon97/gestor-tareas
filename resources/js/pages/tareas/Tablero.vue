<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import draggable from 'vuedraggable';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import EstadoVacio from '@/components/EstadoVacio.vue';
import DialogoDeMotivo from '@/components/tareas/DialogoDeMotivo.vue';
import PanelDeFiltrosDeTareas from '@/components/tareas/PanelDeFiltrosDeTareas.vue';
import SelectorDeVista from '@/components/tareas/SelectorDeVista.vue';
import TarjetaDeTarea from '@/components/tareas/TarjetaDeTarea.vue';
import VistasGuardadas from '@/components/tareas/VistasGuardadas.vue';
import { Button } from '@/components/ui/button';
import { useTablero } from '@/composables/useTablero';
import { cn } from '@/lib/utils';
import { show as mostrarProyecto } from '@/routes/proyectos';
import { columna as columnaDelTablero } from '@/routes/proyectos/tablero';
import { create, index as tareasIndex } from '@/routes/proyectos/tareas';
import { show as mostrarTarea } from '@/routes/tareas';
import type { OpcionesDeTarea } from '@/types/tareas';
import type {
    Agrupacion,
    ColumnaDeTablero,
    ConfiguracionDeVista,
    TarjetaDeTarea as Tarjeta,
    VistaGuardada,
} from '@/types/vistas';

/**
 * El tablero: las mismas tareas del listado, en columnas y movibles.
 *
 * Toda la lógica de mover está en `useTablero`, incluida la razón por la que
 * hay tres caminos distintos según por qué se agrupe. Aquí solo se pinta y se
 * conecta el teclado.
 */
const props = defineProps<{
    proyecto: {
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
    };
    agrupacion: Agrupacion;
    agrupaciones: Array<{ valor: string; etiqueta: string }>;
    columnas: ColumnaDeTablero[];
    filtros: Record<string, string>;
    vistas: VistaGuardada[];
    opciones: OpcionesDeTarea;
    puede: { crear: boolean; mover: boolean };
}>();

const tablero = useTablero({
    proyectoId: props.proyecto.id,
    columnas: () => props.columnas,
    agrupacion: () => props.agrupacion,
    puedeMover: () => props.puede.mover,
});

/** La configuración de ahora, que es lo que guardaría una vista nueva. */
const configuracionActual = computed((): ConfiguracionDeVista => {
    const activos = Object.entries(props.filtros).filter(
        ([, valor]) => valor !== '' && valor !== null,
    );

    return {
        ...(activos.length > 0
            ? { filter: Object.fromEntries(activos) as Record<string, string> }
            : {}),
        ...(props.agrupacion !== 'estado' ? { agrupar: props.agrupacion } : {}),
    };
});

const cambiarAgrupacion = (valor: string): void => {
    router.get(
        window.location.pathname,
        { ...configuracionActual.value, agrupar: valor },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

/* ------------------------------------------------------------- arrastre */

/*
 * Instantánea de antes de que `vuedraggable` toque los arrays.
 *
 * Se hace en `@start`, no en `@change`: para cuando llega `change` el array ya
 * está modificado, y entonces no habría a dónde volver si el servidor rechaza.
 */
const respaldo = ref<ColumnaDeTablero[]>([]);

/**
 * Contador que entra en la clave de cada lista arrastrable.
 *
 * Incrementarlo fuerza a Vue a reconstruir las listas en lugar de reutilizar
 * los nodos que SortableJS ya movió de sitio. Ver `alCambiar`.
 */
const version = ref(0);

const alEmpezar = (
    columna: ColumnaDeTablero,
    evento: { oldIndex?: number },
): void => {
    tablero.arrastrando.value = columna.tareas[evento.oldIndex ?? 0] ?? null;
    tablero.intencion.value = columna.clave;
    respaldo.value = tablero.copiar(tablero.columnas.value);
};

const alTerminar = (): void => {
    tablero.arrastrando.value = null;
};

/**
 * `vuedraggable` pregunta esto antes de dejar soltar.
 *
 * Devolver `false` es lo que impide de verdad el soltar; el marcado visual de
 * la columna es solo cortesía. Sin esta comprobación, la tarjeta caería y
 * volvería sola medio segundo después, que se lee como un fallo.
 */
const puedeSoltar = (evento: {
    to: HTMLElement;
    draggedContext: { element: Tarjeta };
}): boolean => {
    const destino = evento.to.dataset.columna;

    if (destino === undefined) {
        return false;
    }

    // Se apunta la intención aunque se rechace: es lo que permite distinguir
    // «solté aquí» de «pasé por aquí de camino a una columna prohibida».
    tablero.intencion.value = destino;

    return tablero.puedeIr(evento.draggedContext.element, destino);
};

const alCambiar = (clave: string, cambio: Record<string, unknown>): void => {
    if ('added' in cambio) {
        const anadida = cambio.added as { element: Tarjeta };

        /*
         * La tarjeta aterrizó en una columna distinta de la última sobre la
         * que se soltó: pasó por aquí de camino a una columna prohibida y
         * SortableJS la dejó en la última que la admitió. Nadie pidió esta
         * transición, así que vuelve a su sitio.
         */
        if (
            tablero.intencion.value !== null &&
            tablero.intencion.value !== clave
        ) {
            /*
             * La tarjeta aterrizó en una columna del camino, no en la que se
             * soltó. Al servidor no se le pide nada —nadie quiso esta
             * transición— y el modelo vuelve a como estaba.
             *
             * Restaurar el modelo **no basta**: SortableJS ya movió el nodo del
             * DOM de un contenedor a otro, y el diff de Vue reutiliza los nodos
             * por clave sin devolverlos a su lista de origen. Por eso se
             * incrementa `version`, que entra en la clave de cada lista y obliga
             * a reconstruirlas desde cero. Es la única forma de deshacer lo que
             * tocó una biblioteca que escribe en el DOM por su cuenta.
             */
            tablero.columnas.value = respaldo.value;
            version.value += 1;

            return;
        }

        tablero.mover(anadida.element, clave, respaldo.value);

        return;
    }

    // Reordenar dentro de la columna: no cambia ninguna clasificación, solo la
    // posición. Va por su propio endpoint y no toca el estado.
    if ('moved' in cambio) {
        tablero.persistirOrden(clave, respaldo.value);
    }
};

/* ------------------------------------------------------------ cargar más */

const cargando = ref<string | null>(null);

/**
 * La página siguiente de **una** columna.
 *
 * Petición suelta y no recarga de Inertia: una recarga parcial rearmaría las
 * seis columnas y sus seis totales para traer veinte tarjetas de una.
 */
const cargarMas = async (columna: ColumnaDeTablero): Promise<void> => {
    cargando.value = columna.clave;

    const consulta = new URLSearchParams({
        agrupar: props.agrupacion,
        clave: columna.clave,
        desde: String(columna.tareas.length),
    });

    for (const [clave, valor] of Object.entries(props.filtros)) {
        if (valor !== '' && valor !== null) {
            consulta.set(`filter[${clave}]`, valor);
        }
    }

    try {
        const respuesta = await fetch(
            `${columnaDelTablero(props.proyecto.id).url}?${consulta.toString()}`,
            { headers: { Accept: 'application/json' } },
        );
        const datos = (await respuesta.json()) as { tareas: Tarjeta[] };

        const destino = tablero.columnas.value.find(
            (candidata) => candidata.clave === columna.clave,
        );

        if (destino !== undefined) {
            destino.tareas.push(...datos.tareas);
        }
    } finally {
        cargando.value = null;
    }
};

/* --------------------------------------------------------------- teclado */

/**
 * El tablero es **bidimensional** y necesita su propio modelo de teclado: la
 * tabla se recorre con arriba y abajo, y aquí hacen falta también los lados.
 *
 * Un tablero que solo funciona con ratón es un tablero que no se usa cuando
 * tienes las manos en el teclado, que es siempre.
 */
const foco = ref<{ columna: number; fila: number }>({ columna: 0, fila: 0 });

const tarjetas = ref<HTMLElement[]>([]);

const enfocar = (columna: number, fila: number): void => {
    const columnas = tablero.columnas.value;
    const c = Math.max(0, Math.min(columna, columnas.length - 1));
    const f = Math.max(
        0,
        Math.min(fila, (columnas[c]?.tareas.length ?? 1) - 1),
    );

    foco.value = { columna: c, fila: f };

    nextTick(() => {
        const id = columnas[c]?.tareas[f]?.id;

        tarjetas.value
            .find((elemento) => elemento?.dataset.tarea === String(id))
            ?.focus();
    });
};

const alPulsar = (
    evento: KeyboardEvent,
    tarea: Tarjeta,
    columna: number,
    fila: number,
): void => {
    // Ctrl y flecha mueve la tarjeta; la flecha sola mueve el foco. Se mira
    // antes que nada porque comparten tecla.
    if (
        evento.ctrlKey &&
        (evento.key === 'ArrowLeft' || evento.key === 'ArrowRight')
    ) {
        evento.preventDefault();
        tablero.moverConTeclado(tarea, evento.key === 'ArrowLeft' ? -1 : 1);

        return;
    }

    const acciones: Record<string, () => void> = {
        ArrowDown: () => enfocar(columna, fila + 1),
        ArrowUp: () => enfocar(columna, fila - 1),
        ArrowRight: () => enfocar(columna + 1, fila),
        ArrowLeft: () => enfocar(columna - 1, fila),
        Home: () => enfocar(columna, 0),
        End: () =>
            enfocar(columna, tablero.columnas.value[columna].tareas.length - 1),
        Enter: () => router.visit(mostrarTarea(tarea.id).url),
    };

    const accion = acciones[evento.key];

    if (accion === undefined || evento.target !== evento.currentTarget) {
        return;
    }

    evento.preventDefault();
    accion();
};

const esActiva = (columna: number, fila: number): boolean =>
    foco.value.columna === columna && foco.value.fila === fila;
</script>

<template>
    <Head :title="$t('tablero.titulo')" />

    <div class="flex min-w-0 flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="$t('tablero.titulo')"
            :descripcion="props.proyecto.nombre"
        >
            <template #acciones>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="mostrarProyecto(props.proyecto.id)">{{
                        $t('comun.volver')
                    }}</Link>
                </Button>
                <Button v-if="props.puede.crear" as-child size="sm">
                    <Link :href="create(props.proyecto.id)">{{
                        $t('tareas.crear')
                    }}</Link>
                </Button>
            </template>

            <template #meta>
                <SelectorDeVista
                    :proyecto="props.proyecto"
                    actual="kanban"
                    :filtros="props.filtros"
                />
                <VistasGuardadas
                    :vistas="props.vistas"
                    tipo="kanban"
                    :proyecto-id="props.proyecto.id"
                    :configuracion-actual="configuracionActual"
                />
            </template>
        </CabeceraDePagina>

        <div class="flex flex-wrap items-center gap-corto">
            <label
                class="flex items-center gap-fino text-fino text-texto-suave"
            >
                <span>{{ $t('tablero.agrupacion.titulo') }}</span>
                <select
                    class="h-8 rounded-sm border border-borde bg-panel px-corto text-cuerpo text-texto"
                    :value="props.agrupacion"
                    @change="
                        cambiarAgrupacion(
                            ($event.target as HTMLSelectElement).value,
                        )
                    "
                >
                    <option
                        v-for="opcion in props.agrupaciones"
                        :key="opcion.valor"
                        :value="opcion.valor"
                    >
                        {{ opcion.etiqueta }}
                    </option>
                </select>
            </label>

            <PanelDeFiltrosDeTareas
                :filtros="props.filtros"
                :opciones="props.opciones"
            />

            <p
                v-if="!props.puede.mover"
                class="text-fino text-texto-suave"
                role="note"
            >
                {{ $t('tablero.sin_permiso_para_mover') }}
            </p>
        </div>

        <!-- Lo que cambia por el teclado se anuncia: sin esto, mover una
             tarjeta sin ratón no produce ninguna señal para quien no ve. -->
        <p aria-live="polite" class="sr-only">{{ tablero.anuncio.value }}</p>
        <p class="sr-only">{{ $t('tablero.teclado.ayuda') }}</p>

        <!--
            `items-start`: cada columna mide lo que mide su contenido. Sin esto,
            todas toman la altura de la más llena y un tablero con cuarenta
            entregadas deja cinco columnas vacías de cuatro mil píxeles.
        -->
        <div
            class="-mx-bloque flex min-w-0 items-start gap-corto overflow-x-auto px-bloque pb-corto"
        >
            <section
                v-for="(columna, indiceColumna) in tablero.columnas.value"
                :key="columna.clave"
                :class="
                    cn(
                        'flex w-72 shrink-0 flex-col gap-corto rounded-md border bg-panel-cabecera p-corto transition-colors',
                        tablero.noDisponible(columna.clave)
                            ? 'border-dashed border-borde-fuerte opacity-50'
                            : 'border-borde',
                    )
                "
                :aria-label="columna.etiqueta"
            >
                <header
                    class="flex items-baseline justify-between gap-fino px-fino"
                >
                    <h2 class="truncate text-cuerpo font-medium text-texto">
                        {{ columna.etiqueta }}
                    </h2>
                    <!-- Cargadas y total: «50» a secas escondería que hay 218. -->
                    <span class="shrink-0 font-mono text-fino text-texto-suave">
                        {{
                            columna.tareas.length === columna.total
                                ? columna.total
                                : $t('tablero.columna.cargadas', {
                                      cargadas: String(columna.tareas.length),
                                      total: String(columna.total),
                                  })
                        }}
                    </span>
                </header>

                <draggable
                    :key="`${columna.clave}-${version}`"
                    v-model="columna.tareas"
                    :group="{ name: 'tablero' }"
                    item-key="id"
                    handle=".tirador"
                    :disabled="!props.puede.mover"
                    :move="puedeSoltar"
                    :data-columna="columna.clave"
                    tag="div"
                    class="flex max-h-[60vh] min-h-16 flex-col gap-corto overflow-y-auto"
                    ghost-class="opacity-40"
                    :delay="120"
                    :delay-on-touch-only="true"
                    :touch-start-threshold="8"
                    @start="alEmpezar(columna, $event)"
                    @end="alTerminar"
                    @change="alCambiar(columna.clave, $event)"
                >
                    <template #item="{ element, index }">
                        <div
                            ref="tarjetas"
                            :data-tarea="element.id"
                            :tabindex="esActiva(indiceColumna, index) ? 0 : -1"
                            class="rounded-md outline-none focus-visible:ring-[2px] focus-visible:ring-ring"
                            @focus="
                                foco = { columna: indiceColumna, fila: index }
                            "
                            @keydown="
                                alPulsar($event, element, indiceColumna, index)
                            "
                        >
                            <TarjetaDeTarea
                                :tarea="element"
                                :arrastrable="props.puede.mover"
                                :eje-de-la-columna="props.agrupacion"
                            />
                        </div>
                    </template>
                </draggable>

                <p
                    v-if="columna.tareas.length === 0"
                    class="px-fino py-corto text-fino text-texto-tenue"
                >
                    {{ $t('tablero.columna.vacia') }}
                </p>

                <Button
                    v-if="tablero.hayMasEn(columna)"
                    variant="ghost"
                    size="sm"
                    :disabled="cargando === columna.clave"
                    @click="cargarMas(columna)"
                >
                    {{
                        cargando === columna.clave
                            ? $t('tablero.columna.cargando')
                            : $t('tablero.columna.cargar_mas')
                    }}
                </Button>
            </section>
        </div>

        <EstadoVacio
            v-if="tablero.columnas.value.every((c) => c.total === 0)"
            :titulo="$t('tareas.sin_tareas')"
            :cuerpo="$t('tareas.sin_tareas_ayuda')"
        >
            <Button v-if="props.puede.crear" as-child size="sm">
                <Link :href="create(props.proyecto.id)">{{
                    $t('tareas.crear')
                }}</Link>
            </Button>
            <Button as-child variant="outline" size="sm">
                <Link :href="tareasIndex(props.proyecto.id)">{{
                    $t('tablero.vistas.tabla')
                }}</Link>
            </Button>
        </EstadoVacio>

        <DialogoDeMotivo
            :abierto="tablero.motivoPendiente.value !== null"
            :transicion="tablero.motivoPendiente.value?.transicion ?? null"
            :codigo="tablero.motivoPendiente.value?.tarea.codigo ?? ''"
            @confirmar="tablero.confirmarMotivo"
            @cancelar="tablero.cancelarMotivo"
        />
    </div>
</template>
