<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import SeccionPendiente from '@/components/SeccionPendiente.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { destroy, edit, show } from '@/routes/proyectos';
import { index as miembrosIndex } from '@/routes/proyectos/miembros';
import { index as modulosIndex } from '@/routes/proyectos/modulos';
import { index as pagosIndex } from '@/routes/proyectos/pagos';
import { index as periodosIndex } from '@/routes/proyectos/periodos';
import { index as reunionesIndex } from '@/routes/proyectos/reuniones';
import { index as tareasIndex } from '@/routes/proyectos/tareas';

const props = defineProps<{
    proyecto: {
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
        descripcion: string | null;
        estado: string;
        cliente: { id: number; nombre: string };
        modulos_count: number;
        tareas_count: number;
        tareas_abiertas_count: number;
        miembros_count: number;
    };
    resumen: {
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
    };
    seccion: string;
    miRol: string | null;
    puede: {
        editar: boolean;
        eliminar: boolean;
        gestionarMiembros: boolean;
        gestionarModulos: boolean;
        verPagos: boolean;
        verReuniones: boolean;
        generarReportes: boolean;
    };
}>();

const estados = computed(() => Object.entries(props.resumen.tareas_por_estado));

/*
 * Pestañas en lugar de una página larga.
 *
 * Con tareas y Kanban dentro, la página única se convierte en un scroll donde
 * lo importante queda abajo. Las que el rol no permite ver **no se listan**,
 * pero eso es cosmética: quien manda es la policy de cada ruta.
 */
const pestanas = computed(() =>
    [
        {
            id: 'resumen',
            etiqueta: trans('proyectos.resumen.titulo'),
            visible: true,
        },
        {
            id: 'modulos',
            etiqueta: trans('proyectos.secciones.modulos'),
            visible: true,
        },
        {
            id: 'equipo',
            etiqueta: trans('proyectos.secciones.equipo'),
            visible: true,
        },
        {
            id: 'tareas',
            etiqueta: trans('proyectos.secciones.tareas'),
            visible: true,
        },
        {
            id: 'periodos',
            etiqueta: trans('proyectos.secciones.periodos'),
            visible: true,
        },
        {
            id: 'bitacora',
            etiqueta: trans('proyectos.secciones.reuniones'),
            visible: props.puede.verReuniones,
        },
        {
            id: 'reportes',
            etiqueta: trans('proyectos.secciones.reportes'),
            visible: props.puede.generarReportes,
        },
    ].filter((pestana) => pestana.visible),
);

const enlaceDePestana = (id: string): string =>
    id === 'resumen'
        ? show(props.proyecto.id).url
        : `${show(props.proyecto.id).url}?seccion=${id}`;

const eliminar = (): void => {
    router.delete(destroy(props.proyecto.id).url);
};
</script>

<template>
    <Head :title="props.proyecto.nombre" />

    <div class="flex flex-col gap-seccion">
        <div class="flex items-start justify-between gap-bloque">
            <Heading
                variant="small"
                :title="props.proyecto.nombre"
                :description="props.proyecto.cliente.nombre"
            />
            <div class="flex items-center gap-corto">
                <Button
                    v-if="props.puede.editar"
                    as-child
                    size="sm"
                    variant="secondary"
                >
                    <Link :href="edit(props.proyecto.id)">{{
                        $t('comun.editar')
                    }}</Link>
                </Button>
                <Button
                    v-if="props.puede.eliminar"
                    size="sm"
                    variant="outline"
                    @click="eliminar"
                >
                    {{ $t('proyectos.eliminar') }}
                </Button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-celda text-cuerpo">
            <Badge variant="outline" class="font-mono">{{
                props.proyecto.prefijo_tarea
            }}</Badge>
            <Badge variant="secondary">
                {{ $t(`enums.estado_proyecto.${props.proyecto.estado}`) }}
            </Badge>
            <span v-if="props.miRol" class="text-texto-suave">
                {{ $t('proyectos.mi_rol') }}:
                {{ $t(`enums.rol_proyecto.${props.miRol}`) }}
            </span>
        </div>

        <p
            v-if="props.proyecto.descripcion"
            class="max-w-3xl text-cuerpo text-texto-suave"
        >
            {{ props.proyecto.descripcion }}
        </p>

        <!-- Pestañas -->
        <nav
            class="flex flex-wrap gap-fino border-b border-borde"
            :aria-label="props.proyecto.nombre"
        >
            <Link
                v-for="pestana in pestanas"
                :key="pestana.id"
                :href="enlaceDePestana(pestana.id)"
                preserve-scroll
                :aria-current="
                    props.seccion === pestana.id ? 'page' : undefined
                "
                class="-mb-px border-b-2 px-celda py-corto text-cuerpo"
                :class="
                    props.seccion === pestana.id
                        ? 'border-marca font-medium text-texto'
                        : 'border-transparent text-texto-suave hover:text-texto'
                "
            >
                {{ pestana.etiqueta }}
            </Link>
        </nav>

        <!--
            Resumen. Ya no son tres tarjetas idénticas con borde: son tres
            hechos sobre un mismo proyecto, y de corrido con divisores se leen
            mejor que troceados en cajas. Ver docs/05-identidad-visual.md §7.1.
        -->
        <section
            v-if="props.seccion === 'resumen'"
            class="flex flex-col gap-bloque"
        >
            <div
                class="grid gap-bloque sm:grid-cols-3 sm:divide-x sm:divide-borde"
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
                </div>

                <div class="flex flex-col gap-corto sm:pl-bloque">
                    <p class="text-fino font-medium text-texto-suave">
                        {{ $t('proyectos.resumen.ultima_reunion') }}
                    </p>
                    <template v-if="props.resumen.ultima_reunion">
                        <p class="text-cuerpo font-medium">
                            {{ props.resumen.ultima_reunion.titulo }}
                        </p>
                        <p class="font-mono text-fino text-texto-suave">
                            {{ props.resumen.ultima_reunion.fecha }}
                        </p>
                    </template>
                    <p v-else class="text-cuerpo text-texto-suave">
                        {{ $t('proyectos.resumen.sin_reuniones') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Módulos: contenido real desde la fase 05 -->
        <section
            v-else-if="props.seccion === 'modulos'"
            class="flex flex-col gap-corto"
        >
            <p class="text-cuerpo text-texto-suave">
                {{ $t('proyectos.resumen.modulos') }}:
                {{ props.proyecto.modulos_count }}
            </p>
            <Button as-child variant="outline" size="sm" class="self-start">
                <Link :href="modulosIndex(props.proyecto.id)">{{
                    $t('comun.ver')
                }}</Link>
            </Button>
        </section>

        <!-- Equipo: contenido real desde la fase 04 -->
        <section
            v-else-if="props.seccion === 'equipo'"
            class="flex flex-col gap-corto"
        >
            <p class="text-cuerpo text-texto-suave">
                {{ $t('proyectos.resumen.miembros') }}:
                {{ props.proyecto.miembros_count }}
            </p>
            <Button
                v-if="props.puede.gestionarMiembros"
                as-child
                variant="outline"
                size="sm"
                class="self-start"
            >
                <Link :href="miembrosIndex(props.proyecto.id)">{{
                    $t('comun.ver')
                }}</Link>
            </Button>
        </section>

        <!-- Tareas: contenido real desde la fase 06 -->
        <section
            v-else-if="props.seccion === 'tareas'"
            class="flex flex-col gap-corto"
        >
            <p class="text-cuerpo text-texto-suave">
                {{ $t('proyectos.tareas_abiertas') }}:
                {{ props.proyecto.tareas_abiertas_count }} /
                {{ props.proyecto.tareas_count }}
            </p>
            <Button as-child variant="outline" size="sm" class="self-start">
                <Link :href="tareasIndex(props.proyecto.id)">{{
                    $t('comun.ver')
                }}</Link>
            </Button>
        </section>

        <!--
            De aquí abajo, secciones sin contenido todavía. Cada una dice qué
            fase la llena. **Nunca datos de mentira**: un placeholder con cifras
            inventadas se olvida y acaba en producción.
        -->
        <template v-else-if="props.seccion === 'periodos'">
            <SeccionPendiente
                :titulo="$t('proyectos.secciones.periodos')"
                :fase="9"
            >
                <Button as-child variant="outline" size="sm">
                    <Link :href="periodosIndex(props.proyecto.id)">{{
                        $t('comun.ver')
                    }}</Link>
                </Button>
            </SeccionPendiente>

            <!-- Ocultarlo no es el permiso: PagoPolicy niega la ruta igualmente. -->
            <SeccionPendiente
                v-if="props.puede.verPagos"
                :titulo="$t('proyectos.secciones.pagos')"
                :fase="9"
            >
                <Button as-child variant="outline" size="sm">
                    <Link :href="pagosIndex(props.proyecto.id)">{{
                        $t('comun.ver')
                    }}</Link>
                </Button>
            </SeccionPendiente>
        </template>

        <template v-else-if="props.seccion === 'bitacora'">
            <SeccionPendiente
                :titulo="$t('proyectos.secciones.reuniones')"
                :fase="10"
            >
                <Button as-child variant="outline" size="sm">
                    <Link :href="reunionesIndex(props.proyecto.id)">{{
                        $t('comun.ver')
                    }}</Link>
                </Button>
            </SeccionPendiente>

            <SeccionPendiente
                :titulo="$t('proyectos.secciones.archivos')"
                :fase="10"
            />
        </template>

        <SeccionPendiente
            v-else-if="props.seccion === 'reportes'"
            :titulo="$t('proyectos.secciones.reportes')"
            :fase="11"
        />
    </div>
</template>
