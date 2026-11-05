<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Calendar,
    CalendarClock,
    CalendarRange,
    Eye,
    FileText,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import BotonDeAccion from '@/components/BotonDeAccion.vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import InputError from '@/components/InputError.vue';
import { TablaDatos } from '@/components/TablaDatos';
import type { ColumnaDeTabla, OrdenDeColumna } from '@/components/TablaDatos';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
    InputGroupTextarea,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { formatearFecha, formatearMonto } from '@/lib/formato';
import { trans } from '@/lib/i18n';
import { tonoDeEstadoPeriodo } from '@/lib/tonos';
import { show as mostrarPeriodo } from '@/routes/periodos';
import { show as mostrarProyecto } from '@/routes/proyectos';
import { index, store } from '@/routes/proyectos/periodos';
import type { RespuestaPaginada } from '@/types/paginacion';

type FilaPeriodo = {
    id: number;
    consecutivo: number;
    fecha_inicio: string;
    fecha_fin: string | null;
    estado: string;
    vencido: boolean;
    tareas_count: number;
    pago: {
        monto_usd: string;
        monto_cop: string;
        fecha_pago: string;
    } | null;
};

const props = defineProps<{
    proyecto: {
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
    };
    periodos: RespuestaPaginada<FilaPeriodo>;
    filtros: Record<string, string>;
    orden: OrdenDeColumna[];
    columnasVisibles: string[] | null;
    estados: Array<{ valor: string; etiqueta: string }>;
    puede: { gestionar: boolean; verPagos: boolean };
    periodoAbierto: { id: number; consecutivo: number } | null;
}>();

const columnas = computed((): ColumnaDeTabla<FilaPeriodo>[] => [
    {
        id: 'consecutivo',
        etiqueta: trans('recursos.columnas.consecutivo'),
        valor: (fila) => fila.consecutivo,
        ordenPor: 'consecutivo',
        fija: true,
        principal: true,
        anchoMinimo: '9rem',
    },
    {
        id: 'fecha_inicio',
        etiqueta: trans('recursos.columnas.fecha_inicio'),
        valor: (fila) => formatearFecha(fila.fecha_inicio),
        ordenPor: 'fecha_inicio',
        mono: true,
        prioritaria: true,
    },
    {
        id: 'fecha_fin',
        etiqueta: trans('periodos.fecha_fin'),
        valor: (fila) =>
            fila.fecha_fin ? formatearFecha(fila.fecha_fin) : '—',
        ordenPor: 'fecha_fin',
        mono: true,
    },
    {
        id: 'estado',
        etiqueta: trans('recursos.columnas.estado'),
        valor: (fila) => fila.estado,
        filtro: {
            tipo: 'opciones',
            clave: 'estado',
            opciones: props.estados.map((opcion) => ({
                valor: opcion.valor,
                etiqueta: trans(opcion.etiqueta),
            })),
        },
        ajustada: true,
        prioritaria: true,
    },
    {
        id: 'tareas',
        etiqueta: trans('proyectos.secciones.tareas'),
        valor: (fila) => fila.tareas_count,
        ordenPor: 'tareas',
        alineacion: 'derecha',
        mono: true,
        ajustada: true,
    },
    ...(props.puede.verPagos
        ? [
              {
                  id: 'pago',
                  etiqueta: trans('recursos.pago'),
                  alineacion: 'derecha' as const,
                  mono: true,
                  prioritaria: true,
                  anchoMinimo: '8rem',
              },
          ]
        : []),
    {
        id: 'acciones',
        etiqueta: trans('comun.acciones'),
        alineacion: 'derecha',
        ajustada: true,
        prioritaria: true,
    },
]);

const abierto = ref(false);
const fechaInicio = ref(new Date().toISOString().slice(0, 10));
const notas = ref('');
const errores = ref<{ fecha_inicio?: string; notas?: string }>({});

const abrirPeriodo = (): void => {
    router.post(
        store(props.proyecto.id).url,
        { fecha_inicio: fechaInicio.value, notas: notas.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                abierto.value = false;
                notas.value = '';
            },
            onError: (nuevos) => {
                errores.value = nuevos;
            },
        },
    );
};
</script>

<template>
    <Head :title="$t('recursos.periodos')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :icono="CalendarRange"
            :titulo="$t('recursos.periodos')"
            :descripcion="props.proyecto.nombre"
        >
            <template #acciones>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="mostrarProyecto(props.proyecto.id)">{{
                        $t('comun.volver')
                    }}</Link>
                </Button>

                <Popover v-if="props.puede.gestionar" v-model:open="abierto">
                    <PopoverTrigger as-child>
                        <Button
                            size="sm"
                            :disabled="props.periodoAbierto !== null"
                            :title="
                                props.periodoAbierto
                                    ? $t('periodos.ya_hay_abierto_ayuda', {
                                          consecutivo: String(
                                              props.periodoAbierto.consecutivo,
                                          ),
                                      })
                                    : undefined
                            "
                        >
                            {{ $t('periodos.abrir') }}
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent
                        align="end"
                        class="flex w-80 flex-col gap-corto"
                    >
                        <p class="text-fino text-texto-suave">
                            {{ $t('periodos.abrir_ayuda') }}
                        </p>

                        <div class="grid gap-fino">
                            <Label
                                for="periodo-fecha-inicio"
                                class="text-micro text-texto-suave"
                            >
                                {{ $t('recursos.columnas.fecha_inicio') }}
                            </Label>
                            <InputGroup>
                                <InputGroupInput
                                    id="periodo-fecha-inicio"
                                    v-model="fechaInicio"
                                    type="date"
                                    :aria-invalid="!!errores.fecha_inicio"
                                />
                                <InputGroupAddon>
                                    <Calendar aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>
                            <InputError :message="errores.fecha_inicio" />
                        </div>

                        <div class="grid gap-fino">
                            <Label
                                for="periodo-notas"
                                class="text-micro text-texto-suave"
                            >
                                {{ $t('periodos.notas') }}
                            </Label>
                            <InputGroup>
                                <InputGroupTextarea
                                    id="periodo-notas"
                                    v-model="notas"
                                    rows="2"
                                    :placeholder="$t('periodos.notas_marcador')"
                                    :aria-invalid="!!errores.notas"
                                />
                                <InputGroupAddon align="block-start">
                                    <FileText aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>
                            <InputError :message="errores.notas" />
                        </div>

                        <div class="flex justify-end">
                            <Button size="sm" @click="abrirPeriodo">
                                {{ $t('periodos.abrir') }}
                            </Button>
                        </div>
                    </PopoverContent>
                </Popover>
            </template>
        </CabeceraDePagina>

        <TablaDatos
            :url="index(props.proyecto.id).url"
            :filas="props.periodos.data"
            :columnas="columnas"
            :clave="(fila) => fila.id"
            :orden="props.orden"
            :filtros="props.filtros"
            :columnas-visibles="props.columnasVisibles"
            :paginacion="props.periodos"
            :etiqueta="$t('recursos.periodos')"
            :busqueda="{ clave: 'buscar', marcador: $t('periodos.buscar') }"
            :vacio="{
                titulo: $t('periodos.sin_periodos'),
                cuerpo: $t('periodos.sin_periodos_ayuda'),
            }"
            @abrir="(fila) => router.visit(mostrarPeriodo(fila.id).url)"
        >
            <template #celda-consecutivo="{ fila }">
                <Link
                    :href="mostrarPeriodo(fila.id)"
                    class="font-medium underline-offset-4 hover:underline"
                >
                    {{
                        $t('tareas.periodo_numero', {
                            consecutivo: String(fila.consecutivo),
                        })
                    }}
                </Link>
            </template>

            <template #celda-estado="{ fila }">
                <div class="flex items-center gap-fino">
                    <Badge :variant="tonoDeEstadoPeriodo(fila.estado)">
                        <span
                            class="size-1.5 rounded-full bg-current"
                            aria-hidden="true"
                        />
                        {{ $t(`enums.estado_periodo.${fila.estado}`) }}
                    </Badge>
                    <Badge v-if="fila.vencido" variant="aviso">
                        <CalendarClock aria-hidden="true" />
                        {{ $t('periodos.vencido') }}
                    </Badge>
                </div>
            </template>

            <template #celda-pago="{ fila }">
                <span v-if="fila.pago" class="text-texto">{{
                    formatearMonto(fila.pago.monto_usd, 'USD')
                }}</span>
                <Badge v-else variant="neutro">{{
                    $t('periodos.sin_pago')
                }}</Badge>
            </template>

            <template #celda-acciones="{ fila }">
                <div class="flex items-center justify-end">
                    <BotonDeAccion
                        :href="mostrarPeriodo(fila.id)"
                        :icono="Eye"
                        :etiqueta="$t('periodos.ver')"
                    />
                </div>
            </template>
        </TablaDatos>
    </div>
</template>
