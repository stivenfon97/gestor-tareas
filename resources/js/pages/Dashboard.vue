<script setup lang="ts">
import { Deferred, Head, Link } from '@inertiajs/vue3';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import EstadoVacio from '@/components/EstadoVacio.vue';
import BadgeEstado from '@/components/tareas/BadgeEstado.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { formatearFecha, formatearMonto } from '@/lib/formato';
import { trans } from '@/lib/i18n';
import { show as periodoShow } from '@/routes/periodos';
import { show as reunionShow } from '@/routes/reuniones';
import { index as tareasIndex, show as tareaShow } from '@/routes/tareas';

/**
 * Tablero. Cuatro bloques, cada uno con su propio `<Deferred>` porque en el
 * servidor cada uno viaja en su propio grupo de `Inertia::defer()`: un bloque
 * lento no puede retrasar a los otros tres.
 *
 * Reemplaza a los tres recuadros con rayas diagonales que la fase 07b dejó
 * como promesa ("Pendiente: fase 12"). Nada de cifras de mentira: mientras un
 * bloque carga se ve un esqueleto, nunca un cero ni un dato inventado.
 */

type Proyecto = { id: number; nombre: string };

defineProps<{
    trabajo?: Array<{
        id: number;
        codigo: string;
        nombre: string;
        proyecto: Proyecto;
        estado: 'en_proceso' | 'en_espera';
        motivo_espera: string | null;
        dias_en_estado: number;
    }>;
    periodos?: Array<{
        id: number;
        consecutivo: number;
        proyecto: Proyecto;
        dias_transcurridos: number;
        tareas_sin_terminar: number;
        vencido: boolean;
    }>;
    reuniones?: Array<{
        id: number;
        titulo: string;
        fecha: string;
        proyecto: Proyecto;
    }>;
    /**
     * Siempre presente, a diferencia de `ingresos`: es una bandera barata que
     * le dice a la interfaz si debe pintar la tarjeta en absoluto. Un
     * `cliente` la recibe en `false` y ni siquiera intenta pedir el diferido.
     */
    veIngresos: boolean;
    /**
     * Ausente del todo —no `undefined` por no haber cargado, sino nunca
     * anunciado— para quien no ve pagos en ningún proyecto. El JSON de
     * Inertia viaja completo y se lee desde el navegador: ocultar la tarjeta
     * no basta si la clave sigue ahí.
     */
    ingresos?: Array<{
        cliente: string;
        total_cop: string;
        periodos_count: number;
    }>;
}>();

const skeletonFilas = [1, 2, 3];
</script>

<template>
    <Head :title="$t('nav.dashboard')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="$t('dashboard.titulo')"
            :descripcion="$t('dashboard.descripcion')"
        />

        <div class="grid gap-bloque lg:grid-cols-2">
            <!-- Tu trabajo de hoy -->
            <section
                class="flex flex-col gap-corto rounded-md border border-borde bg-panel px-celda py-bloque"
            >
                <div class="flex flex-col gap-minimo">
                    <h2 class="text-medio font-medium text-texto">
                        {{ $t('dashboard.bloques.trabajo.titulo') }}
                    </h2>
                    <p class="text-fino text-texto-suave">
                        {{ $t('dashboard.bloques.trabajo.descripcion') }}
                    </p>
                </div>

                <Deferred data="trabajo">
                    <template #fallback>
                        <div class="flex flex-col gap-corto">
                            <Skeleton
                                v-for="fila in skeletonFilas"
                                :key="fila"
                                class="h-14 w-full"
                            />
                        </div>
                    </template>

                    <EstadoVacio
                        v-if="trabajo && trabajo.length === 0"
                        :titulo="$t('dashboard.bloques.trabajo.vacio_titulo')"
                        :cuerpo="$t('dashboard.bloques.trabajo.vacio_cuerpo')"
                    />
                    <ul
                        v-else-if="trabajo"
                        class="flex flex-col divide-y divide-borde"
                    >
                        <li
                            v-for="tarea in trabajo"
                            :key="tarea.id"
                            class="flex flex-col gap-minimo py-corto first:pt-0 last:pb-0"
                        >
                            <div
                                class="flex items-start justify-between gap-corto"
                            >
                                <Link
                                    :href="tareaShow(tarea.id)"
                                    class="min-w-0 truncate text-cuerpo font-medium text-texto hover:underline"
                                >
                                    {{ tarea.codigo }} — {{ tarea.nombre }}
                                </Link>
                                <BadgeEstado
                                    :estado="tarea.estado"
                                    tamano="pequeno"
                                />
                            </div>
                            <p class="text-fino text-texto-suave">
                                {{ tarea.proyecto.nombre }} ·
                                {{
                                    trans(
                                        'dashboard.bloques.trabajo.dias_en_estado',
                                        {
                                            dias: String(tarea.dias_en_estado),
                                        },
                                    )
                                }}
                            </p>
                            <p
                                v-if="tarea.motivo_espera"
                                class="text-fino text-texto-suave"
                            >
                                {{
                                    trans('dashboard.bloques.trabajo.motivo', {
                                        motivo: tarea.motivo_espera,
                                    })
                                }}
                            </p>
                        </li>
                    </ul>
                </Deferred>

                <Button as-child variant="outline" size="sm" class="self-start">
                    <Link :href="tareasIndex()">
                        {{ $t('dashboard.bloques.trabajo.accion') }}
                    </Link>
                </Button>
            </section>

            <!-- Periodos abiertos -->
            <section
                class="flex flex-col gap-corto rounded-md border border-borde bg-panel px-celda py-bloque"
            >
                <div class="flex flex-col gap-minimo">
                    <h2 class="text-medio font-medium text-texto">
                        {{ $t('dashboard.bloques.periodos.titulo') }}
                    </h2>
                    <p class="text-fino text-texto-suave">
                        {{ $t('dashboard.bloques.periodos.descripcion') }}
                    </p>
                </div>

                <Deferred data="periodos">
                    <template #fallback>
                        <div class="flex flex-col gap-corto">
                            <Skeleton
                                v-for="fila in skeletonFilas"
                                :key="fila"
                                class="h-14 w-full"
                            />
                        </div>
                    </template>

                    <EstadoVacio
                        v-if="periodos && periodos.length === 0"
                        :titulo="$t('dashboard.bloques.periodos.vacio_titulo')"
                        :cuerpo="$t('dashboard.bloques.periodos.vacio_cuerpo')"
                    />
                    <ul
                        v-else-if="periodos"
                        class="flex flex-col divide-y divide-borde"
                    >
                        <li
                            v-for="periodo in periodos"
                            :key="periodo.id"
                            class="flex flex-col gap-minimo py-corto first:pt-0 last:pb-0"
                        >
                            <div
                                class="flex items-start justify-between gap-corto"
                            >
                                <Link
                                    :href="periodoShow(periodo.id)"
                                    class="min-w-0 truncate text-cuerpo font-medium text-texto hover:underline"
                                >
                                    {{ periodo.proyecto.nombre }} ·
                                    {{
                                        trans(
                                            'proyectos.resumen.periodo_numero',
                                            {
                                                consecutivo: String(
                                                    periodo.consecutivo,
                                                ),
                                            },
                                        )
                                    }}
                                </Link>
                                <Badge v-if="periodo.vencido" variant="outline">
                                    {{
                                        $t('dashboard.bloques.periodos.vencido')
                                    }}
                                </Badge>
                            </div>
                            <p class="text-fino text-texto-suave">
                                {{
                                    trans(
                                        'dashboard.bloques.periodos.dias_transcurridos',
                                        {
                                            dias: String(
                                                periodo.dias_transcurridos,
                                            ),
                                        },
                                    )
                                }}
                                ·
                                {{
                                    trans(
                                        'dashboard.bloques.periodos.tareas_sin_terminar',
                                        {
                                            total: String(
                                                periodo.tareas_sin_terminar,
                                            ),
                                        },
                                    )
                                }}
                            </p>
                            <p
                                v-if="periodo.vencido"
                                class="text-fino text-texto-suave"
                            >
                                {{
                                    $t(
                                        'dashboard.bloques.periodos.vencido_ayuda',
                                    )
                                }}
                            </p>
                        </li>
                    </ul>
                </Deferred>
            </section>

            <!-- Últimas reuniones -->
            <section
                class="flex flex-col gap-corto rounded-md border border-borde bg-panel px-celda py-bloque"
            >
                <div class="flex flex-col gap-minimo">
                    <h2 class="text-medio font-medium text-texto">
                        {{ $t('dashboard.bloques.reuniones.titulo') }}
                    </h2>
                    <p class="text-fino text-texto-suave">
                        {{ $t('dashboard.bloques.reuniones.descripcion') }}
                    </p>
                </div>

                <Deferred data="reuniones">
                    <template #fallback>
                        <div class="flex flex-col gap-corto">
                            <Skeleton
                                v-for="fila in skeletonFilas"
                                :key="fila"
                                class="h-12 w-full"
                            />
                        </div>
                    </template>

                    <EstadoVacio
                        v-if="reuniones && reuniones.length === 0"
                        :titulo="$t('dashboard.bloques.reuniones.vacio_titulo')"
                        :cuerpo="$t('dashboard.bloques.reuniones.vacio_cuerpo')"
                    />
                    <ul
                        v-else-if="reuniones"
                        class="flex flex-col divide-y divide-borde"
                    >
                        <li
                            v-for="reunion in reuniones"
                            :key="reunion.id"
                            class="flex flex-col gap-minimo py-corto first:pt-0 last:pb-0"
                        >
                            <Link
                                :href="reunionShow(reunion.id)"
                                class="min-w-0 truncate text-cuerpo font-medium text-texto hover:underline"
                            >
                                {{ reunion.titulo }}
                            </Link>
                            <p class="text-fino text-texto-suave">
                                {{ reunion.proyecto.nombre }} ·
                                {{ formatearFecha(reunion.fecha) }}
                            </p>
                        </li>
                    </ul>
                </Deferred>
            </section>

            <!-- Ingresos por periodo cerrado: ausente por completo para quien no ve pagos. -->
            <section
                v-if="veIngresos"
                class="flex flex-col gap-corto rounded-md border border-borde bg-panel px-celda py-bloque"
            >
                <div class="flex flex-col gap-minimo">
                    <h2 class="text-medio font-medium text-texto">
                        {{ $t('dashboard.bloques.ingresos.titulo') }}
                    </h2>
                    <p class="text-fino text-texto-suave">
                        {{ $t('dashboard.bloques.ingresos.descripcion') }}
                    </p>
                </div>

                <Deferred data="ingresos">
                    <template #fallback>
                        <div class="flex flex-col gap-corto">
                            <Skeleton
                                v-for="fila in skeletonFilas"
                                :key="fila"
                                class="h-10 w-full"
                            />
                        </div>
                    </template>

                    <EstadoVacio
                        v-if="ingresos && ingresos.length === 0"
                        :titulo="$t('dashboard.bloques.ingresos.vacio_titulo')"
                        :cuerpo="$t('dashboard.bloques.ingresos.vacio_cuerpo')"
                    />
                    <ul
                        v-else-if="ingresos"
                        class="flex flex-col divide-y divide-borde"
                    >
                        <li
                            v-for="fila in ingresos"
                            :key="fila.cliente"
                            class="flex items-center justify-between gap-corto py-corto first:pt-0 last:pb-0"
                        >
                            <div class="flex min-w-0 flex-col gap-minimo">
                                <span
                                    class="truncate text-cuerpo font-medium text-texto"
                                >
                                    {{ fila.cliente }}
                                </span>
                                <span class="text-fino text-texto-suave">
                                    {{
                                        trans(
                                            'dashboard.bloques.ingresos.periodos_count',
                                            {
                                                total: String(
                                                    fila.periodos_count,
                                                ),
                                            },
                                        )
                                    }}
                                </span>
                            </div>
                            <span
                                class="shrink-0 font-mono text-cuerpo font-medium text-texto"
                            >
                                {{ formatearMonto(fila.total_cop, 'COP') }}
                            </span>
                        </li>
                    </ul>
                </Deferred>
            </section>
        </div>
    </div>
</template>
