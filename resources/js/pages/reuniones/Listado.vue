<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Calendar, NotebookPen } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import EncabezadoDeSeccion from '@/components/EncabezadoDeSeccion.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatearFechaHora } from '@/lib/formato';
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
    proyecto: {
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
    };
    reuniones: FilaReunion[];
    filtros: Record<string, string>;
    opciones: { tipos: Opcion[] };
    puede: { crear: boolean };
}>();

const tipo = ref(props.filtros.tipo ?? '');
const desde = ref(props.filtros.desde ?? '');
const hasta = ref(props.filtros.hasta ?? '');

/*
 * `SelectItem` no admite el valor vacío (reka-ui lo reserva para «sin
 * selección»): «Todos» usa este centinela y el filtro sigue siendo `''`.
 */
const TODOS = '__todos__';

const tipoElegido = computed({
    get: (): string => tipo.value || TODOS,
    set: (valor: string) => {
        tipo.value = valor === TODOS ? '' : valor;
    },
});

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
        <EncabezadoDeSeccion
            :icono="NotebookPen"
            :titulo="trans('reuniones.bitacora')"
        >
            <template #acciones>
                <Button v-if="props.puede.crear" as-child size="sm">
                    <Link :href="create(props.proyecto.id)">{{
                        trans('reuniones.crear')
                    }}</Link>
                </Button>
            </template>
        </EncabezadoDeSeccion>

        <div class="flex flex-wrap items-end gap-corto">
            <div class="grid gap-1">
                <Label for="filtro-tipo" class="text-fino text-texto-suave">{{
                    trans('reuniones.filtros.tipo')
                }}</Label>
                <Select v-model="tipoElegido">
                    <SelectTrigger id="filtro-tipo">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="TODOS">
                            {{ trans('comun.tabla.todos') }}
                        </SelectItem>
                        <SelectItem
                            v-for="opcion in props.opciones.tipos"
                            :key="opcion.valor"
                            :value="opcion.valor"
                        >
                            {{ trans(opcion.etiqueta) }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="grid gap-1">
                <Label for="filtro-desde" class="text-fino text-texto-suave">{{
                    trans('reuniones.filtros.desde')
                }}</Label>
                <InputGroup>
                    <InputGroupInput
                        id="filtro-desde"
                        v-model="desde"
                        type="date"
                    />
                    <InputGroupAddon>
                        <Calendar aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
            </div>

            <div class="grid gap-1">
                <Label for="filtro-hasta" class="text-fino text-texto-suave">{{
                    trans('reuniones.filtros.hasta')
                }}</Label>
                <InputGroup>
                    <InputGroupInput
                        id="filtro-hasta"
                        v-model="hasta"
                        type="date"
                    />
                    <InputGroupAddon>
                        <Calendar aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
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
                    <span class="w-40 shrink-0 text-fino text-texto-suave">
                        {{ formatearFechaHora(reunion.fecha) }}
                    </span>
                    <Badge variant="outline">
                        {{ trans(`enums.tipo_reunion.${reunion.tipo}`) }}
                    </Badge>
                    <span class="min-w-0 flex-1 truncate text-texto">
                        {{ reunion.titulo }}
                    </span>
                    <Badge
                        v-if="reunion.visibilidad === 'cliente'"
                        variant="outline"
                    >
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
