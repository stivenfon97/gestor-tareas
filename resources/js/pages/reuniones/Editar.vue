<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import FormularioReunion from '@/components/reuniones/FormularioReunion.vue';
import { Button } from '@/components/ui/button';
import { trans } from '@/lib/i18n';
import { show as mostrarReunion, update } from '@/routes/reuniones';
import type { DocumentoTipTap } from '@/types/reuniones';
import type { Opcion } from '@/types/tareas';

const props = defineProps<{
    proyecto: { id: number; nombre: string; prefijo_tarea: string };
    reunion: {
        id: number;
        fecha: string;
        tipo: string;
        titulo: string;
        contenido_json: DocumentoTipTap;
        contenido_html: string;
        acuerdos: string | null;
        visibilidad: string;
        asistentes: Array<{
            usuario_id: number | null;
            nombre: string;
            organizacion: string | null;
        }>;
    };
    opciones: {
        tipos: Opcion[];
        visibilidades: Opcion[];
        miembros: Array<{ id: number; nombre: string }>;
    };
}>();
</script>

<template>
    <Head :title="trans('reuniones.editar')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="trans('reuniones.editar')"
            :descripcion="props.reunion.titulo"
        >
            <template #acciones>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="mostrarReunion(props.reunion.id)">{{
                        trans('comun.volver')
                    }}</Link>
                </Button>
            </template>
        </CabeceraDePagina>

        <FormularioReunion
            :proyecto="props.proyecto"
            :opciones="props.opciones"
            :reunion="props.reunion"
            :accion-url="update(props.reunion.id).url"
        />
    </div>
</template>
