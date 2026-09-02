<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import { computed, ref } from 'vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import EstadoVacio from '@/components/EstadoVacio.vue';
import PanelDeFiltrosDeTareas from '@/components/tareas/PanelDeFiltrosDeTareas.vue';
import SelectorDeVista from '@/components/tareas/SelectorDeVista.vue';
import TarjetaDeTarea from '@/components/tareas/TarjetaDeTarea.vue';
import VistasGuardadas from '@/components/tareas/VistasGuardadas.vue';
import { Button } from '@/components/ui/button';
import { show as mostrarProyecto } from '@/routes/proyectos';
import { create } from '@/routes/proyectos/tareas';
import type { OpcionesDeTarea } from '@/types/tareas';
import type {
    Agrupacion,
    ColumnaDeTablero,
    ConfiguracionDeVista,
    VistaGuardada,
} from '@/types/vistas';

/**
 * Las mismas tareas de arriba abajo, por grupos plegables.
 *
 * Es la vista para **repasar un proyecto entero antes de una reunión**: el
 * tablero enseña en qué estado está cada cosa, y esto enseña qué hay en cada
 * módulo sin desplazarse a lo ancho.
 *
 * Aquí no se arrastra nada. Reordenar leyendo de arriba abajo no es lo que se
 * viene a hacer, y un arrastre que casi nunca se usa es un arrastre que sobre
 * todo estorba al seleccionar texto.
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
    grupos: ColumnaDeTablero[];
    limite: number;
    filtros: Record<string, string>;
    vistas: VistaGuardada[];
    tipoDeVista: 'lista';
    opciones: OpcionesDeTarea;
}>();

/*
 * Los grupos plegados viven en memoria y no en la URL.
 *
 * Es una preferencia de lectura de quien está mirando ahora, como la densidad
 * de fila de la tabla, y no parte de lo que se comparte con un enlace: mandar
 * «las tareas del módulo X» con ese grupo plegado sería mandar una pantalla que
 * esconde justo lo que se quería enseñar.
 */
const plegados = ref<Set<string>>(new Set());

const alternar = (clave: string): void => {
    const copia = new Set(plegados.value);

    if (copia.has(clave)) {
        copia.delete(clave);
    } else {
        copia.add(clave);
    }

    plegados.value = copia;
};

const configuracionActual = computed((): ConfiguracionDeVista => {
    const activos = Object.entries(props.filtros).filter(
        ([, valor]) => valor !== '' && valor !== null,
    );

    return {
        ...(activos.length > 0
            ? { filter: Object.fromEntries(activos) as Record<string, string> }
            : {}),
        ...(props.agrupacion !== 'modulo' ? { agrupar: props.agrupacion } : {}),
    };
});

const cambiarAgrupacion = (valor: string): void => {
    router.get(
        window.location.pathname,
        { ...configuracionActual.value, agrupar: valor },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const vacia = computed(() => props.grupos.every((grupo) => grupo.total === 0));
</script>

<template>
    <Head :title="$t('tablero.lista.titulo')" />

    <div class="flex min-w-0 flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="$t('tablero.lista.titulo')"
            :descripcion="props.proyecto.nombre"
        >
            <template #acciones>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="mostrarProyecto(props.proyecto.id)">{{
                        $t('comun.volver')
                    }}</Link>
                </Button>
            </template>

            <template #meta>
                <SelectorDeVista
                    :proyecto="props.proyecto"
                    actual="lista"
                    :filtros="props.filtros"
                />
                <VistasGuardadas
                    :vistas="props.vistas"
                    tipo="lista"
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
                :otros="{ agrupar: props.agrupacion }"
            />
        </div>

        <EstadoVacio
            v-if="vacia"
            :titulo="$t('tareas.sin_tareas')"
            :cuerpo="$t('tareas.sin_tareas_ayuda')"
        >
            <Button as-child size="sm">
                <Link :href="create(props.proyecto.id)">{{
                    $t('tareas.crear')
                }}</Link>
            </Button>
        </EstadoVacio>

        <div
            v-else
            class="flex min-w-0 flex-col overflow-hidden rounded-md border border-borde-fuerte bg-panel"
        >
            <section
                v-for="grupo in props.grupos"
                :key="grupo.clave"
                class="border-b border-borde last:border-b-0"
            >
                <h2>
                    <button
                        type="button"
                        class="flex w-full items-center gap-corto bg-panel-cabecera px-celda py-corto text-left hover:bg-fila-hover"
                        :aria-expanded="!plegados.has(grupo.clave)"
                        @click="alternar(grupo.clave)"
                    >
                        <ChevronDown
                            class="size-4 shrink-0 text-texto-suave transition-transform"
                            :class="plegados.has(grupo.clave) && '-rotate-90'"
                            aria-hidden="true"
                        />
                        <span
                            class="truncate text-cuerpo font-medium text-texto"
                        >
                            {{ grupo.etiqueta }}
                        </span>
                        <!-- El conteo por grupo va en la cabecera: es lo que se
                             lee al repasar sin desplegar nada. -->
                        <span
                            class="ml-auto shrink-0 font-mono text-fino text-texto-suave"
                        >
                            {{
                                grupo.tareas.length === grupo.total
                                    ? grupo.total
                                    : $t('tablero.columna.cargadas', {
                                          cargadas: String(grupo.tareas.length),
                                          total: String(grupo.total),
                                      })
                            }}
                        </span>
                    </button>
                </h2>

                <div
                    v-if="!plegados.has(grupo.clave)"
                    class="flex flex-col gap-corto px-celda py-corto"
                >
                    <TarjetaDeTarea
                        v-for="tarea in grupo.tareas"
                        :key="tarea.id"
                        :tarea="tarea"
                        :eje-de-la-columna="props.agrupacion"
                    />

                    <p
                        v-if="grupo.tareas.length === 0"
                        class="py-corto text-fino text-texto-tenue"
                    >
                        {{ $t('tablero.lista.vacio') }}
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>
