<script setup lang="ts">
import { computed } from 'vue';
import type { PrioridadTarea } from '@/types/tareas';

/**
 * La prioridad se comunica con una **barra vertical** en el borde, no con un
 * badge: es el tercer eje y el único canal visual que queda libre una vez que el
 * estado se lleva el relleno y el tipo el icono.
 *
 * **Se entiende sin color.** El color solo confirma lo que ya dice la altura del
 * relleno sobre su carril: un cuarto, la mitad, tres cuartos, entero. Quien no
 * distingue el ámbar del rojo sigue viendo que una barra está llena y la otra a
 * la cuarta parte, que es la diferencia entre crítica y baja.
 */
const props = defineProps<{ prioridad: PrioridadTarea }>();

/*
 * Clases enteras y estáticas, nunca interpoladas: el escáner de Tailwind lee el
 * código como texto y una clase compuesta en ejecución no se genera —el fallo
 * aparece solo en el build de producción—.
 */
const colorPorPrioridad: Record<PrioridadTarea, string> = {
    baja: 'bg-prioridad-baja',
    media: 'bg-prioridad-media',
    alta: 'bg-prioridad-alta',
    critica: 'bg-prioridad-critica',
};

/** El canal que no depende del color. */
const alturaPorPrioridad: Record<PrioridadTarea, string> = {
    baja: 'h-1/4',
    media: 'h-1/2',
    alta: 'h-3/4',
    critica: 'h-full',
};

const color = computed(
    () => colorPorPrioridad[props.prioridad] ?? colorPorPrioridad.baja,
);
const altura = computed(
    () => alturaPorPrioridad[props.prioridad] ?? alturaPorPrioridad.baja,
);
</script>

<template>
    <span
        class="flex h-full min-h-4 w-[3px] shrink-0 flex-col justify-end self-stretch rounded-full bg-borde"
        :title="$t(`enums.prioridad_tarea.${props.prioridad}`)"
        :aria-label="$t(`enums.prioridad_tarea.${props.prioridad}`)"
        role="img"
    >
        <span
            class="w-full rounded-full"
            :class="[color, altura]"
            aria-hidden="true"
        />
    </span>
</template>
