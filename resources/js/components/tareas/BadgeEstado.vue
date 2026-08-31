<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { EstadoTarea } from '@/types/tareas';

/**
 * El estado es **el único eje con relleno de color**. El tipo va como icono
 * monocromo y la prioridad como barra vertical; si los tres llevaran relleno, la
 * pantalla sería ilegible.
 */
const props = withDefaults(
    defineProps<{ estado: EstadoTarea; tamano?: 'normal' | 'pequeno' }>(),
    { tamano: 'normal' },
);

/*
 * Mapa estático con las clases escritas enteras.
 *
 * **Nunca `bg-estado-${estado}-fondo`.** El escáner de Tailwind lee el código
 * como texto: una clase compuesta en tiempo de ejecución no existe para él y la
 * utilidad no se genera. En desarrollo puede colar por el JIT y en el build de
 * producción el badge sale sin color, mucho después de darlo por terminado.
 */
const clasesPorEstado: Record<EstadoTarea, string> = {
    pendiente:
        'bg-estado-pendiente-fondo text-estado-pendiente-texto border-estado-pendiente-borde',
    en_proceso:
        'bg-estado-en-proceso-fondo text-estado-en-proceso-texto border-estado-en-proceso-borde',
    en_espera:
        'bg-estado-en-espera-fondo text-estado-en-espera-texto border-estado-en-espera-borde',
    terminada:
        'bg-estado-terminada-fondo text-estado-terminada-texto border-estado-terminada-borde',
    entregada:
        'bg-estado-entregada-fondo text-estado-entregada-texto border-estado-entregada-borde',
    cancelada:
        'bg-estado-cancelada-fondo text-estado-cancelada-texto border-estado-cancelada-borde',
};

// Reserva neutra: un estado inesperado del servidor no deja el badge sin estilo.
const clases = computed(
    () => clasesPorEstado[props.estado] ?? clasesPorEstado.en_espera,
);
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex shrink-0 items-center rounded-sm border font-medium whitespace-nowrap',
                props.tamano === 'pequeno'
                    ? 'px-fino py-[1px] text-micro'
                    : 'px-ajustado py-minimo text-fino',
                clases,
            )
        "
    >
        {{ $t(`enums.estado_tarea.${props.estado}`) }}
    </span>
</template>
