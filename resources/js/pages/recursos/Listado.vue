<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';

/**
 * Listado genérico de los recursos con guarda.
 *
 * Existe para que la autorización de la fase 04 tenga superficie que proteger.
 * Cada recurso recibe su interfaz propia en su fase (05, 06, 09 y 10).
 */
const props = defineProps<{
    titulo: string;
    columnas: string[];
    filas: Array<Record<string, unknown>>;
}>();
</script>

<template>
    <Head :title="$t(props.titulo)" />

    <div class="flex flex-col gap-6">
        <Heading variant="small" :title="$t(props.titulo)" />

        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted text-muted-foreground">
                    <tr>
                        <th v-for="columna in props.columnas" :key="columna" class="px-4 py-2 font-medium">
                            {{ $t(`recursos.columnas.${columna}`) }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(fila, indice) in props.filas" :key="indice" class="border-t border-border">
                        <td v-for="columna in props.columnas" :key="columna" class="px-4 py-2">
                            {{ fila[columna] }}
                        </td>
                    </tr>
                    <tr v-if="props.filas.length === 0">
                        <td :colspan="props.columnas.length" class="px-4 py-6 text-center text-muted-foreground">
                            {{ $t('comun.sin_resultados') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
