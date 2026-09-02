<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, Columns3, List, Table2 } from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { index as agenda } from '@/routes/proyectos/agenda';
import { index as lista } from '@/routes/proyectos/lista';
import { index as tablero } from '@/routes/proyectos/tablero';
import { index as tabla } from '@/routes/proyectos/tareas';
import type { TipoDeVista } from '@/types/vistas';

/**
 * Las cuatro formas de ver las mismas tareas.
 *
 * **Los filtros viajan al cambiar de vista.** Si no lo hicieran, pasar de la
 * tabla al tablero para ver de otra manera lo que acabas de filtrar te
 * devolvería el proyecto entero, y el filtro habría que ponerlo dos veces.
 *
 * No hay una quinta. Cuatro formas ya son muchas para mantener, y las que
 * sobran acaban desactualizándose respecto a las que se usan.
 */
const props = defineProps<{
    proyecto: { id: number };
    actual: TipoDeVista;
    /** Los filtros de ahora, tal y como están en la URL. */
    filtros: Record<string, string>;
}>();

const parametros = computed(() => {
    const activos = Object.entries(props.filtros).filter(
        ([, valor]) => valor !== '' && valor !== null,
    );

    return activos.length > 0
        ? { filter: Object.fromEntries(activos) }
        : undefined;
});

const enlace = (constructor: (id: number) => { url: string }): string => {
    const base = constructor(props.proyecto.id).url;

    if (parametros.value === undefined) {
        return base;
    }

    const consulta = new URLSearchParams();

    for (const [clave, valor] of Object.entries(parametros.value.filter)) {
        consulta.set(`filter[${clave}]`, String(valor));
    }

    return `${base}?${consulta.toString()}`;
};

const vistas = computed(() => [
    {
        tipo: 'tabla' as const,
        Icono: Table2,
        clave: 'tablero.vistas.tabla',
        href: enlace(tabla),
    },
    {
        tipo: 'kanban' as const,
        Icono: Columns3,
        clave: 'tablero.vistas.tablero',
        href: enlace(tablero),
    },
    {
        tipo: 'lista' as const,
        Icono: List,
        clave: 'tablero.vistas.lista',
        href: enlace(lista),
    },
    {
        tipo: 'calendario' as const,
        Icono: CalendarDays,
        clave: 'tablero.vistas.calendario',
        href: enlace(agenda),
    },
]);
</script>

<template>
    <div
        class="inline-flex gap-minimo rounded-md border border-borde bg-panel-cabecera p-minimo"
        role="group"
        :aria-label="$t('tablero.vistas.cambiar')"
    >
        <Link
            v-for="vista in vistas"
            :key="vista.tipo"
            :href="vista.href"
            :aria-current="props.actual === vista.tipo ? 'page' : undefined"
            :title="$t(vista.clave)"
            :class="
                cn(
                    'flex items-center gap-fino rounded-sm px-ajustado py-fino text-fino transition-colors',
                    props.actual === vista.tipo
                        ? 'bg-panel text-texto'
                        : 'text-texto-suave hover:bg-fila-hover hover:text-texto',
                )
            "
        >
            <component :is="vista.Icono" class="size-4" aria-hidden="true" />
            <span class="sr-only sm:not-sr-only">{{ $t(vista.clave) }}</span>
        </Link>
    </div>
</template>
