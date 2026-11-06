<script setup lang="ts">
/**
 * Resumen del proyecto: la primera pestaña.
 *
 * La cabecera y las pestañas no viven aquí sino en `layouts/proyecto/Layout.vue`,
 * que envuelve también a cada sección (módulos, tareas, periodos…). Antes cada
 * pestaña de esta página solo enseñaba un contador y un botón «Ver» que sacaba
 * del proyecto; ahora cada pestaña es la pantalla real de su sección.
 */
import { Deferred, Head } from '@inertiajs/vue3';
import { DatabaseBackup, LayoutDashboard } from '@lucide/vue';
import { computed } from 'vue';
import PanelDeAdjuntos from '@/components/archivos/PanelDeAdjuntos.vue';
import EncabezadoDeSeccion from '@/components/EncabezadoDeSeccion.vue';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { formatearFecha, formatearMonto } from '@/lib/formato';
import { trans } from '@/lib/i18n';
import {
    destroy as borrarRespaldo,
    store as subirRespaldo,
} from '@/routes/proyectos/respaldos';
import type { Adjunto } from '@/types/archivos';

const props = defineProps<{
    proyecto: { id: number; nombre: string };
    /**
     * Diferido desde la fase 09 (`Inertia::defer()`): no llega en la
     * respuesta inicial, así que puede ser `undefined` mientras la pantalla
     * ya está pintada. Cada uso va dentro de `<Deferred data="resumen">`.
     */
    resumen?: {
        tareas_por_estado: Record<string, number>;
        periodo_abierto: {
            id: number;
            consecutivo: number;
            fecha_inicio: string;
            fecha_fin: string | null;
            dias_transcurridos: number;
            vencido: boolean;
        } | null;
        ultima_reunion: { id: number; titulo: string; fecha: string } | null;
        periodos_cerrados_count: number;
        total_pagado_usd: string | null;
    };
    puede: { gestionarRespaldos: boolean };
    /**
     * Ausente del todo para quien no gestiona el proyecto: la clave ni
     * siquiera llega vacía. Ocultarla en la interfaz no basta —el JSON de
     * Inertia viaja completo y se lee desde el navegador—, así que quien no
     * puede verla no la recibe.
     */
    respaldos?: Adjunto[];
}>();

const estados = computed(() =>
    Object.entries(props.resumen?.tareas_por_estado ?? {}),
);
</script>

<template>
    <Head :title="props.proyecto.nombre" />

    <div class="flex flex-col gap-seccion">
        <!--
            Resumen. Ya no son tres tarjetas idénticas con borde: son tres
            hechos sobre un mismo proyecto, y de corrido con divisores se leen
            mejor que troceados en cajas. Ver docs/05-identidad-visual.md §7.1.
        -->
        <section class="flex flex-col gap-bloque">
            <EncabezadoDeSeccion
                :icono="LayoutDashboard"
                :titulo="trans('proyectos.resumen.titulo')"
            />

            <!--
                `max-w-4xl`: tres hechos sobre el mismo proyecto se leen como un
                grupo, y repartidos entre 1140px de ancho quedan uno en cada
                esquina de la pantalla. La tabla sí ocupa todo el ancho —alinear
                columnas es su trabajo—; un resumen de tres datos, no.

                Cada celda es una pila vertical: el periodo abierto lleva
                fechas y, desde la fase 09, también periodos cerrados y lo
                pagado, y el bloque crece hacia abajo sin rehacerse.
            -->
            <Deferred data="resumen">
                <template #fallback>
                    <!--
                        Misma rejilla, mismo alto de bloque: sin esto el hueco
                        de carga salta cuando los datos llegan y el resto de
                        la pantalla se mueve debajo del usuario.
                    -->
                    <div
                        class="grid max-w-4xl gap-bloque sm:grid-cols-3 sm:divide-x sm:divide-borde"
                    >
                        <div
                            v-for="indice in 3"
                            :key="indice"
                            class="flex flex-col gap-corto sm:px-bloque sm:first:pr-bloque sm:first:pl-0 sm:last:pr-0 sm:last:pl-bloque"
                        >
                            <Skeleton class="h-4 w-32" />
                            <Skeleton class="h-5 w-24" />
                            <Skeleton class="h-4 w-40" />
                        </div>
                    </div>
                </template>

                <div
                    v-if="props.resumen"
                    class="grid max-w-4xl gap-bloque sm:grid-cols-3 sm:divide-x sm:divide-borde"
                >
                    <div class="flex flex-col gap-corto sm:pr-bloque">
                        <p class="text-fino font-medium text-texto-suave">
                            {{ $t('proyectos.resumen.tareas_por_estado') }}
                        </p>
                        <ul
                            v-if="estados.length"
                            class="flex flex-col gap-fino text-cuerpo"
                        >
                            <li
                                v-for="[estado, total] in estados"
                                :key="estado"
                                class="flex justify-between gap-bloque"
                            >
                                <span>{{
                                    $t(`enums.estado_tarea.${estado}`)
                                }}</span>
                                <span class="font-medium">{{ total }}</span>
                            </li>
                        </ul>
                        <p v-else class="text-cuerpo text-texto-suave">
                            {{ $t('proyectos.resumen.sin_tareas') }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-corto sm:px-bloque">
                        <p class="text-fino font-medium text-texto-suave">
                            {{ $t('proyectos.resumen.periodo_abierto') }}
                        </p>
                        <template v-if="props.resumen.periodo_abierto">
                            <p class="text-cuerpo font-medium">
                                {{
                                    $t('proyectos.resumen.periodo_numero', {
                                        consecutivo: String(
                                            props.resumen.periodo_abierto
                                                .consecutivo,
                                        ),
                                    })
                                }}
                            </p>
                            <p class="text-cuerpo text-texto-suave">
                                {{
                                    $t('proyectos.resumen.dias_transcurridos', {
                                        dias: String(
                                            props.resumen.periodo_abierto
                                                .dias_transcurridos,
                                        ),
                                    })
                                }}
                            </p>
                            <!-- Pasarse de la fecha de fin es válido: el cierre es manual. -->
                            <div
                                v-if="props.resumen.periodo_abierto.vencido"
                                class="flex flex-col gap-fino"
                            >
                                <Badge variant="outline" class="self-start">
                                    {{ $t('proyectos.resumen.vencido') }}
                                </Badge>
                                <span class="text-fino text-texto-suave">
                                    {{ $t('proyectos.resumen.vencido_ayuda') }}
                                </span>
                            </div>
                        </template>
                        <p v-else class="text-cuerpo text-texto-suave">
                            {{ $t('proyectos.resumen.sin_periodo_abierto') }}
                        </p>
                        <p class="text-fino text-texto-suave">
                            {{
                                $t('proyectos.resumen.periodos_cerrados', {
                                    total: String(
                                        props.resumen.periodos_cerrados_count,
                                    ),
                                })
                            }}
                        </p>
                        <p
                            v-if="props.resumen.total_pagado_usd !== null"
                            class="text-fino text-texto-suave"
                        >
                            {{
                                $t('proyectos.resumen.total_pagado', {
                                    monto: formatearMonto(
                                        props.resumen.total_pagado_usd,
                                        'USD',
                                    ),
                                })
                            }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-corto sm:pl-bloque">
                        <p class="text-fino font-medium text-texto-suave">
                            {{ $t('proyectos.resumen.ultima_reunion') }}
                        </p>
                        <template v-if="props.resumen.ultima_reunion">
                            <p class="text-cuerpo font-medium">
                                {{ props.resumen.ultima_reunion.titulo }}
                            </p>
                            <p class="text-fino text-texto-suave">
                                {{
                                    formatearFecha(
                                        props.resumen.ultima_reunion.fecha,
                                    )
                                }}
                            </p>
                        </template>
                        <p v-else class="text-cuerpo text-texto-suave">
                            {{ $t('proyectos.resumen.sin_reuniones') }}
                        </p>
                    </div>
                </div>
            </Deferred>
        </section>

        <section
            v-if="props.puede.gestionarRespaldos"
            class="flex flex-col gap-corto"
        >
            <EncabezadoDeSeccion
                :icono="DatabaseBackup"
                :titulo="trans('archivos.coleccion.respaldos_bd')"
            />
            <PanelDeAdjuntos
                coleccion="respaldos_bd"
                :adjuntos="props.respaldos ?? []"
                :subir-url="subirRespaldo(props.proyecto.id).url"
                :borrar-url="
                    (adjunto) =>
                        borrarRespaldo([props.proyecto.id, adjunto.id]).url
                "
                :puede-subir="true"
                :puede-borrar="true"
            />
        </section>
    </div>
</template>
