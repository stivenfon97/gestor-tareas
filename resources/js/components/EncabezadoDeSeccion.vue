<script setup lang="ts">
/**
 * Subtítulo de una sección dentro de una pantalla que ya tiene su
 * `CabeceraDePagina` —hoy, las pestañas de un proyecto—.
 *
 * El `<h1>` es el del proyecto y lo pinta su layout; aquí va el `<h2>` de la
 * sección (Módulos, Periodos, Bitácora…) con su icono, el mismo que el menú
 * lateral, y sus acciones a la derecha. Sin borde inferior: la línea de las
 * pestañas ya separa la cabecera del contenido.
 */
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        titulo: string;
        icono: Component;
        descripcion?: string | null;
    }>(),
    {
        descripcion: null,
    },
);
</script>

<template>
    <header class="flex flex-col gap-corto">
        <div
            class="flex flex-col gap-corto sm:flex-row sm:items-center sm:justify-between sm:gap-bloque"
        >
            <div class="flex min-w-0 items-center gap-corto">
                <component
                    :is="icono"
                    class="size-4 shrink-0 text-texto-suave"
                    aria-hidden="true"
                />
                <h2 class="text-medio font-semibold text-texto">
                    {{ titulo }}
                </h2>
                <p
                    v-if="descripcion"
                    class="hidden truncate text-cuerpo text-texto-suave md:block"
                >
                    {{ descripcion }}
                </p>
            </div>

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
