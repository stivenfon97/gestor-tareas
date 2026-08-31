<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
import { TablaDatos } from '@/components/TablaDatos';
import type { ColumnaDeTabla } from '@/components/TablaDatos';
import type { OrdenDeColumna } from '@/components/TablaDatos';
import BadgeEstado from '@/components/tareas/BadgeEstado.vue';
import BarraPrioridad from '@/components/tareas/BarraPrioridad.vue';
import IconoTipo from '@/components/tareas/IconoTipo.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { show as mostrarProyecto } from '@/routes/proyectos';
import { create, index } from '@/routes/proyectos/tareas';
import { create as capturaRapida } from '@/routes/proyectos/tareas/rapida';
import { show } from '@/routes/tareas';
import type { RespuestaPaginada } from '@/types/paginacion';
import type { FilaTarea, OpcionesDeTarea } from '@/types/tareas';

const props = defineProps<{
    proyecto: {
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
    };
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
const columnas = computed((): ColumnaDeTabla<FilaTarea>[] => [
    {
        id: 'codigo',
        etiqueta: trans('tareas.codigo'),
        valor: (tarea) => tarea.codigo,
        ordenPor: 'codigo',
        anclada: true,
        fija: true,
        mono: true,
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
        anchoMinimo: '4rem',
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
        anchoMinimo: '18rem',
    },
    {
        id: 'modulo',
        etiqueta: trans('tareas.modulo'),
        valor: (tarea) => tarea.modulo,
        filtro: {
            tipo: 'opciones',
            clave: 'modulo_id',
            opciones: props.opciones.modulos.map((modulo) => ({
                valor: String(modulo.id),
                etiqueta: modulo.ruta,
            })),
        },
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
    {
        id: 'periodo',
        etiqueta: trans('tareas.periodo'),
        valor: (tarea) => tarea.periodo,
        filtro: {
            tipo: 'opciones',
            clave: 'periodo_id',
            opciones: props.opciones.periodos.map((periodo) => ({
                valor: String(periodo.id),
                etiqueta: String(periodo.consecutivo),
            })),
        },
        anchoMinimo: '6rem',
    },
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
    <Head :title="$t('tareas.titulo')" />

    <div class="flex flex-col gap-seccion">
        <div class="flex items-start justify-between gap-bloque">
            <Heading
                variant="small"
                :title="$t('tareas.titulo')"
                :description="props.proyecto.nombre"
            />
            <div class="flex items-center gap-corto">
                <Button as-child variant="ghost" size="sm">
                    <Link :href="mostrarProyecto(props.proyecto.id)">{{
                        $t('comun.volver')
                    }}</Link>
                </Button>
                <template v-if="props.puede.crear">
                    <Button as-child variant="secondary" size="sm">
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
            </div>
        </div>

        <TablaDatos
            :url="index(props.proyecto.id).url"
            :filas="props.tareas.data"
            :columnas="columnas"
            :clave="(tarea) => tarea.id"
            :orden="props.orden"
            :filtros="props.filtros"
            :columnas-visibles="props.columnasVisibles"
            :paginacion="props.tareas"
            :etiqueta="$t('tareas.titulo')"
            :vacio="{
                titulo: $t('tareas.sin_tareas'),
                cuerpo: $t('tareas.sin_tareas_ayuda'),
            }"
            seleccionable
            :acciones-en-lote="accionesEnLote"
            @abrir="abrir"
            @en-lote="enLote"
        >
            <template #vacio-acciones>
                <Button v-if="props.puede.crear" as-child size="sm">
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
