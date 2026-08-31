<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';
import EstadoClienteController from '@/actions/App/Http/Controllers/EstadoClienteController';
import Heading from '@/components/Heading.vue';
import { TablaDatos } from '@/components/TablaDatos';
import type { ColumnaDeTabla, OrdenDeColumna } from '@/components/TablaDatos';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, index, show } from '@/routes/clientes';
import type { RespuestaPaginada } from '@/types/paginacion';

type Cliente = {
    id: number;
    nombre: string;
    contacto_nombre: string | null;
    contacto_email: string | null;
    activo: boolean;
    proyectos_count: number;
    proyectos_activos_count: number;
};

const props = defineProps<{
    clientes: RespuestaPaginada<Cliente>;
    filtros: Record<string, string>;
    orden: OrdenDeColumna[];
    columnasVisibles: string[] | null;
    puede: { crear: boolean };
}>();

const columnas = computed((): ColumnaDeTabla<Cliente>[] => [
    {
        id: 'nombre',
        etiqueta: trans('comun.nombre'),
        valor: (cliente) => cliente.nombre,
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
        id: 'contacto',
        etiqueta: trans('clientes.contacto_nombre'),
        valor: (cliente) => cliente.contacto_nombre,
        anchoMinimo: '12rem',
    },
    {
        id: 'correo',
        etiqueta: trans('comun.correo'),
        valor: (cliente) => cliente.contacto_email,
        // Nace oculta: ocupa mucho y casi nunca se lee desde el listado.
        ocultaPorDefecto: true,
        anchoMinimo: '14rem',
    },
    {
        id: 'proyectos',
        etiqueta: trans('clientes.proyectos'),
        valor: (cliente) =>
            `${cliente.proyectos_activos_count} / ${cliente.proyectos_count}`,
        alineacion: 'derecha',
        anchoMinimo: '7rem',
    },
    {
        id: 'estado',
        etiqueta: trans('comun.estado'),
        valor: (cliente) => (cliente.activo ? '1' : '0'),
        filtro: {
            tipo: 'opciones',
            clave: 'activo',
            opciones: [
                { valor: '1', etiqueta: trans('clientes.activo') },
                { valor: '0', etiqueta: trans('clientes.inactivo') },
            ],
        },
        anchoMinimo: '7rem',
    },
    {
        id: 'acciones',
        etiqueta: trans('comun.acciones'),
        alineacion: 'derecha',
        anchoMinimo: '8rem',
    },
]);

const abrir = (cliente: Cliente): void => {
    router.visit(show(cliente.id).url);
};
</script>

<template>
    <Head :title="$t('clientes.titulo')" />

    <div class="flex flex-col gap-seccion">
        <div class="flex items-start justify-between gap-bloque">
            <Heading
                variant="small"
                :title="$t('clientes.titulo')"
                :description="$t('clientes.nota_desactivar')"
            />
            <Button v-if="props.puede.crear" as-child size="sm">
                <Link :href="create()">{{ $t('clientes.crear') }}</Link>
            </Button>
        </div>

        <TablaDatos
            :url="index().url"
            :filas="props.clientes.data"
            :columnas="columnas"
            :clave="(cliente) => cliente.id"
            :orden="props.orden"
            :filtros="props.filtros"
            :columnas-visibles="props.columnasVisibles"
            :paginacion="props.clientes"
            :etiqueta="$t('clientes.titulo')"
            :vacio="{
                titulo: $t('clientes.sin_clientes'),
                cuerpo: $t('clientes.sin_clientes_ayuda'),
            }"
            @abrir="abrir"
        >
            <template #vacio-acciones>
                <Button v-if="props.puede.crear" as-child size="sm">
                    <Link :href="create()">{{ $t('clientes.crear') }}</Link>
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

            <template #celda-contacto="{ fila }">
                <span class="text-texto-suave">{{
                    fila.contacto_nombre ?? '—'
                }}</span>
            </template>

            <template #celda-correo="{ fila }">
                <span class="text-texto-suave">{{
                    fila.contacto_email ?? '—'
                }}</span>
            </template>

            <template #celda-estado="{ fila }">
                <Badge :variant="fila.activo ? 'secondary' : 'outline'">
                    {{
                        fila.activo
                            ? $t('clientes.activo')
                            : $t('clientes.inactivo')
                    }}
                </Badge>
            </template>

            <template #celda-acciones="{ fila }">
                <!-- Un cliente nunca se borra, se desactiva: tiene proyectos con
                     historial de pagos colgando y la clave foránea es `restrict`. -->
                <Form
                    v-if="props.puede.crear"
                    v-bind="EstadoClienteController.update.form(fila.id)"
                    v-slot="{ processing }"
                >
                    <Button
                        variant="ghost"
                        size="sm"
                        type="submit"
                        :disabled="processing"
                    >
                        {{
                            fila.activo
                                ? $t('clientes.desactivar')
                                : $t('clientes.activar')
                        }}
                    </Button>
                </Form>
            </template>
        </TablaDatos>
    </div>
</template>
