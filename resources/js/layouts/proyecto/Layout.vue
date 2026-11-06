<script setup lang="ts">
/**
 * Marco de todas las pantallas de un proyecto: cabecera y pestañas.
 *
 * Antes las pestañas vivían dentro de `proyectos/Show.vue` y cada una solo
 * enseñaba un contador y un botón «Ver» que sacaba al usuario del proyecto a
 * otra pantalla con otra cabecera. Ahora cada pestaña **es** la pantalla real
 * de la sección (su ruta, su controlador, su tabla con el estado en la URL) y
 * este layout persistente la envuelve: al cambiar de pestaña solo cambia el
 * contenido, la cabecera no se vuelve a montar.
 *
 * Lo que pinta llega en la prop compartida `cabeceraDeProyecto`
 * (`App\Services\PresentarCabeceraDeProyecto`). Si la página no la trae —el
 * listado global de tareas usa la misma página que el del proyecto— el layout
 * se aparta y deja solo el contenido.
 *
 * Las pestañas que el rol no puede abrir no se listan; quien manda es la
 * policy de cada ruta, que el servidor ya consultó para decidir la lista.
 */
import { Link, router, usePage } from '@inertiajs/vue3';
import { FolderGit2, MoreHorizontal } from '@lucide/vue';
import { computed, ref } from 'vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import ConfirmarPorNombre from '@/components/ConfirmarPorNombre.vue';
import HistorialDelRegistro from '@/components/historial/HistorialDelRegistro.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { trans } from '@/lib/i18n';
import { tonoDeEstadoProyecto } from '@/lib/tonos';
import { destroy, edit, show } from '@/routes/proyectos';
import { index as agendaIndex } from '@/routes/proyectos/agenda';
import { index as listaIndex } from '@/routes/proyectos/lista';
import { index as miembrosIndex } from '@/routes/proyectos/miembros';
import { index as modulosIndex } from '@/routes/proyectos/modulos';
import { index as pagosIndex } from '@/routes/proyectos/pagos';
import { index as periodosIndex } from '@/routes/proyectos/periodos';
import { index as reportesIndex } from '@/routes/proyectos/reportes';
import { index as reunionesIndex } from '@/routes/proyectos/reuniones';
import { index as tableroIndex } from '@/routes/proyectos/tablero';
import { index as tareasIndex } from '@/routes/proyectos/tareas';

type CabeceraDeProyecto = {
    id: number;
    nombre: string;
    descripcion: string | null;
    prefijo_tarea: string;
    estado: string;
    cliente: { id: number; nombre: string };
    miRol: string | null;
    puede: { editar: boolean; eliminar: boolean; verHistorial: boolean };
    pestanas: Record<string, boolean>;
};

/*
 * Inertia entrega a un layout persistente todas las props de la página. Este
 * no declara ninguna (lee `cabeceraDeProyecto` de `usePage`), así que sin esto
 * acabarían como atributos sueltos en el `<div>` raíz.
 */
defineOptions({ inheritAttrs: false });

const pagina = usePage<{ cabeceraDeProyecto?: CabeceraDeProyecto }>();
const proyecto = computed(() => pagina.props.cabeceraDeProyecto ?? null);

const { currentUrl } = useCurrentUrl();

/**
 * Una pestaña está activa si la URL actual es una de sus rutas o cuelga de
 * ella. Tareas tiene cuatro: listado, tablero, lista agrupada y calendario son
 * formas de ver lo mismo, y el selector de vista vive dentro de la sección.
 */
const pestanas = computed(() => {
    if (!proyecto.value) {
        return [];
    }

    const id = proyecto.value.id;
    const visibles = proyecto.value.pestanas;

    return [
        {
            id: 'resumen',
            etiqueta: trans('proyectos.resumen.titulo'),
            href: show(id).url,
            rutas: [],
            visible: true,
        },
        {
            id: 'modulos',
            etiqueta: trans('proyectos.secciones.modulos'),
            href: modulosIndex(id).url,
            rutas: [modulosIndex(id).url],
            visible: visibles.modulos,
        },
        {
            id: 'equipo',
            etiqueta: trans('proyectos.secciones.equipo'),
            href: miembrosIndex(id).url,
            rutas: [miembrosIndex(id).url],
            visible: visibles.equipo,
        },
        {
            id: 'tareas',
            etiqueta: trans('proyectos.secciones.tareas'),
            href: tareasIndex(id).url,
            rutas: [
                tareasIndex(id).url,
                tableroIndex(id).url,
                listaIndex(id).url,
                agendaIndex(id).url,
            ],
            visible: visibles.tareas,
        },
        {
            id: 'periodos',
            etiqueta: trans('proyectos.secciones.periodos'),
            href: periodosIndex(id).url,
            rutas: [periodosIndex(id).url],
            visible: visibles.periodos,
        },
        {
            id: 'pagos',
            etiqueta: trans('proyectos.secciones.pagos'),
            href: pagosIndex(id).url,
            rutas: [pagosIndex(id).url],
            visible: visibles.pagos,
        },
        {
            id: 'bitacora',
            etiqueta: trans('proyectos.secciones.reuniones'),
            href: reunionesIndex(id).url,
            rutas: [reunionesIndex(id).url],
            visible: visibles.bitacora,
        },
        {
            id: 'reportes',
            etiqueta: trans('proyectos.secciones.reportes'),
            href: reportesIndex(id).url,
            rutas: [reportesIndex(id).url],
            visible: visibles.reportes,
        },
    ].filter((pestana) => pestana.visible);
});

const activa = computed(() => {
    const ruta = currentUrl.value;
    const coincide = pestanas.value.find((pestana) =>
        pestana.rutas.some(
            (base) => ruta === base || ruta.startsWith(`${base}/`),
        ),
    );

    return coincide?.id ?? 'resumen';
});

/*
 * Eliminar un proyecto se lleva por delante módulos, tareas, periodos, pagos y
 * reuniones: vive en el menú de acciones secundarias, no al lado de «Editar»,
 * y confirma escribiendo el nombre del proyecto.
 */
const confirmando = ref(false);

const eliminar = (): void => {
    if (!proyecto.value) {
        return;
    }

    confirmando.value = false;
    router.delete(destroy(proyecto.value.id).url);
};
</script>

<template>
    <div v-if="proyecto" class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :icono="FolderGit2"
            :titulo="proyecto.nombre"
            :descripcion="proyecto.descripcion ?? proyecto.cliente.nombre"
        >
            <template #acciones>
                <HistorialDelRegistro
                    v-if="proyecto.puede.verHistorial"
                    tipo="proyecto"
                    :id="proyecto.id"
                />
                <!-- Primaria: es la acción del proyecto, no de la sección. -->
                <Button v-if="proyecto.puede.editar" as-child size="sm">
                    <Link :href="edit(proyecto.id)">{{
                        $t('comun.editar')
                    }}</Link>
                </Button>

                <DropdownMenu v-if="proyecto.puede.eliminar">
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
                    proyecto.prefijo_tarea
                }}</Badge>
                <Badge :variant="tonoDeEstadoProyecto(proyecto.estado)">
                    <span
                        class="size-1.5 rounded-full bg-current"
                        aria-hidden="true"
                    />
                    {{ $t(`enums.estado_proyecto.${proyecto.estado}`) }}
                </Badge>
                <span class="text-cuerpo text-texto-suave">{{
                    proyecto.cliente.nombre
                }}</span>
                <span
                    v-if="proyecto.miRol"
                    class="text-cuerpo text-texto-suave"
                >
                    {{ $t('proyectos.mi_rol') }}:
                    {{ $t(`enums.rol_proyecto.${proyecto.miRol}`) }}
                </span>
            </template>
        </CabeceraDePagina>

        <ConfirmarPorNombre
            v-if="proyecto.puede.eliminar"
            v-model:abierto="confirmando"
            :titulo="$t('proyectos.eliminar_confirmar_titulo')"
            :descripcion="$t('proyectos.eliminar_confirmar_cuerpo')"
            :nombre="proyecto.nombre"
            :etiqueta-campo="
                $t('proyectos.eliminar_confirmar_campo', {
                    nombre: proyecto.nombre,
                })
            "
            :boton="$t('proyectos.eliminar')"
            @confirmar="eliminar"
        />

        <!--
            Pestañas. Se desplazan a lo ancho en móvil en lugar de partirse en
            varias filas: ocho pestañas envueltas ocupan más que el contenido
            que anuncian.
        -->
        <nav
            class="-mx-bloque flex gap-fino overflow-x-auto border-b border-borde px-bloque md:mx-0 md:flex-wrap md:overflow-visible md:px-0"
            :aria-label="proyecto.nombre"
        >
            <Link
                v-for="pestana in pestanas"
                :key="pestana.id"
                :href="pestana.href"
                preserve-scroll
                :aria-current="activa === pestana.id ? 'page' : undefined"
                class="-mb-px shrink-0 border-b-2 px-celda py-corto text-cuerpo"
                :class="
                    activa === pestana.id
                        ? 'border-marca font-medium text-texto'
                        : 'border-transparent text-texto-suave hover:text-texto'
                "
            >
                {{ pestana.etiqueta }}
            </Link>
        </nav>

        <slot />
    </div>

    <div v-else>
        <slot />
    </div>
</template>
