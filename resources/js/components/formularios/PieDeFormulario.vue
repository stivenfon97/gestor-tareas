<script setup lang="ts">
/**
 * Acciones de un formulario, en el pie de `TarjetaDeFormulario`.
 *
 * Siempre en el mismo orden y sitio: a la derecha, «Cancelar» como secundaria
 * (`outline`) y la acción que guarda como primaria. Mientras se envía, el
 * botón se deshabilita y enseña un `Spinner`: sin eso un segundo clic mandaba
 * el formulario dos veces.
 *
 * El slot por defecto va a la izquierda, para una ayuda corta o una acción
 * secundaria (por ejemplo, eliminar en un formulario de edición).
 */
import type { UrlMethodPair } from '@inertiajs/core';
import { Link } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

withDefaults(
    defineProps<{
        procesando: boolean;
        cancelar?: string | UrlMethodPair;
        etiqueta?: string;
    }>(),
    {
        cancelar: undefined,
        etiqueta: undefined,
    },
);
</script>

<template>
    <div
        class="flex w-full flex-col-reverse gap-corto sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="text-fino text-texto-suave">
            <slot />
        </div>

        <div
            class="flex flex-col-reverse gap-corto sm:flex-row sm:items-center"
        >
            <Button v-if="cancelar !== undefined" as-child variant="outline">
                <Link :href="cancelar">{{ $t('comun.cancelar') }}</Link>
            </Button>
            <Button type="submit" :disabled="procesando">
                <Spinner v-if="procesando" />
                <Save v-else aria-hidden="true" />
                {{ etiqueta ?? $t('comun.guardar') }}
            </Button>
        </div>
    </div>
</template>
