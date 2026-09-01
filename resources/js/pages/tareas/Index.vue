<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import { TablaDatos } from '@/components/TablaDatos';
import type { ColumnaDeTabla } from '@/components/TablaDatos';
import type { OrdenDeColumna } from '@/components/TablaDatos';
import BadgeEstado from '@/components/tareas/BadgeEstado.vue';
import BarraPrioridad from '@/components/tareas/BarraPrioridad.vue';
import IconoTipo from '@/components/tareas/IconoTipo.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { trans } from '@/lib/i18n';
import { show as mostrarProyecto } from '@/routes/proyectos';
import { create, index } from '@/routes/proyectos/tareas';
import { create as capturaRapida } from '@/routes/proyectos/tareas/rapida';
import { index as listado, show } from '@/routes/tareas';
import type { RespuestaPaginada } from '@/types/paginacion';
import type { FilaTarea, OpcionesDeTarea, TipoTarea } from '@/types/tareas';

/**
 * Sirve a los dos alcances: el listado de un proyecto
 * (`proyectos.tareas.index`) y el listado global de todas las tareas visibles
 * (`tareas.index`). Lo que los distingue es `proyecto`: cuando llega `null` no
 * hay proyecto y aparece la columna «Proyecto» en su lugar.
 *
 * Es el mismo listado con distinto alcance, así que es la misma página: dos
 * copias divergirían en la primera columna que alguien añadiera.
 */
const props = defineProps<{
    proyecto: {
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
    } | null;
    tareas: RespuestaPaginada<FilaTarea>;
    filtros: Record<string, string>;
    orden: OrdenDeColumna[];
    columnasVisibles: string[] | null;
    opciones: OpcionesDeTarea;
    puede: { crear: boolean };
}>();

/*
 * Las columnas se declaran **aquí**, no dentro de `TablaDatos`: el componente no
 * sabe qué es una tarea, y así el mismo componente sirve a clientes, proyectos y
 * a lo que venga.
 *
 * `ordenPor` tiene que coincidir con un `allowedSorts` de `TareaController`, y
 * `filtro.clave` con un `allowedFilters`.
 */
const esGlobal = computed(() => props.proyecto === null);

const columnas = computed((): ColumnaDeTabla<FilaTarea>[] => [
    {
        id: 'codigo',
        etiqueta: trans('tareas.codigo'),
        valor: (tarea) => tarea.codigo,
        ordenPor: 'codigo',
        anclada: true,
        fija: true,
        mono: true,
        // Titular de la tarjeta en móvil: es lo que identifica la tarea.
        principal: true,
        anchoMinimo: '10rem',
    },
    {
        id: 'tipo',
        etiqueta: trans('tareas.tipo'),
        valor: (tarea) => tarea.tipo,
        filtro: {
            tipo: 'opciones',
            clave: 'tipo',
            opciones: props.opciones.tipos.map((opcion) => ({
                valor: opcion.valor,
                etiqueta: trans(opcion.etiqueta),
            })),
        },
        // La cabecera lleva rótulo, leyenda y filtro; con 4rem el rótulo se
        // partía en dos líneas.
        anchoMinimo: '6rem',
    },
    {
        id: 'nombre',
        etiqueta: trans('comun.nombre'),
        valor: (tarea) => tarea.nombre,
        ordenPor: 'nombre',
        filtro: {
            tipo: 'texto',
            clave: 'texto',
            marcador: trans('comun.buscar'),
        },
        // En la tarjeta va arriba, junto al código y sin etiqueta: es el
        // texto que se lee, y ponerle «Nombre:» delante solo gasta ancho.
        principal: true,
        anchoMinimo: '18rem',
    },
    // El proyecto solo aparece en el listado global: dentro de un proyecto la
    // columna repetiría lo mismo en todas las filas.
    ...(esGlobal.value
        ? [
              {
                  id: 'proyecto',
                  etiqueta: trans('tareas.proyecto'),
                  valor: (tarea: FilaTarea) => tarea.proyecto?.nombre ?? null,
                  filtro: {
                      tipo: 'opciones' as const,
                      clave: 'proyecto_id',
                      opciones: (props.opciones.proyectos ?? []).map(
                          (proyecto) => ({
                              valor: String(proyecto.id),
                              etiqueta: proyecto.nombre,
                          }),
                      ),
                  },
                  prioritaria: true,
                  anchoMinimo: '14rem',
              },
          ]
        : []),
    {
        id: 'modulo',
        etiqueta: trans('tareas.modulo'),
        valor: (tarea) => tarea.modulo,
        // El filtro por módulo recorre el árbol de **un** proyecto; en el
        // listado global no hay un árbol, hay tantos como proyectos.
        filtro: esGlobal.value
            ? undefined
            : {
                  tipo: 'opciones',
                  clave: 'modulo_id',
                  opciones: props.opciones.modulos.map((modulo) => ({
                      valor: String(modulo.id),
                      etiqueta: modulo.ruta,
                  })),
              },
        prioritaria: !esGlobal.value,
        anchoMinimo: '14rem',
    },
    {
        id: 'estado',
        etiqueta: trans('comun.estado'),
        valor: (tarea) => tarea.estado,
        ordenPor: 'estado',
        filtro: {
            tipo: 'opciones',
            clave: 'estado',
            opciones: props.opciones.estados.map((opcion) => ({
                valor: opcion.valor,
                etiqueta: trans(opcion.etiqueta),
            })),
        },
        prioritaria: true,
        anchoMinimo: '8rem',
    },
    {
        id: 'prioridad',
        etiqueta: trans('tareas.prioridad'),
        valor: (tarea) => tarea.prioridad,
        ordenPor: 'prioridad',
        filtro: {
            tipo: 'opciones',
            clave: 'prioridad',
            opciones: props.opciones.prioridades.map((opcion) => ({
                valor: opcion.valor,
                etiqueta: trans(opcion.etiqueta),
            })),
        },
        anchoMinimo: '6rem',
    },
    // El periodo es del proyecto: en el listado global no dice nada.
    ...(esGlobal.value
        ? []
        : [
              {
                  id: 'periodo',
                  etiqueta: trans('tareas.periodo'),
                  valor: (tarea: FilaTarea) => tarea.periodo,
                  filtro: {
                      tipo: 'opciones' as const,
                      clave: 'periodo_id',
                      opciones: props.opciones.periodos.map((periodo) => ({
                          valor: String(periodo.id),
                          etiqueta: String(periodo.consecutivo),
                      })),
                  },
                  anchoMinimo: '6rem',
              },
          ]),
    {
        id: 'fecha_fin',
        etiqueta: trans('tareas.fecha_fin'),
        valor: (tarea) => tarea.fecha_fin,
        ordenPor: 'fecha_fin',
        mono: true,
        anchoMinimo: '7rem',
    },
    {
        id: 'arrastrada',
        etiqueta: trans('tareas.arrastrada'),
        valor: (tarea) => tarea.arrastrada,
        filtro: {
            tipo: 'opciones',
            clave: 'arrastradas',
            opciones: [
                {
                    valor: '1',
                    etiqueta: trans('tareas.filtros.solo_arrastradas'),
                },
            ],
        },
        // Nace oculta: importa al cerrar un periodo, no todos los días.
        ocultaPorDefecto: true,
        anchoMinimo: '7rem',
    },
]);

const abrir = (tarea: FilaTarea): void => {
    router.visit(show(tarea.id).url);
};

/*
 * La única acción en lote de esta fase. No inventa un endpoint nuevo —mover
 * tareas en bloque es trabajo de la fase 08— pero tampoco deja la selección de
 * adorno: copiar los códigos de una tanda es lo que se hace de verdad al armar
 * un correo o un reporte.
 */
const accionesEnLote = computed(() => [
    { id: 'copiar_codigos', etiqueta: trans('tareas.lote.copiar_codigos') },
]);

const enLote = async (
    accion: string,
    claves: Array<string | number>,
): Promise<void> => {
    if (accion !== 'copiar_codigos') {
        return;
    }

    const codigos = props.tareas.data
        .filter((tarea) => claves.includes(tarea.id))
        .map((tarea) => tarea.codigo);

    await navigator.clipboard.writeText(codigos.join('\n'));

    toast.success(
        trans('tareas.lote.codigos_copiados', {
            total: String(codigos.length),
        }),
    );
};
</script>

<template>
    <Head :title="esGlobal ? $t('tareas.todas.titulo') : $t('tareas.titulo')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="esGlobal ? $t('tareas.todas.titulo') : $t('tareas.titulo')"
            :descripcion="
                esGlobal
                    ? $t('tareas.todas.descripcion')
                    : (props.proyecto?.nombre ?? null)
            "
        >
            <template v-if="props.proyecto" #acciones>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="mostrarProyecto(props.proyecto.id)">{{
                        $t('comun.volver')
                    }}</Link>
                </Button>
                <template v-if="props.puede.crear">
                    <Button as-child variant="outline" size="sm">
                        <Link :href="capturaRapida(props.proyecto.id)">
                            {{ $t('tareas.rapida.titulo') }}
                        </Link>
                    </Button>
                    <Button as-child size="sm">
                        <Link :href="create(props.proyecto.id)">{{
                            $t('tareas.crear')
                        }}</Link>
                    </Button>
                </template>
            </template>
        </CabeceraDePagina>

        <TablaDatos
            :url="props.proyecto ? index(props.proyecto.id).url : listado().url"
            :filas="props.tareas.data"
            :columnas="columnas"
            :clave="(tarea) => tarea.id"
            :orden="props.orden"
            :filtros="props.filtros"
            :columnas-visibles="props.columnasVisibles"
            :paginacion="props.tareas"
            :etiqueta="
                esGlobal ? $t('tareas.todas.titulo') : $t('tareas.titulo')
            "
            :vacio="{
                titulo: $t('tareas.sin_tareas'),
                cuerpo: esGlobal
                    ? $t('tareas.todas.sin_tareas_ayuda')
                    : $t('tareas.sin_tareas_ayuda'),
            }"
            seleccionable
            :acciones-en-lote="accionesEnLote"
            @abrir="abrir"
            @en-lote="enLote"
        >
            <template #vacio-acciones>
                <Button
                    v-if="props.puede.crear && props.proyecto"
                    as-child
                    size="sm"
                >
                    <Link :href="create(props.proyecto.id)">{{
                        $t('tareas.crear')
                    }}</Link>
                </Button>
            </template>

            <!-- Barra = prioridad, icono = tipo, badge = estado. Cada eje por su
                 canal visual; si los tres llevaran relleno sería ilegible. -->
            <template #celda-codigo="{ fila }">
                <div class="flex items-stretch gap-corto">
                    <BarraPrioridad :prioridad="fila.prioridad" />
                    <Link
                        :href="show(fila.id)"
                        class="underline-offset-4 hover:underline"
                    >
                        {{ fila.codigo }}
                    </Link>
                </div>
            </template>

            <!--
                Clave de lectura de la columna de iconos. Sin ella, quien abre
                la aplicación ve cinco siluetas y ninguna forma de saber qué
                distingue una de otra: el `title` de cada icono resuelve la duda
                de uno en uno, la leyenda las resuelve todas de golpe.

                La escribe la página porque `TablaDatos` no sabe qué es un tipo
                de tarea; el componente solo sabe que esta columna tiene leyenda.
            -->
            <template #leyenda-tipo>
                <ul class="flex flex-col gap-corto">
                    <li
                        v-for="opcion in props.opciones.tipos"
                        :key="opcion.valor"
                        class="flex items-center gap-corto text-cuerpo text-texto"
                    >
                        <IconoTipo :tipo="opcion.valor as TipoTarea" />
                        {{ $t(opcion.etiqueta) }}
                    </li>
                </ul>
            </template>

            <template #celda-tipo="{ fila }">
                <IconoTipo :tipo="fila.tipo" />
            </template>

            <template #celda-nombre="{ fila }">
                <Link
                    :href="show(fila.id)"
                    class="underline-offset-4 hover:underline"
                >
                    {{ fila.nombre }}
                </Link>
            </template>

            <template #celda-proyecto="{ fila }">
                <Link
                    v-if="fila.proyecto"
                    :href="mostrarProyecto(fila.proyecto.id)"
                    class="underline-offset-4 hover:underline"
                >
                    {{ fila.proyecto.nombre }}
                </Link>
                <span v-else class="text-texto-tenue">—</span>
            </template>

            <template #celda-modulo="{ fila }">
                <span class="text-texto-suave">{{
                    fila.modulo ?? $t('tareas.sin_modulo')
                }}</span>
            </template>

            <template #celda-estado="{ fila }">
                <BadgeEstado :estado="fila.estado" tamano="pequeno" />
            </template>

            <template #celda-prioridad="{ fila }">
                <span class="flex items-center gap-corto">
                    <BarraPrioridad :prioridad="fila.prioridad" />
                    <span class="text-texto-suave">
                        {{ $t(`enums.prioridad_tarea.${fila.prioridad}`) }}
                    </span>
                </span>
            </template>

            <template #celda-periodo="{ fila }">
                <span class="text-texto-suave">{{
                    fila.periodo ?? $t('tareas.sin_periodo')
                }}</span>
            </template>

            <template #celda-fecha_fin="{ fila }">
                <span class="text-texto-suave">{{
                    fila.fecha_fin ?? '—'
                }}</span>
            </template>

            <template #celda-arrastrada="{ fila }">
                <Badge v-if="fila.arrastrada" variant="outline">
                    {{ $t('tareas.arrastrada') }}
                </Badge>
                <span v-else class="text-texto-tenue">—</span>
            </template>
        </TablaDatos>
    </div>
</template>
