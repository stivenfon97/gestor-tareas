<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import EstadoClienteController from '@/actions/App/Http/Controllers/EstadoClienteController';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import { TablaDatos } from '@/components/TablaDatos';
import type { ColumnaDeTabla, OrdenDeColumna } from '@/components/TablaDatos';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { trans } from '@/lib/i18n';
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
        principal: true,
        anchoMinimo: '16rem',
    },
    {
        id: 'contacto',
        etiqueta: trans('clientes.contacto_nombre'),
        valor: (cliente) => cliente.contacto_nombre,
        prioritaria: true,
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
        /*
         * Un solo número, y la columna dice cuál es.
         *
         * Antes decía «1 / 1» bajo el rótulo «Proyectos», y nada en la pantalla
         * explicaba qué era cada mitad: se leía igual como «uno de uno» que
         * como una fracción o una fecha abreviada. El total sigue estando a un
         * palmo, en el globo de ayuda de la celda.
         */
        id: 'proyectos',
        etiqueta: trans('clientes.proyectos_activos'),
        valor: (cliente) => cliente.proyectos_activos_count,
        alineacion: 'derecha',
        prioritaria: true,
        anchoMinimo: '9rem',
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
        prioritaria: true,
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
        <CabeceraDePagina
            :titulo="$t('clientes.titulo')"
            :descripcion="$t('clientes.nota_desactivar')"
        >
            <template v-if="props.puede.crear" #acciones>
                <Button as-child size="sm">
                    <Link :href="create()">{{ $t('clientes.crear') }}</Link>
                </Button>
            </template>
        </CabeceraDePagina>

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

            <template #celda-proyectos="{ fila }">
                <span
                    :title="
                        $t('clientes.proyectos_de_total', {
                            activos: String(fila.proyectos_activos_count),
                            total: String(fila.proyectos_count),
                        })
                    "
                >
                    {{ fila.proyectos_activos_count }}
                </span>
            </template>

            <template #celda-acciones="{ fila }">
                <!-- Un cliente nunca se borra, se desactiva: tiene proyectos con
                     historial de pagos colgando y la clave foránea es `restrict`.

                     `outline`, no `ghost`: cambiar el estado de un cliente es
                     una acción, y un texto suelto alineado a la derecha no se
                     parece a nada en lo que se pueda pulsar. La etiqueta dice
                     **qué va a pasar**, no en qué estado está: en un cliente
                     inactivo pone «Activar». -->
                <Form
                    v-if="props.puede.crear"
                    v-bind="EstadoClienteController.update.form(fila.id)"
                    v-slot="{ processing }"
                >
                    <Button
                        variant="outline"
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
