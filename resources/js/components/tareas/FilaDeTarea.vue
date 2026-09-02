<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import BadgeEstado from '@/components/tareas/BadgeEstado.vue';
import BarraPrioridad from '@/components/tareas/BarraPrioridad.vue';
import IconoTipo from '@/components/tareas/IconoTipo.vue';
import { Badge } from '@/components/ui/badge';
import { show } from '@/routes/tareas';
import type { TarjetaDeTarea } from '@/types/vistas';

/**
 * Una tarea, una fila. Existe solo para la lista agrupada (0.3 de la fase
 * 09): `TarjetaDeTarea.vue` es del tablero, y ahí tres líneas por tarjeta
 * tienen sentido porque la columna es estrecha y hay poco de qué presumir a
 * lo ancho. En la lista la columna es toda la pantalla y setenta tareas en
 * tres líneas cada una es un scroll interminable para repasar un proyecto
 * "de un vistazo" — que es literalmente para lo que existe esta vista.
 *
 * Código, nombre, estado y lo que quepa, **en la misma línea**: la densidad
 * se acerca a la de la tabla, no a la del tablero.
 */
withDefaults(
    defineProps<{
        tarea: TarjetaDeTarea;
        /** Se oculta el eje por el que ya está agrupada la sección. */
        ejeDeLaColumna?: string | null;
    }>(),
    { ejeDeLaColumna: null },
);
</script>

<template>
    <Link
        :href="show(tarea.id)"
        class="flex items-stretch gap-corto rounded-sm border border-borde bg-panel px-corto py-fino hover:bg-fila-hover"
    >
        <BarraPrioridad :prioridad="tarea.prioridad" />

        <IconoTipo v-if="ejeDeLaColumna !== 'tipo'" :tipo="tarea.tipo" />

        <span class="shrink-0 font-mono text-fino text-texto-suave">
            {{ tarea.codigo }}
        </span>

        <span class="min-w-0 flex-1 truncate text-cuerpo text-texto">
            {{ tarea.nombre }}
        </span>

        <span
            v-if="tarea.modulo && ejeDeLaColumna !== 'modulo'"
            class="hidden shrink-0 truncate text-fino text-texto-suave sm:inline"
        >
            {{ tarea.modulo }}
        </span>

        <Badge v-if="tarea.arrastrada" variant="outline" class="shrink-0">
            {{ $t('tareas.arrastrada') }}
        </Badge>

        <span
            v-if="tarea.fecha_fin"
            class="hidden shrink-0 font-mono text-fino text-texto-tenue sm:inline"
        >
            {{ tarea.fecha_fin }}
        </span>

        <BadgeEstado
            v-if="ejeDeLaColumna !== 'estado'"
            :estado="tarea.estado"
            tamano="pequeno"
            class="shrink-0"
        />
    </Link>
</template>
