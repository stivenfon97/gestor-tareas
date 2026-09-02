<script setup lang="ts">
/**
 * Un listado vacío es una invitación a actuar, no un hueco.
 *
 * Y **no hay un solo vacío**: «todavía no hay datos» y «los filtros no
 * devuelven nada» son situaciones distintas con salidas distintas. Confundirlas
 * es el fallo habitual —al que filtró mal se le ofrece «crea la primera tarea»
 * cuando tiene doscientas, y se queda pensando que perdió datos—.
 *
 * Este componente solo pinta lo que le dan: quién decide cuál de los dos es el
 * caso es la vista que lo usa, y lo decide porque el servidor le dice si hay
 * filtros activos, no porque lo adivine.
 *
 * Vive en `components/` y no dentro de `TablaDatos/` desde la fase 08: lo usan
 * también el tablero y la lista agrupada, y un componente genérico metido en la
 * carpeta de otro componente es una dependencia invertida esperando a romperse.
 */
defineProps<{
    titulo: string;
    cuerpo: string;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center gap-corto px-celda py-vacio text-center"
    >
        <p class="text-medio font-medium text-texto">{{ titulo }}</p>
        <p class="max-w-md text-cuerpo text-texto-suave">{{ cuerpo }}</p>
        <div class="mt-corto flex items-center gap-corto">
            <slot />
        </div>
    </div>
</template>
