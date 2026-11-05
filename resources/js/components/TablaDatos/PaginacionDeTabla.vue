<script setup lang="ts">
/**
 * Pie de la tabla: qué se está viendo, cuántas filas por página y cómo moverse.
 *
 * **Se pinta siempre**, también con una sola página. Esconderlo dejaba la tabla
 * sin pie y sin forma de saber si había más resultados ni de cambiar el tamaño
 * de página; con una página los botones nacen deshabilitados, que es la
 * respuesta honesta a «¿hay más?».
 *
 * Por debajo de `md` se queda en lo que se usa con el pulgar: el rango, y
 * anterior y siguiente.
 */
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from '@lucide/vue';
import { computed, useId } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatearNumero } from '@/lib/formato';
import type { PaginacionDeTabla } from './tipos';

const props = defineProps<{
    paginacion: PaginacionDeTabla;
    compacta: boolean;
}>();

const emit = defineEmits<{
    irAPagina: [pagina: number];
    cambiarPorPagina: [porPagina: number];
}>();

/** Tamaños de página que admite el servidor (`EstadoDeTabla::TAMANOS`). */
const TAMANOS_DE_PAGINA = [15, 30, 50, 100] as const;

const idPorPagina = useId();

const actual = computed(() => props.paginacion.current_page);
const ultima = computed(() => Math.max(1, props.paginacion.last_page));
const esPrimera = computed(() => actual.value <= 1);
const esUltima = computed(() => actual.value >= ultima.value);

const rango = computed(() => {
    const { total, per_page: porPagina } = props.paginacion;

    if (total === 0) {
        return { desde: 0, hasta: 0 };
    }

    const desde = (actual.value - 1) * porPagina + 1;

    return { desde, hasta: Math.min(desde + porPagina - 1, total) };
});
</script>

<template>
    <div
        class="flex flex-wrap items-center justify-between gap-corto border-t border-borde bg-panel-cabecera px-celda py-corto text-fino text-texto-suave"
        data-paginacion-de-tabla
    >
        <span>
            {{
                $t('comun.tabla.mostrando', {
                    desde: formatearNumero(rango.desde),
                    hasta: formatearNumero(rango.hasta),
                    total: formatearNumero(props.paginacion.total),
                })
            }}
        </span>

        <div class="flex items-center gap-bloque">
            <div v-if="!props.compacta" class="flex items-center gap-corto">
                <!-- `<label>` nativo y no `Label`: el de shadcn fija `text-sm` y
                     tailwind-merge no reconoce `text-fino` como tamaño para
                     sustituirlo, así que el rótulo salía más grande que el pie. -->
                <label :for="idPorPagina" class="text-fino text-texto-suave">
                    {{ $t('comun.tabla.por_pagina') }}
                </label>
                <Select
                    :model-value="String(props.paginacion.per_page)"
                    @update:model-value="
                        (valor) => emit('cambiarPorPagina', Number(valor))
                    "
                >
                    <SelectTrigger :id="idPorPagina" size="sm" class="w-18">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="opcion in TAMANOS_DE_PAGINA"
                            :key="opcion"
                            :value="String(opcion)"
                        >
                            {{ opcion }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <span v-if="!props.compacta" class="text-texto">
                {{
                    $t('comun.tabla.pagina_de', {
                        actual: formatearNumero(actual),
                        total: formatearNumero(ultima),
                    })
                }}
            </span>

            <div class="flex items-center gap-fino">
                <Button
                    v-if="!props.compacta"
                    variant="outline"
                    size="icon-sm"
                    :disabled="esPrimera"
                    :aria-label="$t('comun.tabla.primera_pagina')"
                    :title="$t('comun.tabla.primera_pagina')"
                    @click="emit('irAPagina', 1)"
                >
                    <ChevronsLeft aria-hidden="true" />
                </Button>
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="esPrimera"
                    :aria-label="$t('comun.anterior')"
                    :title="$t('comun.anterior')"
                    @click="emit('irAPagina', actual - 1)"
                >
                    <ChevronLeft aria-hidden="true" />
                </Button>
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="esUltima"
                    :aria-label="$t('comun.siguiente')"
                    :title="$t('comun.siguiente')"
                    @click="emit('irAPagina', actual + 1)"
                >
                    <ChevronRight aria-hidden="true" />
                </Button>
                <Button
                    v-if="!props.compacta"
                    variant="outline"
                    size="icon-sm"
                    :disabled="esUltima"
                    :aria-label="$t('comun.tabla.ultima_pagina')"
                    :title="$t('comun.tabla.ultima_pagina')"
                    @click="emit('irAPagina', ultima)"
                >
                    <ChevronsRight aria-hidden="true" />
                </Button>
            </div>
        </div>
    </div>
</template>
