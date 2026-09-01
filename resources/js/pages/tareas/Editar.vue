<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TareaController from '@/actions/App/Http/Controllers/TareaController';
import Heading from '@/components/Heading.vue';
import FormularioTarea from '@/components/tareas/FormularioTarea.vue';
import { show } from '@/routes/tareas';
import type {
    OpcionesDeTarea,
    PrioridadTarea,
    TipoTarea,
} from '@/types/tareas';

const props = defineProps<{
    proyecto: { id: number; nombre: string; prefijo_tarea: string };
    tarea: {
        id: number;
        codigo: string;
        nombre: string;
        descripcion: string | null;
        estado: string;
        tipo: TipoTarea;
        prioridad: PrioridadTarea;
        modulo_id: number | null;
        periodo_id: number | null;
        tarea_origen_id: number | null;
    };
    opciones: OpcionesDeTarea;
    candidatasDeOrigen: Array<{ id: number; codigo: string; nombre: string }>;
}>();
</script>

<template>
    <Head :title="$t('tareas.editar')" />

    <div class="flex max-w-2xl flex-col gap-6">
        <Heading
            variant="small"
            :title="$t('tareas.editar')"
            :description="`${props.tarea.codigo} — ${props.tarea.nombre}`"
        />

        <FormularioTarea
            :opciones="props.opciones"
            :accion="TareaController.update(props.tarea.id)"
            :volver-a="show(props.tarea.id).url"
            :tipo-fijado="props.tarea.tipo"
            :inicial="{
                nombre: props.tarea.nombre,
                descripcion: props.tarea.descripcion,
                tipo: props.tarea.tipo,
                prioridad: props.tarea.prioridad,
                modulo_id: props.tarea.modulo_id,
                periodo_id: props.tarea.periodo_id,
                tarea_origen_id: props.tarea.tarea_origen_id,
            }"
            :tareas-de-origen="props.candidatasDeOrigen"
        />
    </div>
</template>
