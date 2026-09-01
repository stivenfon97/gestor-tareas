<script setup lang="ts">
/**
 * Cabecera de página. Un solo patrón para las cinco pantallas.
 *
 * Antes cada página componía el título y sus botones a mano con un `flex`
 * distinto, y el resultado era que el título y el botón flotaban sin
 * estructura: nada decía dónde acaba la cabecera y empieza el contenido.
 *
 * Aquí eso está resuelto en un sitio: título, descripción opcional, acciones a
 * la derecha, una línea que separa, y por debajo de `sm` las acciones bajan a
 * su propia fila en lugar de estrujar el título.
 *
 * El slot `meta` es para lo que acompaña al título sin ser una acción —badges,
 * el rol, el prefijo—; va bajo la descripción y por encima de la línea.
 */
withDefaults(
    defineProps<{
        titulo: string;
        descripcion?: string | null;
    }>(),
    {
        descripcion: null,
    },
);
</script>

<template>
    <header class="flex flex-col gap-corto border-b border-borde pb-bloque">
        <div
            class="flex flex-col gap-corto sm:flex-row sm:items-start sm:justify-between sm:gap-bloque"
        >
            <div class="flex min-w-0 flex-col gap-minimo">
                <h1 class="text-alto font-semibold text-texto">
                    {{ titulo }}
                </h1>
                <p
                    v-if="descripcion"
                    class="max-w-3xl text-cuerpo text-texto-suave"
                >
                    {{ descripcion }}
                </p>
            </div>

            <!-- `shrink-0` para que un título largo no parta los botones. -->
            <div
                v-if="$slots.acciones"
                class="flex shrink-0 flex-wrap items-center gap-corto"
            >
                <slot name="acciones" />
            </div>
        </div>

        <div v-if="$slots.meta" class="flex flex-wrap items-center gap-celda">
            <slot name="meta" />
        </div>
    </header>
</template>
