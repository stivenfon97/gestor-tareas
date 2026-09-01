<script setup lang="ts" generic="T extends Record<string, unknown>">
import { ChevronDown } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import FiltroDeColumna from './FiltroDeColumna.vue';
import type { ColumnaDeTabla } from './tipos';

/**
 * Los filtros de columna, apilados. Solo por debajo de `md`.
 *
 * En la tabla los filtros cuelgan de la cabecera de cada columna, y esa
 * cabecera no existe cuando la tabla se convierte en tarjetas. Sin esto, en el teléfono no
 * se podría filtrar en absoluto: se verían las tareas de todos los proyectos y
 * no habría forma de reducirlas.
 *
 * Nace plegado —ocupa lo que ocupan dos tarjetas— pero **abierto si ya hay
 * algún filtro puesto**: al llegar por un enlace filtrado, lo primero que hay
 * que poder ver es por qué faltan filas.
 */
const props = defineProps<{
    columnas: ColumnaDeTabla<T>[];
    filtros: Record<string, string>;
    total: number;
}>();

const emit = defineEmits<{
    aplicar: [clave: string, valor: string, retrasar: boolean];
    limpiar: [];
}>();

const conFiltro = computed(() =>
    props.columnas.filter((columna) => columna.filtro !== undefined),
);

const abierto = ref(props.total > 0);
</script>

<template>
    <details
        v-if="conFiltro.length"
        :open="abierto"
        class="rounded-md border border-borde bg-panel"
        @toggle="abierto = ($event.target as HTMLDetailsElement).open"
    >
        <summary
            class="flex cursor-pointer items-center gap-fino px-celda py-corto text-cuerpo text-texto marker:content-none"
        >
            <ChevronDown
                class="size-4 transition-transform"
                :class="abierto && 'rotate-180'"
                aria-hidden="true"
            />
            {{ $t('comun.tabla.filtros') }}
            <span v-if="props.total > 0" class="text-fino text-texto-suave">
                ({{
                    $t('comun.tabla.filtros_activos', {
                        total: String(props.total),
                    })
                }})
            </span>
        </summary>

        <div
            class="flex flex-col gap-corto border-t border-borde px-celda py-corto"
        >
            <label
                v-for="columna in conFiltro"
                :key="columna.id"
                class="flex flex-col gap-minimo"
            >
                <span class="text-micro text-texto-suave">
                    {{ columna.etiqueta }}
                </span>
                <FiltroDeColumna
                    :filtro="columna.filtro!"
                    :etiqueta="columna.etiqueta"
                    :valor="props.filtros[columna.filtro!.clave] ?? ''"
                    comodo
                    @cambiar="
                        (valor: string, retrasar: boolean) =>
                            emit(
                                'aplicar',
                                columna.filtro!.clave,
                                valor,
                                retrasar,
                            )
                    "
                />
            </label>

            <Button
                v-if="props.total > 0"
                variant="outline"
                size="sm"
                class="self-start"
                @click="emit('limpiar')"
            >
                {{ $t('comun.tabla.limpiar_filtros') }}
            </Button>
        </div>
    </details>
</template>
