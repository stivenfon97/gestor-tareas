<script setup lang="ts">
/**
 * Estado de carga que **no hace saltar el diseño**: mismas filas y mismas
 * columnas que la tabla que va a aparecer, dentro del mismo `<tbody>`, así que
 * hereda sus anchos.
 *
 * Un spinner centrado colapsaría la altura del contenedor y la página daría un
 * salto justo cuando el usuario ya movió el cursor a donde creía que iba a
 * estar la primera fila.
 */
defineProps<{
    columnas: number;
    filas: number;
    altoDeFila: string;
}>();
</script>

<template>
    <tr
        v-for="fila in filas"
        :key="fila"
        class="border-t border-borde"
        :class="altoDeFila"
        aria-hidden="true"
    >
        <td v-for="columna in columnas" :key="columna" class="px-celda">
            <span class="block h-2 animate-pulse rounded-full bg-borde" />
        </td>
    </tr>
</template>
