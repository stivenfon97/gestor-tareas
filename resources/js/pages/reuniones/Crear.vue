<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import FormularioReunion from '@/components/reuniones/FormularioReunion.vue';
import { Button } from '@/components/ui/button';
import { trans } from '@/lib/i18n';
import {
    index as indiceDeReuniones,
    store,
} from '@/routes/proyectos/reuniones';
import type { Opcion } from '@/types/tareas';

const props = defineProps<{
    proyecto: { id: number; nombre: string; prefijo_tarea: string };
    opciones: {
        tipos: Opcion[];
        visibilidades: Opcion[];
        miembros: Array<{ id: number; nombre: string }>;
    };
}>();
</script>

<template>
    <Head :title="trans('reuniones.crear')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="trans('reuniones.crear')"
            :descripcion="props.proyecto.nombre"
        >
            <template #acciones>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="indiceDeReuniones(props.proyecto.id)">{{
                        trans('comun.volver')
                    }}</Link>
                </Button>
            </template>
        </CabeceraDePagina>

        <FormularioReunion
            :proyecto="props.proyecto"
            :opciones="props.opciones"
            :accion-url="store(props.proyecto.id).url"
        />
    </div>
</template>
