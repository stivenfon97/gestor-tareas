<script setup lang="ts">
import { Deferred, Head, Link, router } from '@inertiajs/vue3';
import { MoreHorizontal } from '@lucide/vue';
import { computed, ref } from 'vue';
import PanelDeAdjuntos from '@/components/archivos/PanelDeAdjuntos.vue';
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
import { Skeleton } from '@/components/ui/skeleton';
import { trans } from '@/lib/i18n';
import { destroy, edit, show } from '@/routes/proyectos';
import { index as agendaIndex } from '@/routes/proyectos/agenda';
import { index as listaIndex } from '@/routes/proyectos/lista';
import { index as miembrosIndex } from '@/routes/proyectos/miembros';
import { index as modulosIndex } from '@/routes/proyectos/modulos';
import { index as pagosIndex } from '@/routes/proyectos/pagos';
import { index as periodosIndex } from '@/routes/proyectos/periodos';
import {
    destroy as borrarRespaldo,
    store as subirRespaldo,
} from '@/routes/proyectos/respaldos';
import { index as reunionesIndex } from '@/routes/proyectos/reuniones';
import { index as tableroIndex } from '@/routes/proyectos/tablero';
import { index as tareasIndex } from '@/routes/proyectos/tareas';
import type { Adjunto } from '@/types/archivos';

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
        gestionarRespaldos: boolean;
    };
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
                            class="flex flex-col gap-corto sm:px-bloque sm:first:pr-bloque sm:first:pl-0 sm:last:pl-bloque sm:last:pr-0"
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
                                    $t(
                                        'proyectos.resumen.dias_transcurridos',
                                        {
                                            dias: String(
                                                props.resumen.periodo_abierto
                                                    .dias_transcurridos,
                                            ),
                                        },
                                    )
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
                                    monto: props.resumen.total_pagado_usd,
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
                            <p class="font-mono text-fino text-texto-suave">
                                {{ props.resumen.ultima_reunion.fecha }}
                            </p>
                        </template>
                        <p v-else class="text-cuerpo text-texto-suave">
                            {{ $t('proyectos.resumen.sin_reuniones') }}
                        </p>
                    </div>
                </div>
            </Deferred>
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
                 opción más entre varias. Las otras tres formas de ver lo mismo
                 van al lado, en secundaria: son alternativas, no el camino. -->
            <div class="flex flex-wrap items-center gap-corto">
                <Button as-child size="sm">
                    <Link :href="tareasIndex(props.proyecto.id)">{{
                        $t('proyectos.ver_tareas')
                    }}</Link>
                </Button>
                <Button as-child variant="outline" size="sm">
                    <Link :href="tableroIndex(props.proyecto.id)">{{
                        $t('tablero.vistas.tablero')
                    }}</Link>
                </Button>
                <Button as-child variant="outline" size="sm">
                    <Link :href="listaIndex(props.proyecto.id)">{{
                        $t('tablero.vistas.lista')
                    }}</Link>
                </Button>
                <Button as-child variant="outline" size="sm">
                    <Link :href="agendaIndex(props.proyecto.id)">{{
                        $t('tablero.vistas.calendario')
                    }}</Link>
                </Button>
            </div>
        </section>

        <!-- Periodos y pagos: contenido real desde la fase 09 -->
        <section
            v-else-if="props.seccion === 'periodos'"
            class="flex flex-col gap-bloque"
        >
            <div class="flex flex-col items-start gap-corto">
                <Deferred data="resumen">
                    <template #fallback>
                        <Skeleton class="h-5 w-48" />
                    </template>
                    <p v-if="props.resumen" class="text-cuerpo text-texto-suave">
                        {{ $t('proyectos.resumen.periodo_abierto') }}:
                        <span v-if="props.resumen.periodo_abierto">
                            {{
                                $t('proyectos.resumen.periodo_numero', {
                                    consecutivo: String(
                                        props.resumen.periodo_abierto
                                            .consecutivo,
                                    ),
                                })
                            }}
                        </span>
                        <span v-else>{{
                            $t('proyectos.resumen.sin_periodo_abierto')
                        }}</span>
                    </p>
                </Deferred>
                <Button as-child size="sm">
                    <Link :href="periodosIndex(props.proyecto.id)">{{
                        $t('proyectos.secciones.periodos')
                    }}</Link>
                </Button>
            </div>

            <!-- Ocultarlo no es el permiso: PagoPolicy niega la ruta igualmente. -->
            <div
                v-if="props.puede.verPagos"
                class="flex flex-col items-start gap-corto"
            >
                <p class="text-cuerpo text-texto-suave">
                    {{ $t('proyectos.secciones.pagos') }}
                </p>
                <Button as-child variant="outline" size="sm">
                    <Link :href="pagosIndex(props.proyecto.id)">{{
                        $t('comun.ver')
                    }}</Link>
                </Button>
            </div>
        </section>

        <template v-else-if="props.seccion === 'bitacora'">
            <section class="flex flex-col items-start gap-corto">
                <h3 class="text-cuerpo font-medium text-texto">
                    {{ trans('proyectos.secciones.reuniones') }}
                </h3>
                <Deferred data="resumen">
                    <template #fallback>
                        <Skeleton class="h-5 w-48" />
                    </template>
                    <p v-if="props.resumen" class="text-cuerpo text-texto-suave">
                        <template v-if="props.resumen.ultima_reunion">
                            {{ trans('proyectos.resumen.ultima_reunion') }}:
                            {{ props.resumen.ultima_reunion.titulo }} ({{
                                props.resumen.ultima_reunion.fecha
                            }})
                        </template>
                        <template v-else>{{
                            trans('proyectos.resumen.sin_reuniones')
                        }}</template>
                    </p>
                </Deferred>
                <Button as-child size="sm">
                    <Link :href="reunionesIndex(props.proyecto.id)">{{
                        trans('reuniones.bitacora')
                    }}</Link>
                </Button>
            </section>

            <section
                v-if="props.puede.gestionarRespaldos"
                class="flex flex-col gap-corto"
            >
                <h3 class="text-cuerpo font-medium text-texto">
                    {{ trans('archivos.coleccion.respaldos_bd') }}
                </h3>
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
        </template>

        <SeccionPendiente
            v-else-if="props.seccion === 'reportes'"
            :titulo="$t('proyectos.secciones.reportes')"
            :fase="11"
        />
    </div>
</template>
