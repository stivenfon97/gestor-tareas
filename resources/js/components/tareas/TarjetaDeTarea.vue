<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { GripVertical } from '@lucide/vue';
import BadgeEstado from '@/components/tareas/BadgeEstado.vue';
import BarraPrioridad from '@/components/tareas/BarraPrioridad.vue';
import IconoTipo from '@/components/tareas/IconoTipo.vue';
import { Badge } from '@/components/ui/badge';
import { show } from '@/routes/tareas';
import type { TarjetaDeTarea } from '@/types/vistas';

/**
 * Una tarea como tarjeta, en el tablero y en la lista.
 *
 * Reutiliza los tres componentes de ejes de la fase 07 **tal cual**: barra de
 * prioridad en el borde, icono monocromo para el tipo y badge con relleno para
 * el estado. Si los tres llevaran color, una columna de treinta tarjetas sería
 * ilegible.
 *
 * El badge de estado solo aparece cuando **no** se está agrupando por estado:
 * dentro de la columna «En proceso», repetir «En proceso» en cada tarjeta es
 * decir treinta veces lo que ya dice la cabecera.
 */
withDefaults(
    defineProps<{
        tarea: TarjetaDeTarea;
        /** Si la tarjeta se puede arrastrar. Manda el permiso del servidor. */
        arrastrable?: boolean;
        /** Se oculta el eje por el que ya está agrupada la columna. */
        ejeDeLaColumna?: string | null;
    }>(),
    { arrastrable: false, ejeDeLaColumna: null },
);
</script>

<template>
    <article
        class="flex items-stretch gap-corto rounded-md border border-borde bg-panel px-celda py-corto"
    >
        <BarraPrioridad :prioridad="tarea.prioridad" />

        <div class="flex min-w-0 flex-1 flex-col gap-fino">
            <div class="flex items-center gap-fino">
                <IconoTipo
                    v-if="ejeDeLaColumna !== 'tipo'"
                    :tipo="tarea.tipo"
                />
                <Link
                    :href="show(tarea.id)"
                    class="font-mono text-fino text-texto-suave underline-offset-4 hover:underline"
                >
                    {{ tarea.codigo }}
                </Link>

                <!--
                    El tirador es lo único que arrastra: sin él, seleccionar el
                    texto de una tarjeta empezaría un arrastre, y en táctil
                    desplazar la columna movería la tarjeta de debajo del dedo.
                -->
                <GripVertical
                    v-if="arrastrable"
                    class="tirador ml-auto size-4 shrink-0 cursor-grab text-texto-tenue"
                    aria-hidden="true"
                />
            </div>

            <p class="text-cuerpo text-texto">{{ tarea.nombre }}</p>

            <div class="flex flex-wrap items-center gap-fino">
                <BadgeEstado
                    v-if="ejeDeLaColumna !== 'estado'"
                    :estado="tarea.estado"
                    tamano="pequeno"
                />
                <span
                    v-if="tarea.modulo && ejeDeLaColumna !== 'modulo'"
                    class="truncate text-fino text-texto-suave"
                >
                    {{ tarea.modulo }}
                </span>
                <!-- Una tarea arrastrada de otro periodo se marca: al cerrar un
                     periodo es lo primero que hay que poder distinguir. -->
                <Badge v-if="tarea.arrastrada" variant="outline">
                    {{ $t('tareas.arrastrada') }}
                </Badge>
                <span
                    v-if="tarea.fecha_fin"
                    class="ml-auto font-mono text-fino text-texto-tenue"
                >
                    {{ tarea.fecha_fin }}
                </span>
            </div>
        </div>
    </article>
</template>
