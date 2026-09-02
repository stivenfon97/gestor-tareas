<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
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
        Cabecera de la aplicación, **solo por debajo de `md`**.

        Al retirar la miga en la 07d esta franja quedó cruzando la pantalla con
        un botón a cada extremo y nada en medio: el mismo defecto que la 07d
        había corregido en la barra de herramientas de la tabla, reaparecido
        arriba. En escritorio desaparece —el conmutador del menú se fue a la
        cabecera del propio menú y el de tema al menú de usuario—, y aquí abajo
        se queda porque **tiene trabajo**: el menú lateral es un cajón cerrado,
        así que esto es la única puerta a la navegación y el único sitio donde
        la aplicación dice cómo se llama.
    -->
    <header
        class="flex h-12 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-bloque md:hidden"
    >
        <SidebarTrigger class="-ml-1" />

        <Link
            :href="dashboard()"
            class="flex min-w-0 items-center gap-fino text-texto"
        >
            <AppLogo />
        </Link>

        <Breadcrumbs
            v-if="hayCamino"
            :breadcrumbs="props.breadcrumbs"
            class="ml-auto"
        />
    </header>
</template>
