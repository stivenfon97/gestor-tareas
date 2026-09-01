<script setup lang="ts">
import { Bug, Code, FlaskConical, LifeBuoy, Sparkles } from '@lucide/vue';
import { computed } from 'vue';
import type { TipoTarea } from '@/types/tareas';

/**
 * El tipo se comunica con un **icono monocromo**, sin color propio: hereda el
 * color del texto que lo rodea.
 *
 * Darle color competiría con el badge de estado y la pantalla dejaría de decir
 * dónde mirar.
 *
 * Los cinco iconos se distinguen por silueta, no por detalle: llave/corchetes,
 * insecto, salvavidas, destellos y matraz no se parecen entre sí a 16px, que es
 * el tamaño al que se van a ver siempre.
 *
 * El `title` y el `aria-label` van en un `<span>` que envuelve al icono, **no
 * en el `<svg>`**: un atributo `title` sobre un `<svg>` no produce ningún globo
 * de ayuda en el navegador —hace falta un elemento `<title>` hijo—, así que
 * durante la fase 07 la columna era una hilera de cinco siluetas sin ninguna
 * forma de saber cuál era cuál. Con el envoltorio, el globo aparece; la clave
 * de lectura completa está en la leyenda de la cabecera de la columna.
 */
const props = defineProps<{ tipo: TipoTarea }>();

const iconosPorTipo = {
    desarrollo: Code,
    correccion: Bug,
    soporte: LifeBuoy,
    mejora: Sparkles,
    investigacion: FlaskConical,
} as const;

const icono = computed(() => iconosPorTipo[props.tipo] ?? Code);
const etiqueta = computed(() => `enums.tipo_tarea.${props.tipo}`);
</script>

<template>
    <span
        class="inline-flex text-texto-suave"
        role="img"
        :title="$t(etiqueta)"
        :aria-label="$t(etiqueta)"
    >
        <component :is="icono" class="size-4 shrink-0" aria-hidden="true" />
    </span>
</template>
