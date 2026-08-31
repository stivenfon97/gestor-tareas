<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { EnlacePaginacion } from '@/types/paginacion';

/**
 * Paginación de un paginador de Laravel.
 *
 * Las etiquetas «anterior» y «siguiente» llegan del servidor con entidades HTML
 * (`&laquo;`) y en el idioma del backend, así que se reemplazan por texto
 * traducido: el resto son números y se muestran tal cual.
 */
const props = defineProps<{ enlaces: EnlacePaginacion[] }>();

const visible = computed(() => props.enlaces.length > 3);

const etiqueta = (enlace: EnlacePaginacion, indice: number): string | null => {
    if (indice === 0 || indice === props.enlaces.length - 1) {
        return null;
    }

    return enlace.label;
};
</script>

<template>
    <nav v-if="visible" class="flex flex-wrap items-center gap-1" :aria-label="$t('comun.paginacion')">
        <template v-for="(enlace, indice) in props.enlaces" :key="indice">
            <Link
                v-if="enlace.url"
                :href="enlace.url"
                preserve-scroll
                class="rounded-md border border-border px-3 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                :class="enlace.active ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'"
            >
                {{
                    etiqueta(enlace, indice) ??
                    (indice === 0 ? $t('comun.anterior') : $t('comun.siguiente'))
                }}
            </Link>
            <span
                v-else
                class="rounded-md border border-border px-3 py-1.5 text-sm text-muted-foreground opacity-50"
            >
                {{
                    etiqueta(enlace, indice) ??
                    (indice === 0 ? $t('comun.anterior') : $t('comun.siguiente'))
                }}
            </span>
        </template>
    </nav>
</template>
