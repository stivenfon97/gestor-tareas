<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'ajustes.secciones.apariencia',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="$t('ajustes.secciones.apariencia')" />

    <h1 class="sr-only">{{ $t('ajustes.secciones.apariencia') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="$t('ajustes.apariencia.titulo')"
            :description="$t('ajustes.apariencia.descripcion')"
        />
        <AppearanceTabs />
    </div>
</template>
