<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import { TablaDatos } from '@/components/TablaDatos';
import type { ColumnaDeTabla, OrdenDeColumna } from '@/components/TablaDatos';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatearFecha } from '@/lib/formato';
import { trans } from '@/lib/i18n';
import { create, index, show } from '@/routes/proyectos';
import { index as tareasIndex } from '@/routes/tareas';
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
        // Titular de la tarjeta en móvil.
        principal: true,
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
        prioritaria: true,
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
        prioritaria: true,
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
        prioritaria: true,
        anchoMinimo: '8rem',
    },
    {
        id: 'creado',
        etiqueta: trans('proyectos.fecha_creacion'),
        valor: (proyecto) =>
            proyecto.creado ? formatearFecha(proyecto.creado) : '—',
        ordenPor: 'created_at',
        // Nace oculta: solo interesa al ordenar por antigüedad.
        ocultaPorDefecto: true,
        anchoMinimo: '8rem',
    },
]);

const abrir = (proyecto: Proyecto): void => {
    router.visit(show(proyecto.id).url);
};

/*
 * Enlace al listado global ya filtrado por este proyecto y por los estados
 * abiertos, que es lo que cuenta la columna. El filtro viaja en la URL porque
 * ahí es donde vive el estado de un listado: así el enlace se puede compartir.
 */
const ESTADOS_ABIERTOS = ['pendiente', 'en_proceso', 'en_espera'];

const tareasAbiertasDe = (id: number): string =>
    `${tareasIndex().url}?filter[proyecto_id]=${id}&filter[estado]=${ESTADOS_ABIERTOS.join(',')}`;
</script>

<template>
    <Head :title="$t('proyectos.titulo')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="$t('proyectos.titulo')"
            :descripcion="$t('proyectos.descripcion_listado')"
        >
            <template v-if="props.puede.crear" #acciones>
                <Button as-child size="sm">
                    <Link :href="create()">{{ $t('proyectos.crear') }}</Link>
                </Button>
            </template>
        </CabeceraDePagina>

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

            <!-- El conteo es la pregunta «¿qué queda por hacer aquí?»; que
                 sea un enlace la responde en un clic en vez de en cuatro. -->
            <template #celda-tareas_abiertas="{ fila }">
                <!-- En color de marca, que es el color de enlace del tema: un
                     enlace que se ve igual que el texto de al lado no lo
                     encuentra nadie, y el sentido de esta columna es ser la
                     entrada al listado. -->
                <Link
                    v-if="fila.tareas_abiertas_count > 0"
                    :href="tareasAbiertasDe(fila.id)"
                    class="text-marca underline-offset-4 hover:underline"
                >
                    {{ fila.tareas_abiertas_count }}
                </Link>
                <span v-else class="text-texto-tenue">0</span>
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
