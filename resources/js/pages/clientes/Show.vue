<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Building2, Eye } from '@lucide/vue';
import { computed } from 'vue';
import EstadoClienteController from '@/actions/App/Http/Controllers/EstadoClienteController';
import BotonDeAccion from '@/components/BotonDeAccion.vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import { TablaLocal } from '@/components/TablaDatos';
import type { ColumnaDeTabla } from '@/components/TablaDatos';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatearFecha } from '@/lib/formato';
import { trans } from '@/lib/i18n';
import { tonoDeActivo, tonoDeEstadoProyecto } from '@/lib/tonos';
import { edit, index } from '@/routes/clientes';
import { show as mostrarProyecto } from '@/routes/proyectos';

const props = defineProps<{
    cliente: {
        id: number;
        nombre: string;
        contacto_nombre: string | null;
        contacto_email: string | null;
        contacto_telefono: string | null;
        notas: string | null;
        activo: boolean;
    };
    proyectos: Array<{
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
        estado: string;
    }>;
    resumen: {
        proyectos_activos: number;
        tareas_abiertas: number;
        ultimo_periodo_cerrado: {
            proyecto: string;
            consecutivo: number;
            fecha_fin: string | null;
        } | null;
    };
    puede: { editar: boolean };
}>();

type FilaProyecto = (typeof props.proyectos)[number];

const columnasDeProyectos = computed((): ColumnaDeTabla<FilaProyecto>[] => [
    {
        id: 'nombre',
        etiqueta: trans('comun.nombre'),
        valor: (fila) => fila.nombre,
        ordenPor: 'nombre',
        fija: true,
        principal: true,
    },
    {
        id: 'prefijo',
        etiqueta: trans('proyectos.prefijo'),
        valor: (fila) => fila.prefijo_tarea,
        ordenPor: 'prefijo',
        ajustada: true,
        prioritaria: true,
    },
    {
        id: 'estado',
        etiqueta: trans('comun.estado'),
        valor: (fila) => fila.estado,
        filtro: {
            tipo: 'opciones',
            clave: 'estado',
            opciones: ['activo', 'pausado', 'finalizado'].map((valor) => ({
                valor,
                etiqueta: trans(`enums.estado_proyecto.${valor}`),
            })),
        },
        ajustada: true,
        prioritaria: true,
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
    <Head :title="props.cliente.nombre" />

    <div class="flex flex-col gap-6">
        <CabeceraDePagina
            :icono="Building2"
            :titulo="props.cliente.nombre"
            :descripcion="props.cliente.contacto_nombre ?? undefined"
        >
            <template #acciones>
                <div class="flex items-center gap-2">
                    <Button as-child variant="ghost" size="sm">
                        <Link :href="index()">{{ $t('comun.volver') }}</Link>
                    </Button>
                    <Button
                        v-if="props.puede.editar"
                        as-child
                        size="sm"
                        variant="outline"
                    >
                        <Link :href="edit(props.cliente.id)">{{
                            $t('comun.editar')
                        }}</Link>
                    </Button>
                    <Form
                        novalidate
                        v-if="props.puede.editar"
                        v-bind="
                            EstadoClienteController.update.form(
                                props.cliente.id,
                            )
                        "
                        v-slot="{ processing }"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            type="submit"
                            :disabled="processing"
                        >
                            {{
                                props.cliente.activo
                                    ? $t('clientes.desactivar')
                                    : $t('clientes.activar')
                            }}
                        </Button>
                    </Form>
                </div>
            </template>
        </CabeceraDePagina>

        <div class="flex flex-wrap items-center gap-3 text-sm">
            <Badge :variant="tonoDeActivo(props.cliente.activo)">
                <span
                    class="size-1.5 rounded-full bg-current"
                    aria-hidden="true"
                />
                {{
                    props.cliente.activo
                        ? $t('clientes.activo')
                        : $t('clientes.inactivo')
                }}
            </Badge>
            <span
                v-if="props.cliente.contacto_email"
                class="text-muted-foreground"
            >
                {{ props.cliente.contacto_email }}
            </span>
            <span
                v-if="props.cliente.contacto_telefono"
                class="text-muted-foreground"
            >
                {{ props.cliente.contacto_telefono }}
            </span>
        </div>

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-border p-4">
                <p class="text-sm text-muted-foreground">
                    {{ $t('clientes.resumen.proyectos_activos') }}
                </p>
                <p class="text-2xl font-semibold">
                    {{ props.resumen.proyectos_activos }}
                </p>
            </div>
            <div class="rounded-lg border border-border p-4">
                <p class="text-sm text-muted-foreground">
                    {{ $t('clientes.resumen.tareas_abiertas') }}
                </p>
                <p class="text-2xl font-semibold">
                    {{ props.resumen.tareas_abiertas }}
                </p>
            </div>
            <div class="rounded-lg border border-border p-4">
                <p class="text-sm text-muted-foreground">
                    {{ $t('clientes.resumen.ultimo_periodo_cerrado') }}
                </p>
                <p
                    v-if="props.resumen.ultimo_periodo_cerrado"
                    class="text-sm font-medium"
                >
                    {{
                        $t('clientes.resumen.periodo', {
                            consecutivo: String(
                                props.resumen.ultimo_periodo_cerrado
                                    .consecutivo,
                            ),
                            proyecto:
                                props.resumen.ultimo_periodo_cerrado.proyecto,
                        })
                    }}
                    <span class="block text-muted-foreground">
                        {{
                            formatearFecha(
                                props.resumen.ultimo_periodo_cerrado.fecha_fin,
                            )
                        }}
                    </span>
                </p>
                <p v-else class="text-sm text-muted-foreground">
                    {{ $t('clientes.resumen.sin_periodos_cerrados') }}
                </p>
            </div>
        </section>

        <section class="flex flex-col gap-3">
            <h3 class="text-base font-medium">
                {{ $t('clientes.proyectos') }}
            </h3>

            <TablaLocal
                :filas="props.proyectos"
                :columnas="columnasDeProyectos"
                :clave="(fila) => fila.id"
                :etiqueta="$t('clientes.proyectos')"
                :busqueda="$t('proyectos.buscar')"
                :vacio="{
                    titulo: $t('clientes.sin_proyectos'),
                    cuerpo: $t('clientes.sin_proyectos_ayuda'),
                }"
            >
                <template #celda-nombre="{ fila }">
                    <Link
                        :href="mostrarProyecto(fila.id)"
                        class="underline-offset-4 hover:underline"
                    >
                        {{ fila.nombre }}
                    </Link>
                </template>
                <template #celda-prefijo="{ fila }">
                    <Badge variant="outline" class="font-mono">{{
                        fila.prefijo_tarea
                    }}</Badge>
                </template>
                <template #celda-estado="{ fila }">
                    <Badge :variant="tonoDeEstadoProyecto(fila.estado)">
                        <span
                            class="size-1.5 rounded-full bg-current"
                            aria-hidden="true"
                        />
                        {{ $t(`enums.estado_proyecto.${fila.estado}`) }}
                    </Badge>
                </template>
                <template #celda-acciones="{ fila }">
                    <div class="flex justify-end">
                        <BotonDeAccion
                            :href="mostrarProyecto(fila.id)"
                            :icono="Eye"
                            :etiqueta="$t('proyectos.ver')"
                        />
                    </div>
                </template>
            </TablaLocal>
        </section>

        <section v-if="props.cliente.notas" class="flex flex-col gap-2">
            <h3 class="text-base font-medium">{{ $t('clientes.notas') }}</h3>
            <p
                class="max-w-3xl text-sm whitespace-pre-line text-muted-foreground"
            >
                {{ props.cliente.notas }}
            </p>
        </section>
    </div>
</template>
