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
</script>

<template>
    <component
        :is="icono"
        class="size-4 shrink-0 text-muted-foreground"
        :aria-label="$t(`enums.tipo_tarea.${props.tipo}`)"
    />
</template>
