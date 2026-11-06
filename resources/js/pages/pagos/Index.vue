<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Banknote, Eye } from '@lucide/vue';
import { computed } from 'vue';
import BotonDeAccion from '@/components/BotonDeAccion.vue';
import EncabezadoDeSeccion from '@/components/EncabezadoDeSeccion.vue';
import { TablaDatos } from '@/components/TablaDatos';
import type { ColumnaDeTabla, OrdenDeColumna } from '@/components/TablaDatos';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatearFecha, formatearMonto } from '@/lib/formato';
import { trans } from '@/lib/i18n';
import { tonoDeOrigenTasa } from '@/lib/tonos';
import { show as mostrarPago } from '@/routes/pagos';
import { create, index } from '@/routes/proyectos/pagos';
import type { RespuestaPaginada } from '@/types/paginacion';

type FilaPago = {
    id: number;
    periodo_consecutivo: number;
    fecha_pago: string;
    monto_usd: string;
    tasa_cambio: string;
    monto_cop: string;
    origen_tasa: string;
    referencia: string | null;
};

const props = defineProps<{
    proyecto: {
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
    };
    pagos: RespuestaPaginada<FilaPago>;
    filtros: Record<string, string>;
    orden: OrdenDeColumna[];
    columnasVisibles: string[] | null;
    origenes: Array<{ valor: string; etiqueta: string }>;
    puede: { registrar: boolean };
}>();

const columnas = computed((): ColumnaDeTabla<FilaPago>[] => [
    {
        id: 'periodo',
        etiqueta: trans('pagos.periodo'),
        valor: (fila) => fila.periodo_consecutivo,
        fija: true,
        principal: true,
        anchoMinimo: '9rem',
    },
    {
        id: 'fecha_pago',
        etiqueta: trans('pagos.fecha_pago'),
        valor: (fila) => formatearFecha(fila.fecha_pago),
        ordenPor: 'fecha_pago',
        mono: true,
        prioritaria: true,
    },
    {
        id: 'monto_usd',
        etiqueta: trans('pagos.monto_usd'),
        valor: (fila) => formatearMonto(fila.monto_usd, 'USD'),
        ordenPor: 'monto_usd',
        alineacion: 'derecha',
        mono: true,
        prioritaria: true,
    },
    {
        id: 'tasa_cambio',
        etiqueta: trans('pagos.tasa_cambio'),
        valor: (fila) => formatearMonto(fila.tasa_cambio, 'COP'),
        ordenPor: 'tasa_cambio',
        alineacion: 'derecha',
        mono: true,
        ocultaPorDefecto: true,
    },
    {
        id: 'monto_cop',
        etiqueta: trans('pagos.monto_cop'),
        valor: (fila) => formatearMonto(fila.monto_cop, 'COP'),
        ordenPor: 'monto_cop',
        alineacion: 'derecha',
        mono: true,
        prioritaria: true,
    },
    {
        id: 'origen',
        etiqueta: trans('pagos.origen_tasa'),
        valor: (fila) => fila.origen_tasa,
        filtro: {
            tipo: 'opciones',
            clave: 'origen_tasa',
            opciones: props.origenes.map((opcion) => ({
                valor: opcion.valor,
                etiqueta: trans(opcion.etiqueta),
            })),
        },
        ajustada: true,
    },
    {
        id: 'referencia',
        etiqueta: trans('pagos.referencia'),
        valor: (fila) => fila.referencia ?? '—',
        mono: true,
        ocultaPorDefecto: true,
    },
    {
        id: 'acciones',
        etiqueta: trans('comun.acciones'),
        alineacion: 'derecha',
        ajustada: true,
        prioritaria: true,
    },
]);
</script>

<template>
    <Head :title="$t('recursos.pagos')" />

    <div class="flex flex-col gap-seccion">
        <EncabezadoDeSeccion :icono="Banknote" :titulo="$t('recursos.pagos')">
            <template #acciones>
                <Button v-if="props.puede.registrar" as-child size="sm">
                    <Link :href="create(props.proyecto.id)">{{
                        $t('pagos.registrar')
                    }}</Link>
                </Button>
            </template>
        </EncabezadoDeSeccion>

        <TablaDatos
            :url="index(props.proyecto.id).url"
            :filas="props.pagos.data"
            :columnas="columnas"
            :clave="(fila) => fila.id"
            :orden="props.orden"
            :filtros="props.filtros"
            :columnas-visibles="props.columnasVisibles"
            :paginacion="props.pagos"
            :etiqueta="$t('recursos.pagos')"
            :busqueda="{ clave: 'buscar', marcador: $t('pagos.buscar') }"
            :vacio="{
                titulo: $t('pagos.sin_pagos'),
                cuerpo: $t('pagos.sin_pagos_ayuda'),
            }"
            @abrir="(fila) => router.visit(mostrarPago(fila.id).url)"
        >
            <template #vacio-acciones>
                <Button v-if="props.puede.registrar" as-child size="sm">
                    <Link :href="create(props.proyecto.id)">{{
                        $t('pagos.registrar')
                    }}</Link>
                </Button>
            </template>

            <template #celda-periodo="{ fila }">
                <Link
                    :href="mostrarPago(fila.id)"
                    class="font-medium underline-offset-4 hover:underline"
                >
                    {{
                        $t('tareas.periodo_numero', {
                            consecutivo: String(fila.periodo_consecutivo),
                        })
                    }}
                </Link>
            </template>

            <template #celda-origen="{ fila }">
                <Badge :variant="tonoDeOrigenTasa(fila.origen_tasa)">
                    <span
                        class="size-1.5 rounded-full bg-current"
                        aria-hidden="true"
                    />
                    {{ $t(`enums.origen_tasa_cambio.${fila.origen_tasa}`) }}
                </Badge>
            </template>

            <template #celda-acciones="{ fila }">
                <div class="flex items-center justify-end">
                    <BotonDeAccion
                        :href="mostrarPago(fila.id)"
                        :icono="Eye"
                        :etiqueta="$t('pagos.ver')"
                    />
                </div>
            </template>
        </TablaDatos>
    </div>
</template>
