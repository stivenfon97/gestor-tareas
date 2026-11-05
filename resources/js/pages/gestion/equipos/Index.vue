<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import { TablaDatos } from '@/components/TablaDatos';
import type { ColumnaDeTabla, OrdenDeColumna } from '@/components/TablaDatos';
import { Button } from '@/components/ui/button';
import { trans } from '@/lib/i18n';
import { create, edit, index } from '@/routes/equipos';
import type { RespuestaPaginada } from '@/types/paginacion';

type Equipo = {
    id: number;
    nombre: string;
    descripcion: string | null;
    usuarios_count: number;
};

const props = defineProps<{
    equipos: RespuestaPaginada<Equipo>;
    filtros: Record<string, string>;
    orden: OrdenDeColumna[];
    columnasVisibles: string[] | null;
}>();

const columnas = computed((): ColumnaDeTabla<Equipo>[] => [
    {
        id: 'nombre',
        etiqueta: trans('comun.nombre'),
        valor: (equipo) => equipo.nombre,
        ordenPor: 'nombre',
        fija: true,
        anclada: true,
        filtro: {
            tipo: 'texto',
            clave: 'nombre',
            marcador: trans('comun.buscar'),
        },
        principal: true,
        anchoMinimo: '14rem',
    },
    {
        id: 'descripcion',
        etiqueta: trans('comun.descripcion'),
        valor: (equipo) => equipo.descripcion,
        prioritaria: true,
        anchoMinimo: '18rem',
    },
    {
        id: 'miembros',
        etiqueta: trans('gestion.equipos.cantidad_miembros'),
        valor: (equipo) => equipo.usuarios_count,
        alineacion: 'derecha',
        ajustada: true,
        prioritaria: true,
    },
    {
        id: 'acciones',
        etiqueta: trans('comun.acciones'),
        alineacion: 'derecha',
        ajustada: true,
    },
]);

const abrir = (equipo: Equipo): void => {
    router.visit(edit(equipo.id).url);
};
</script>

<template>
    <Head :title="$t('gestion.equipos.titulo')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="$t('gestion.equipos.titulo')"
            :descripcion="$t('gestion.equipos.nota_no_otorga_permisos')"
        >
            <template #acciones>
                <Button as-child size="sm">
                    <Link :href="create()">{{
                        $t('gestion.equipos.crear')
                    }}</Link>
                </Button>
            </template>
        </CabeceraDePagina>

        <TablaDatos
            :url="index().url"
            :filas="props.equipos.data"
            :columnas="columnas"
            :clave="(equipo) => equipo.id"
            :orden="props.orden"
            :filtros="props.filtros"
            :columnas-visibles="props.columnasVisibles"
            :paginacion="props.equipos"
            :etiqueta="$t('gestion.equipos.titulo')"
            :vacio="{
                titulo: $t('gestion.equipos.sin_equipos'),
                cuerpo: $t('gestion.equipos.sin_equipos_ayuda'),
            }"
            @abrir="abrir"
        >
            <template #vacio-acciones>
                <Button as-child size="sm">
                    <Link :href="create()">{{
                        $t('gestion.equipos.crear')
                    }}</Link>
                </Button>
            </template>

            <template #celda-nombre="{ fila }">
                <Link
                    :href="edit(fila.id)"
                    class="underline-offset-4 hover:underline"
                >
                    {{ fila.nombre }}
                </Link>
            </template>

            <template #celda-descripcion="{ fila }">
                <span class="text-texto-suave">{{
                    fila.descripcion ?? '—'
                }}</span>
            </template>

            <template #celda-acciones="{ fila }">
                <Button as-child variant="ghost" size="sm">
                    <Link :href="edit(fila.id)">{{ $t('comun.editar') }}</Link>
                </Button>
            </template>
        </TablaDatos>
    </div>
</template>
