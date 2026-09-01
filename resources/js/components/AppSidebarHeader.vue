<script setup lang="ts">
import { computed } from 'vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

/**
 * La miga solo aparece cuando **hay camino**.
 *
 * Con un solo nivel la miga dice «Tablero» y el `<h1>` de justo debajo dice
 * «Tablero»: el mismo nombre dos veces en cuarenta píxeles de altura. La miga
 * sirve para volver al nivel de arriba, y con un nivel no hay arriba.
 */
const hayCamino = computed(() => props.breadcrumbs.length > 1);
</script>

<template>
    <!--
        Cabecera de la aplicación: el conmutador del menú y el del tema, nada
        más. `h-12` y no `h-16`: con la miga fuera, dieciséis unidades de alto
        para dos botones era una franja de aire sobre el título de cada
        pantalla.
    -->
    <header
        class="flex h-12 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <Breadcrumbs v-if="hayCamino" :breadcrumbs="props.breadcrumbs" />
        </div>

        <!--
            El conmutador vive en la cabecera y no solo en ajustes: cambiar de
            tema es cosa de la luz de la habitación, no de la configuración de
            la cuenta, y pasa varias veces al día.
        -->
        <AppearanceTabs compacto class="ml-auto" />
    </header>
</template>
