<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TareaController from '@/actions/App/Http/Controllers/TareaController';
import Heading from '@/components/Heading.vue';
import FormularioTarea from '@/components/tareas/FormularioTarea.vue';
import { index } from '@/routes/proyectos/tareas';
import type { OpcionesDeTarea } from '@/types/tareas';

const props = defineProps<{
    proyecto: { id: number; nombre: string; prefijo_tarea: string };
    opciones: OpcionesDeTarea;
    sugerencias: { modulo_id: number | null; prioridad_por_tipo: Record<string, string> };
    candidatasDeOrigen: Array<{ id: number; codigo: string; nombre: string }>;
    periodoAbierto: { id: number; consecutivo: number } | null;
}>();
</script>

<template>
    <Head :title="$t('tareas.crear')" />

    <div class="flex max-w-2xl flex-col gap-6">
        <Heading variant="small" :title="$t('tareas.crear')" :description="props.proyecto.nombre" />

        <!--
            Sin periodo abierto la tarea nace suelta. Se avisa antes de crearla,
            no después: asignarle periodo más tarde es trabajo manual.
        -->
        <p
            v-if="props.periodoAbierto === null"
            class="rounded-lg border border-dashed border-border p-3 text-sm text-muted-foreground"
        >
            {{ $t('tareas.sin_periodo_abierto') }}
        </p>

        <FormularioTarea
            :opciones="props.opciones"
            :accion="TareaController.store(props.proyecto.id)"
            :volver-a="index(props.proyecto.id).url"
            :prioridad-por-tipo="props.sugerencias.prioridad_por_tipo"
            :modulo-sugerido="props.sugerencias.modulo_id"
            :tareas-de-origen="props.candidatasDeOrigen"
        />
    </div>
</template>
