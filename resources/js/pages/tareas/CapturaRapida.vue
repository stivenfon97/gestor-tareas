<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import CapturaRapidaFormulario from '@/components/tareas/CapturaRapidaFormulario.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/proyectos/tareas';
import { previsualizar, store } from '@/routes/proyectos/tareas/rapida';
import type { Opcion } from '@/types/tareas';

const props = defineProps<{
    proyecto: { id: number; nombre: string; prefijo_tarea: string };
    opciones: { estados: Opcion[]; tipos: Opcion[]; prioridades: Opcion[] };
    periodoAbierto: number | null;
}>();
</script>

<template>
    <Head :title="$t('tareas.rapida.titulo')" />

    <div class="flex flex-col gap-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                :title="$t('tareas.rapida.titulo')"
                :description="$t('tareas.rapida.descripcion')"
            />
            <Button as-child variant="ghost" size="sm">
                <Link :href="index(props.proyecto.id)">{{
                    $t('comun.volver')
                }}</Link>
            </Button>
        </div>

        <CapturaRapidaFormulario
            :opciones="props.opciones"
            :periodo-abierto="props.periodoAbierto"
            :previsualizar-url="previsualizar(props.proyecto.id).url"
            :store-url="store(props.proyecto.id).url"
        />
    </div>
</template>
