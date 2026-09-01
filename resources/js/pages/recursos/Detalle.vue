<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';

/** Detalle genérico. Misma razón de ser que `recursos/Listado.vue`. */
const props = defineProps<{
    titulo: string;
    campos: Array<{ clave: string; valor: string | number | null }>;
}>();
</script>

<template>
    <Head :title="$t(props.titulo)" />

    <div class="flex max-w-xl flex-col gap-6">
        <Heading variant="small" :title="$t(props.titulo)" />

        <dl class="divide-y divide-border rounded-lg border border-border">
            <div
                v-for="campo in props.campos"
                :key="campo.clave"
                class="flex justify-between gap-4 px-4 py-3 text-sm"
            >
                <dt class="text-muted-foreground">
                    {{ $t(`recursos.columnas.${campo.clave}`) }}
                </dt>
                <dd class="text-right">{{ campo.valor }}</dd>
            </div>
        </dl>
    </div>
</template>
