<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { TablaDatos } from '@/components/TablaDatos';
import type { ColumnaDeTabla, OrdenDeColumna } from '@/components/TablaDatos';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, index, show } from '@/routes/proyectos';
import type { RespuestaPaginada } from '@/types/paginacion';

type Proyecto = {
    id: number;
    nombre: string;
    prefijo_tarea: string;
    estado: string;
    cliente: { id: number; nombre: string } | null;
    tareas_abiertas_count: number;
    creado: string | null;
};

const props = defineProps<{
    proyectos: RespuestaPaginada<Proyecto>;
    filtros: Record<string, string>;
    orden: OrdenDeColumna[];
    columnasVisibles: string[] | null;
    estados: Array<{ valor: string; etiqueta: string }>;
    clientes: Array<{ id: number; nombre: string }>;
    puede: { crear: boolean };
}>();

const columnas = computed((): ColumnaDeTabla<Proyecto>[] => [
    {
        id: 'nombre',
        etiqueta: trans('comun.nombre'),
        valor: (proyecto) => proyecto.nombre,
        ordenPor: 'nombre',
        fija: true,
        anclada: true,
        filtro: {
            tipo: 'texto',
            clave: 'nombre',
            marcador: trans('comun.buscar'),
        },
        anchoMinimo: '16rem',
    },
    {
        id: 'cliente',
        etiqueta: trans('proyectos.cliente'),
        valor: (proyecto) => proyecto.cliente?.nombre ?? null,
        filtro: {
            tipo: 'opciones',
            clave: 'cliente_id',
            opciones: props.clientes.map((cliente) => ({
                valor: String(cliente.id),
                etiqueta: cliente.nombre,
            })),
        },
        anchoMinimo: '12rem',
    },
    {
        id: 'prefijo',
        etiqueta: trans('proyectos.prefijo'),
        valor: (proyecto) => proyecto.prefijo_tarea,
        mono: true,
        anchoMinimo: '6rem',
    },
    {
        id: 'tareas_abiertas',
        etiqueta: trans('proyectos.tareas_abiertas'),
        valor: (proyecto) => proyecto.tareas_abiertas_count,
        alineacion: 'derecha',
        anchoMinimo: '8rem',
    },
    {
        id: 'estado',
        etiqueta: trans('comun.estado'),
        valor: (proyecto) => proyecto.estado,
        filtro: {
            tipo: 'opciones',
            clave: 'estado',
            opciones: props.estados.map((opcion) => ({
                valor: opcion.valor,
                etiqueta: trans(opcion.etiqueta),
            })),
        },
        anchoMinimo: '8rem',
    },
    {
        id: 'creado',
        etiqueta: trans('proyectos.fecha_creacion'),
        valor: (proyecto) => proyecto.creado,
        ordenPor: 'created_at',
        mono: true,
        // Nace oculta: solo interesa al ordenar por antigüedad.
        ocultaPorDefecto: true,
        anchoMinimo: '8rem',
    },
]);

const abrir = (proyecto: Proyecto): void => {
    router.visit(show(proyecto.id).url);
};
</script>

<template>
    <Head :title="$t('proyectos.titulo')" />

    <div class="flex flex-col gap-seccion">
        <div class="flex items-start justify-between gap-bloque">
            <Heading variant="small" :title="$t('proyectos.titulo')" />
            <Button v-if="props.puede.crear" as-child size="sm">
                <Link :href="create()">{{ $t('proyectos.crear') }}</Link>
            </Button>
        </div>

        <TablaDatos
            :url="index().url"
            :filas="props.proyectos.data"
            :columnas="columnas"
            :clave="(proyecto) => proyecto.id"
            :orden="props.orden"
            :filtros="props.filtros"
            :columnas-visibles="props.columnasVisibles"
            :paginacion="props.proyectos"
            :etiqueta="$t('proyectos.titulo')"
            :vacio="{
                titulo: $t('proyectos.sin_proyectos'),
                cuerpo: $t('proyectos.sin_proyectos_ayuda'),
            }"
            @abrir="abrir"
        >
            <template #vacio-acciones>
                <Button v-if="props.puede.crear" as-child size="sm">
                    <Link :href="create()">{{ $t('proyectos.crear') }}</Link>
                </Button>
            </template>

            <template #celda-nombre="{ fila }">
                <Link
                    :href="show(fila.id)"
                    class="underline-offset-4 hover:underline"
                >
                    {{ fila.nombre }}
                </Link>
            </template>

            <template #celda-cliente="{ fila }">
                <span class="text-texto-suave">{{
                    fila.cliente?.nombre ?? '—'
                }}</span>
            </template>

            <template #celda-prefijo="{ fila }">
                <Badge variant="outline">{{ fila.prefijo_tarea }}</Badge>
            </template>

            <template #celda-estado="{ fila }">
                <span class="text-texto-suave">
                    {{ $t(`enums.estado_proyecto.${fila.estado}`) }}
                </span>
            </template>
        </TablaDatos>
    </div>
</template>
