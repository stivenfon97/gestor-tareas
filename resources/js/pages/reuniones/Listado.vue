<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { trans } from '@/lib/i18n';
import { create, index } from '@/routes/proyectos/reuniones';
import { show as mostrarReunion } from '@/routes/reuniones';
import type { Opcion } from '@/types/tareas';

type FilaReunion = {
    id: number;
    fecha: string;
    titulo: string;
    tipo: string;
    visibilidad: string;
};

const props = defineProps<{
    proyecto: { id: number; nombre: string; slug: string; prefijo_tarea: string };
    reuniones: FilaReunion[];
    filtros: Record<string, string>;
    opciones: { tipos: Opcion[] };
    puede: { crear: boolean };
}>();

const tipo = ref(props.filtros.tipo ?? '');
const desde = ref(props.filtros.desde ?? '');
const hasta = ref(props.filtros.hasta ?? '');

/*
 * El estado del listado va en la URL, no en memoria: así "las reuniones de
 * tipo llamada de julio" es un enlace, no una instrucción de tres pasos.
 */
const aplicarFiltros = (): void => {
    router.get(
        index(props.proyecto.id).url,
        {
            ...(tipo.value ? { 'filter[tipo]': tipo.value } : {}),
            ...(desde.value ? { 'filter[desde]': desde.value } : {}),
            ...(hasta.value ? { 'filter[hasta]': hasta.value } : {}),
        },
        { preserveState: true, replace: true },
    );
};

watch([tipo, desde, hasta], aplicarFiltros);

const limpiarFiltros = (): void => {
    tipo.value = '';
    desde.value = '';
    hasta.value = '';
};
</script>

<template>
    <Head :title="trans('reuniones.bitacora')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="trans('reuniones.bitacora')"
            :descripcion="props.proyecto.nombre"
        >
            <template #acciones>
                <Button v-if="props.puede.crear" as-child size="sm">
                    <Link :href="create(props.proyecto.id)">{{
                        trans('reuniones.crear')
                    }}</Link>
                </Button>
            </template>
        </CabeceraDePagina>

        <div class="flex flex-wrap items-end gap-corto">
            <div class="grid gap-1">
                <label
                    for="filtro-tipo"
                    class="text-fino text-texto-suave"
                    >{{ trans('reuniones.filtros.tipo') }}</label
                >
                <select
                    id="filtro-tipo"
                    v-model="tipo"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                >
                    <option value="">{{ trans('comun.tabla.todos') }}</option>
                    <option
                        v-for="opcion in props.opciones.tipos"
                        :key="opcion.valor"
                        :value="opcion.valor"
                    >
                        {{ trans(opcion.etiqueta) }}
                    </option>
                </select>
            </div>

            <div class="grid gap-1">
                <label for="filtro-desde" class="text-fino text-texto-suave">{{
                    trans('reuniones.filtros.desde')
                }}</label>
                <Input id="filtro-desde" v-model="desde" type="date" />
            </div>

            <div class="grid gap-1">
                <label for="filtro-hasta" class="text-fino text-texto-suave">{{
                    trans('reuniones.filtros.hasta')
                }}</label>
                <Input id="filtro-hasta" v-model="hasta" type="date" />
            </div>

            <Button variant="ghost" size="sm" @click="limpiarFiltros">
                {{ trans('reuniones.filtros.limpiar') }}
            </Button>
        </div>

        <ul
            v-if="props.reuniones.length"
            class="flex flex-col overflow-hidden rounded-md border border-borde-fuerte bg-panel"
        >
            <li
                v-for="reunion in props.reuniones"
                :key="reunion.id"
                class="border-b border-borde last:border-b-0"
            >
                <Link
                    :href="mostrarReunion(reunion.id)"
                    class="flex flex-wrap items-center gap-corto px-celda py-fino hover:bg-fila-hover"
                >
                    <span class="w-40 shrink-0 font-mono text-fino text-texto-suave">
                        {{ reunion.fecha }}
                    </span>
                    <Badge variant="outline">
                        {{ trans(`enums.tipo_reunion.${reunion.tipo}`) }}
                    </Badge>
                    <span class="min-w-0 flex-1 truncate text-texto">
                        {{ reunion.titulo }}
                    </span>
                    <Badge v-if="reunion.visibilidad === 'cliente'" variant="outline">
                        {{ trans('enums.visibilidad.cliente') }}
                    </Badge>
                </Link>
            </li>
        </ul>
        <p v-else class="text-fino text-texto-tenue">
            {{ trans('reuniones.vacio') }}
        </p>
    </div>
</template>
