<script setup lang="ts">
import { Info } from '@lucide/vue';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

/**
 * Clave de lectura de una columna, colgada de su cabecera.
 *
 * Una columna que comunica con símbolos —cinco iconos de tipo de tarea— no se
 * explica sola: quien abre la aplicación por primera vez ve cinco siluetas y
 * ninguna forma de saber cuál es cuál. El `title` de cada icono resuelve la
 * duda de uno en uno; la leyenda las resuelve todas de golpe.
 *
 * **El contenido lo pone la página**, por un slot `leyenda-{columna}`: aquí
 * dentro no puede haber ni una referencia a tareas, clientes o proyectos.
 */
defineProps<{ etiqueta: string }>();
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <button
                type="button"
                class="inline-flex size-5 shrink-0 items-center justify-center rounded-sm text-texto-tenue opacity-60 hover:bg-fila-hover hover:opacity-100 focus-visible:ring-[2px] focus-visible:ring-ring focus-visible:outline-none"
                :title="$t('comun.tabla.leyenda_de', { columna: etiqueta })"
                :aria-label="
                    $t('comun.tabla.leyenda_de', { columna: etiqueta })
                "
            >
                <Info class="size-3.5" aria-hidden="true" />
            </button>
        </PopoverTrigger>

        <PopoverContent align="start" class="flex w-64 flex-col gap-corto">
            <p class="text-micro font-medium text-texto-suave">
                {{ $t('comun.tabla.leyenda_de', { columna: etiqueta }) }}
            </p>
            <slot />
        </PopoverContent>
    </Popover>
</template>
