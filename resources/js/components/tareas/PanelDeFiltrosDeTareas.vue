<script setup lang="ts">
import { ListFilter, Search } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

/*
 * `SelectItem` no admite el valor vacío (reka-ui lo reserva para «sin
 * selección»): «Todos» usa este centinela y en la URL sigue siendo `''`.
 */
const TODOS = '__todos__';

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
            <div class="grid gap-minimo">
                <Label
                    for="filtro-tareas-texto"
                    class="text-micro text-texto-suave"
                >
                    {{ $t('comun.buscar') }}
                </Label>
                <InputGroup>
                    <InputGroupInput
                        id="filtro-tareas-texto"
                        type="search"
                        :model-value="props.filtros.texto ?? ''"
                        :placeholder="$t('tareas.filtros.texto')"
                        @update:model-value="
                            (valor: string | number) =>
                                estado.aplicarFiltro(
                                    'texto',
                                    String(valor),
                                    true,
                                )
                        "
                    />
                    <InputGroupAddon>
                        <Search aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
            </div>

            <div
                v-for="campo in campos"
                :key="campo.clave"
                class="grid gap-minimo"
            >
                <Label
                    :for="`filtro-tareas-${campo.clave}`"
                    class="text-micro text-texto-suave"
                >
                    {{ campo.etiqueta }}
                </Label>
                <Select
                    :model-value="props.filtros[campo.clave] || TODOS"
                    @update:model-value="
                        (valor) =>
                            estado.aplicarFiltro(
                                campo.clave,
                                valor === TODOS ? '' : String(valor),
                            )
                    "
                >
                    <SelectTrigger
                        :id="`filtro-tareas-${campo.clave}`"
                        class="w-full"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="TODOS">
                            {{ $t('comun.tabla.todos') }}
                        </SelectItem>
                        <SelectItem
                            v-for="opcion in campo.opciones"
                            :key="opcion.valor"
                            :value="opcion.valor"
                        >
                            {{ opcion.etiqueta }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

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
