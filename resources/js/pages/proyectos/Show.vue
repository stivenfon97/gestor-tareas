<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { MoreHorizontal } from '@lucide/vue';
import { computed, ref } from 'vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import ConfirmarPorNombre from '@/components/ConfirmarPorNombre.vue';
import SeccionPendiente from '@/components/SeccionPendiente.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { trans } from '@/lib/i18n';
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

/*
 * Eliminar un proyecto estaba en la esquina superior derecha, al lado de
 * «Editar» y del mismo tamaño: justo donde el ratón va por defecto. Una acción
 * que se lleva por delante módulos, tareas, periodos, pagos y reuniones no
 * puede competir en prominencia con la acción de todos los días.
 *
 * Baja dos escalones: vive en el menú de acciones secundarias y confirma
 * escribiendo el nombre del proyecto.
 */
const confirmando = ref(false);

const eliminar = (): void => {
    confirmando.value = false;
    router.delete(destroy(props.proyecto.id).url);
};
</script>

<template>
    <Head :title="props.proyecto.nombre" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="props.proyecto.nombre"
            :descripcion="
                props.proyecto.descripcion ?? props.proyecto.cliente.nombre
            "
        >
            <template #acciones>
                <!-- Primaria: es lo que se viene a hacer a esta pantalla. -->
                <Button v-if="props.puede.editar" as-child size="sm">
                    <Link :href="edit(props.proyecto.id)">{{
                        $t('comun.editar')
                    }}</Link>
                </Button>

                <DropdownMenu v-if="props.puede.eliminar">
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="outline"
                            size="icon-sm"
                            :title="$t('comun.mas_acciones')"
                            :aria-label="$t('comun.mas_acciones')"
                        >
                            <MoreHorizontal class="size-4" aria-hidden="true" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <DropdownMenuItem
                            variant="destructive"
                            class="text-cuerpo"
                            @select="confirmando = true"
                        >
                            {{ $t('proyectos.eliminar') }}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </template>

            <template #meta>
                <Badge variant="outline" class="font-mono">{{
                    props.proyecto.prefijo_tarea
                }}</Badge>
                <Badge variant="secondary">
                    {{ $t(`enums.estado_proyecto.${props.proyecto.estado}`) }}
                </Badge>
                <span class="text-cuerpo text-texto-suave">{{
                    props.proyecto.cliente.nombre
                }}</span>
                <span v-if="props.miRol" class="text-cuerpo text-texto-suave">
                    {{ $t('proyectos.mi_rol') }}:
                    {{ $t(`enums.rol_proyecto.${props.miRol}`) }}
                </span>
            </template>
        </CabeceraDePagina>

        <ConfirmarPorNombre
            v-if="props.puede.eliminar"
            v-model:abierto="confirmando"
            :titulo="$t('proyectos.eliminar_confirmar_titulo')"
            :descripcion="$t('proyectos.eliminar_confirmar_cuerpo')"
            :nombre="props.proyecto.nombre"
            :etiqueta-campo="
                $t('proyectos.eliminar_confirmar_campo', {
                    nombre: props.proyecto.nombre,
                })
            "
            :boton="$t('proyectos.eliminar')"
            @confirmar="eliminar"
        />

        <!--
            Pestañas. Se desplazan a lo ancho en móvil en lugar de partirse en
            tres filas: siete pestañas envueltas ocupan más que el contenido
            que anuncian.
        -->
        <nav
            class="-mx-bloque flex gap-fino overflow-x-auto border-b border-borde px-bloque md:mx-0 md:flex-wrap md:overflow-visible md:px-0"
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
                class="-mb-px shrink-0 border-b-2 px-celda py-corto text-cuerpo"
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
            <!--
                `max-w-4xl`: tres hechos sobre el mismo proyecto se leen como un
                grupo, y repartidos entre 1140px de ancho quedan uno en cada
                esquina de la pantalla. La tabla sí ocupa todo el ancho —alinear
                columnas es su trabajo—; un resumen de tres datos, no.

                Cada celda es una pila vertical: cuando la fase 09 traiga fechas
                y montos, el periodo abierto gana líneas y el bloque crece hacia
                abajo sin rehacerse.
            -->
            <div
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
            class="flex flex-col items-start gap-corto"
        >
            <p class="text-cuerpo text-texto-suave">
                {{ $t('proyectos.tareas_abiertas') }}:
                {{ props.proyecto.tareas_abiertas_count }} /
                {{ props.proyecto.tareas_count }}
            </p>
            <!-- Primaria, no `outline`: es el destino de la pestaña, no una
                 opción más entre varias. -->
            <Button as-child size="sm">
                <Link :href="tareasIndex(props.proyecto.id)">{{
                    $t('proyectos.ver_tareas')
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
