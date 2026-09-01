<script setup lang="ts">
import { ListFilter } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import FiltroDeColumna from './FiltroDeColumna.vue';
import type { FiltroDeColumna as Filtro } from './tipos';

/**
 * El filtro de una columna, colgado de su cabecera.
 *
 * **Un control de filtro no puede heredar el ancho de su columna.** Cuando
 * vivía en una fila bajo la cabecera, el `<select>` de «Tipo» —una columna que
 * solo lleva un icono— se renderizaba como `Tc`: la celda medía cuarenta
 * píxeles y el control medía lo que la celda. Ensanchar esa columna solo movía
 * el problema a la siguiente columna estrecha que alguien añadiera.
 *
 * La solución es sacar el control de la maquetación de la tabla: el disparador
 * es un icono de tamaño fijo y el control vive en una capa flotante, que se
 * dimensiona por su contenido y no por la columna. Vale igual para una columna
 * de cuarenta píxeles que para una de cuatrocientos.
 *
 * De paso desaparece la segunda fila del `<thead>`, que era una banda de
 * controles entre la cabecera y los datos.
 */
const props = defineProps<{
    filtro: Filtro;
    etiqueta: string;
    valor: string;
}>();

const emit = defineEmits<{ cambiar: [valor: string, retrasar: boolean] }>();

const activo = computed(() => props.valor !== '');

/** Lo que dice el disparador: qué columna filtra y si ya está filtrando. */
const rotulo = computed(() => ({
    columna: props.etiqueta,
}));
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <button
                type="button"
                data-filtro-cabecera
                class="inline-flex size-5 shrink-0 items-center justify-center rounded-sm hover:bg-fila-hover focus-visible:ring-[2px] focus-visible:ring-ring focus-visible:outline-none"
                :class="
                    activo
                        ? 'text-marca'
                        : 'text-texto-tenue opacity-60 hover:opacity-100'
                "
                :title="$t('comun.tabla.filtrar_por', rotulo)"
                :aria-label="$t('comun.tabla.filtrar_por', rotulo)"
            >
                <ListFilter class="size-3.5" aria-hidden="true" />
                <span v-if="activo" class="sr-only">
                    {{ $t('comun.tabla.filtro_puesto') }}
                </span>
            </button>
        </PopoverTrigger>

        <PopoverContent align="start" class="flex w-64 flex-col gap-corto">
            <p class="text-micro font-medium text-texto-suave">
                {{ props.etiqueta }}
            </p>

            <FiltroDeColumna
                :filtro="props.filtro"
                :etiqueta="props.etiqueta"
                :valor="props.valor"
                comodo
                @cambiar="
                    (valor: string, retrasar: boolean) =>
                        emit('cambiar', valor, retrasar)
                "
            />

            <Button
                v-if="activo"
                variant="ghost"
                size="sm"
                class="self-start text-texto-suave"
                @click="emit('cambiar', '', false)"
            >
                {{ $t('comun.tabla.limpiar_filtro') }}
            </Button>
        </PopoverContent>
    </Popover>
</template>
