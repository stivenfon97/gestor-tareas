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

                `max-w-contenido` acota el contenido en pantallas grandes. Sin
                tope, en 1920px una tabla de cinco columnas reparte 1600px entre
                cinco celdas medio vacías y el ojo tiene que cruzar la pantalla
                entera para leer una fila. El tope no muerde por debajo de
                1440px: ahí el ancho disponible ya es menor.

                **No lleva `flex-1`.** Estirarlo hasta el pie de la ventana era
                lo que hacía que un listado de tres filas dejara seiscientos
                píxeles de panel vacío debajo: la altura la manda el contenido,
                y por debajo se ve el fondo de la página. Ver
                docs/05-identidad-visual.md §12.
            -->
            <div
                class="mx-auto flex w-full max-w-contenido min-w-0 flex-col px-bloque py-bloque md:px-seccion md:py-seccion"
            >
                <slot />
            </div>
        </AppContent>
        <Toaster />
    </AppShell>
</template>
