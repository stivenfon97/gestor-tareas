<script setup lang="ts">
import { ListFilter } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useEstadoEnLaUrl } from '@/composables/useEstadoEnLaUrl';
import { trans } from '@/lib/i18n';
import type { OpcionesDeTarea } from '@/types/tareas';

/**
 * Los filtros de tareas, para las vistas que no son la tabla.
 *
 * La tabla los cuelga de la cabecera de cada columna; el tablero, la lista y el
 * calendario no tienen cabeceras de columna, así que van aquí, en una capa
 * flotante.
 *
 * Usa `useEstadoEnLaUrl` —el núcleo, no la capa de tabla— porque comparte los
 * filtros y el retraso al escribir con el listado, pero no tiene orden
 * multicolumna, ni columnas visibles, ni páginas.
 */
const props = defineProps<{
    filtros: Record<string, string>;
    opciones: OpcionesDeTarea;
    /** Parámetros propios de la vista que hay que conservar al filtrar. */
    otros?: Record<string, string>;
}>();

const estado = useEstadoEnLaUrl({
    get url() {
        return window.location.pathname;
    },
    filtros: () => props.filtros,
    otros: () => props.otros ?? {},
});

const activos = computed(
    () =>
        Object.values(props.filtros).filter(
            (valor) => valor !== '' && valor !== null,
        ).length,
);

const campos = computed(() => [
    {
        clave: 'estado',
        etiqueta: trans('comun.estado'),
        opciones: props.opciones.estados,
    },
    {
        clave: 'tipo',
        etiqueta: trans('tareas.tipo'),
        opciones: props.opciones.tipos,
    },
    {
        clave: 'prioridad',
        etiqueta: trans('tareas.prioridad'),
        opciones: props.opciones.prioridades,
    },
    {
        clave: 'modulo_id',
        etiqueta: trans('tareas.modulo'),
        opciones: props.opciones.modulos.map((modulo) => ({
            valor: String(modulo.id),
            etiqueta: modulo.ruta,
        })),
    },
]);
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button variant="outline" size="sm" class="gap-fino">
                <ListFilter class="size-4" aria-hidden="true" />
                {{ $t('comun.tabla.filtros') }}
                <span v-if="activos > 0" class="text-marca"
                    >({{ activos }})</span
                >
            </Button>
        </PopoverTrigger>

        <PopoverContent align="start" class="flex w-72 flex-col gap-corto">
            <label class="flex flex-col gap-minimo">
                <span class="text-micro text-texto-suave">
                    {{ $t('comun.buscar') }}
                </span>
                <input
                    type="search"
                    class="h-8 w-full min-w-0 rounded-sm border border-borde bg-panel px-corto text-cuerpo text-texto"
                    :value="props.filtros.texto ?? ''"
                    :aria-label="$t('comun.buscar')"
                    @input="
                        estado.aplicarFiltro(
                            'texto',
                            ($event.target as HTMLInputElement).value,
                            true,
                        )
                    "
                />
            </label>

            <label
                v-for="campo in campos"
                :key="campo.clave"
                class="flex flex-col gap-minimo"
            >
                <span class="text-micro text-texto-suave">
                    {{ campo.etiqueta }}
                </span>
                <select
                    class="h-8 w-full min-w-0 rounded-sm border border-borde bg-panel px-corto text-cuerpo text-texto"
                    :value="props.filtros[campo.clave] ?? ''"
                    :aria-label="
                        $t('comun.tabla.filtrar_por', {
                            columna: campo.etiqueta,
                        })
                    "
                    @change="
                        estado.aplicarFiltro(
                            campo.clave,
                            ($event.target as HTMLSelectElement).value,
                        )
                    "
                >
                    <option value="">{{ $t('comun.tabla.todos') }}</option>
                    <option
                        v-for="opcion in campo.opciones"
                        :key="opcion.valor"
                        :value="opcion.valor"
                    >
                        {{ opcion.etiqueta }}
                    </option>
                </select>
            </label>

            <Button
                v-if="activos > 0"
                variant="ghost"
                size="sm"
                class="self-start text-texto-suave"
                @click="estado.limpiarFiltros()"
            >
                {{ $t('comun.tabla.limpiar_filtros') }}
            </Button>
        </PopoverContent>
    </Popover>
</template>
