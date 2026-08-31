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
 * caso es `TablaDatos`, y lo decide porque el servidor le dice si hay filtros
 * activos, no porque lo adivine.
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
