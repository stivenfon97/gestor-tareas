<script setup lang="ts">
import { computed } from 'vue';
import type { PrioridadTarea } from '@/types/tareas';

/**
 * La prioridad se comunica con una **barra vertical** en el borde, no con un
 * badge: es el tercer eje y el único canal visual que queda libre una vez que el
 * estado se lleva el relleno y el tipo el icono.
 */
const props = defineProps<{ prioridad: PrioridadTarea }>();

// Clases enteras y estáticas, nunca interpoladas: el escáner de Tailwind lee el
// código como texto y una clase compuesta en ejecución no se genera.
const clasesPorPrioridad: Record<PrioridadTarea, string> = {
    baja: 'bg-prioridad-baja',
    media: 'bg-prioridad-media',
    alta: 'bg-prioridad-alta',
    critica: 'bg-prioridad-critica',
};

const clases = computed(() => clasesPorPrioridad[props.prioridad] ?? clasesPorPrioridad.baja);
</script>

<template>
    <span
        class="inline-block w-1 shrink-0 self-stretch rounded-full"
        :class="clases"
        :title="$t(`enums.prioridad_tarea.${props.prioridad}`)"
        :aria-label="$t(`enums.prioridad_tarea.${props.prioridad}`)"
        role="img"
    />
</template>
