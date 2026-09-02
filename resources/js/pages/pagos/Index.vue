<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import EstadoVacio from '@/components/EstadoVacio.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { show as mostrarPago } from '@/routes/pagos';
import { show as mostrarProyecto } from '@/routes/proyectos';
import { create } from '@/routes/proyectos/pagos';

const props = defineProps<{
    proyecto: { id: number; nombre: string; slug: string; prefijo_tarea: string };
    pagos: Array<{
        id: number;
        periodo_consecutivo: number;
        fecha_pago: string;
        monto_usd: string;
        monto_cop: string;
        origen_tasa: string;
    }>;
    puede: { registrar: boolean };
}>();
</script>

<template>
    <Head :title="$t('recursos.pagos')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="$t('recursos.pagos')"
            :descripcion="props.proyecto.nombre"
        >
            <template #acciones>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="mostrarProyecto(props.proyecto.id)">{{
                        $t('comun.volver')
                    }}</Link>
                </Button>
                <Button v-if="props.puede.registrar" as-child size="sm">
                    <Link :href="create(props.proyecto.id)">{{
                        $t('pagos.registrar')
                    }}</Link>
                </Button>
            </template>
        </CabeceraDePagina>

        <EstadoVacio
            v-if="props.pagos.length === 0"
            :titulo="$t('pagos.sin_pagos')"
            :cuerpo="$t('pagos.sin_pagos_ayuda')"
        />

        <div
            v-else
            class="overflow-x-auto rounded-md border border-borde-fuerte bg-panel"
        >
            <table class="w-full min-w-2xl text-left text-cuerpo">
                <thead class="bg-panel-cabecera text-texto-suave">
                    <tr>
                        <th class="px-celda py-corto font-medium">
                            {{ $t('recursos.columnas.consecutivo') }}
                        </th>
                        <th class="px-celda py-corto font-medium">
                            {{ $t('recursos.columnas.fecha_pago') }}
                        </th>
                        <th class="px-celda py-corto font-medium">
                            {{ $t('recursos.columnas.monto_usd') }}
                        </th>
                        <th class="px-celda py-corto font-medium">
                            {{ $t('recursos.columnas.monto_cop') }}
                        </th>
                        <th class="px-celda py-corto font-medium">
                            {{ $t('pagos.origen_tasa') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="pago in props.pagos"
                        :key="pago.id"
                        class="border-t border-borde hover:bg-fila-hover"
                    >
                        <td class="px-celda py-corto">
                            <Link
                                :href="mostrarPago(pago.id)"
                                class="font-mono text-texto underline-offset-4 hover:underline"
                            >
                                {{
                                    $t('tareas.periodo_numero', {
                                        consecutivo: String(
                                            pago.periodo_consecutivo,
                                        ),
                                    })
                                }}
                            </Link>
                        </td>
                        <td class="px-celda py-corto font-mono text-texto-suave">
                            {{ pago.fecha_pago }}
                        </td>
                        <td class="px-celda py-corto text-texto">
                            {{ $t('pagos.usd') }} {{ pago.monto_usd }}
                        </td>
                        <td class="px-celda py-corto text-texto">
                            {{ $t('pagos.cop') }} {{ pago.monto_cop }}
                        </td>
                        <td class="px-celda py-corto">
                            <Badge variant="outline">
                                {{
                                    $t(
                                        `enums.origen_tasa_cambio.${pago.origen_tasa}`,
                                    )
                                }}
                            </Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
