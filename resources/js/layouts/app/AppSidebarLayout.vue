<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />

            <!--
                Margen de contenedor. Antes el contenido salía pegado al canto
                del panel —la tabla, la cabecera y el pie—, y la aplicación se
                leía como un esqueleto sin terminar. Se pone aquí y no en cada
                página para que sea el mismo en todas.

                `min-w-0` es lo que permite que una tabla ancha se desplace
                dentro de su panel en lugar de empujar el documento entero: sin
                él, un hijo de un flex no baja de su ancho de contenido.
            -->
            <div
                class="flex min-w-0 flex-1 flex-col px-bloque py-bloque md:px-seccion md:py-seccion"
            >
                <slot />
            </div>
        </AppContent>
        <Toaster />
    </AppShell>
</template>
