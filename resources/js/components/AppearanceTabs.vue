<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';
import { cn } from '@/lib/utils';

/**
 * Conmutador de tema de **tres posiciones**: claro, oscuro y sistema.
 *
 * Las tres son necesarias. Con solo dos, elegir «claro» a mediodía deja la
 * aplicación en claro por la noche aunque el sistema entero haya cambiado.
 * «Sistema» es una elección, no la ausencia de elección, y por eso ocupa su
 * propio botón.
 *
 * La preferencia la persiste `useAppearance` en `localStorage` (cliente) y en
 * una cookie (para que el servidor pinte ya la clase `.dark` y no haya
 * destello en la primera carga).
 */
const { appearance, updateAppearance } = useAppearance();

const opciones = [
    { valor: 'light', Icono: Sun, clave: 'comun.tema.claro' },
    { valor: 'dark', Icono: Moon, clave: 'comun.tema.oscuro' },
    { valor: 'system', Icono: Monitor, clave: 'comun.tema.sistema' },
] as const;

withDefaults(defineProps<{ compacto?: boolean }>(), { compacto: false });
</script>

<template>
    <div
        class="inline-flex gap-minimo rounded-md border border-borde bg-panel-cabecera p-minimo"
        role="group"
        :aria-label="$t('comun.tema.titulo')"
    >
        <button
            v-for="{ valor, Icono, clave } in opciones"
            :key="valor"
            type="button"
            :aria-pressed="appearance === valor"
            :title="$t(clave)"
            :class="
                cn(
                    'flex items-center gap-fino rounded-sm px-ajustado py-fino text-fino transition-colors',
                    appearance === valor
                        ? 'bg-panel text-texto'
                        : 'text-texto-suave hover:bg-fila-hover hover:text-texto',
                )
            "
            @click="updateAppearance(valor)"
        >
            <component :is="Icono" class="size-4" aria-hidden="true" />
            <span :class="compacto ? 'sr-only' : ''">{{ $t(clave) }}</span>
        </button>
    </div>
</template>
