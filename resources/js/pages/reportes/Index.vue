<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Copy,
    Download,
    FileSpreadsheet,
    Send,
    ShieldAlert,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import BotonDeAccion from '@/components/BotonDeAccion.vue';
import EncabezadoDeSeccion from '@/components/EncabezadoDeSeccion.vue';
import EstadoVacio from '@/components/EstadoVacio.vue';
import InputError from '@/components/InputError.vue';
import { TablaDatos } from '@/components/TablaDatos';
import type { ColumnaDeTabla, OrdenDeColumna } from '@/components/TablaDatos';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatearFechaHora } from '@/lib/formato';
import { trans } from '@/lib/i18n';
import { tonoDeTipoReporte } from '@/lib/tonos';
import { index, store } from '@/routes/proyectos/reportes';
import { descargar } from '@/routes/reportes';
import type { RespuestaPaginada } from '@/types/paginacion';

type Periodo = {
    id: number;
    consecutivo: number;
    fecha_inicio: string;
    fecha_fin: string | null;
    total_tareas: number;
    por_estado: Record<string, number>;
};

type TipoReporte = 'completo' | 'cliente';

type FilaHistorial = {
    id: number;
    tipo: TipoReporte;
    periodo_consecutivo: number;
    nombre_archivo: string;
    total_tareas: number;
    resumen_estados: Record<string, number>;
    hash: string;
    generado_por: string;
    generado_en: string;
    duplicado: boolean;
};

const props = defineProps<{
    proyecto: {
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
    };
    periodos: Periodo[];
    reportes: RespuestaPaginada<FilaHistorial>;
    filtros: Record<string, string>;
    orden: OrdenDeColumna[];
    columnasVisibles: string[] | null;
    tipos: Array<{ valor: string; etiqueta: string }>;
    puede: { generar: boolean };
}>();

/*
 * Vista previa del conteo antes de descargar: ambos reportes comparten el
 * mismo conjunto de tareas -solo cambian las columnas, nunca las filas-, así
 * que un único periodo elegido basta para previsualizar los dos.
 */
const periodoElegidoId = ref<string>(
    props.periodos[0] ? String(props.periodos[0].id) : '',
);

const periodoElegido = computed<Periodo | null>(
    () =>
        props.periodos.find(
            (periodo) => String(periodo.id) === periodoElegidoId.value,
        ) ?? null,
);

const formulario = useForm<{ periodo_id: string; tipo: TipoReporte }>({
    periodo_id: '',
    tipo: 'cliente',
});

/*
 * Un único camino para «generar y descargar»: el POST solo escribe la fila
 * -refresca el historial al volver-, y el flash trae el id recién creado
 * para pedir su descarga por la misma ruta que la histórica. No hay una
 * generación de archivo distinta para el primer clic.
 */
const generar = (tipo: TipoReporte): void => {
    if (periodoElegido.value === null) {
        return;
    }

    formulario.periodo_id = String(periodoElegido.value.id);
    formulario.tipo = tipo;

    formulario.post(store(props.proyecto.id).url, {
        preserveScroll: true,
        onFlash: (flash) => {
            const id = flash.reporte_generado_id as number | undefined;

            if (id !== undefined) {
                window.location.href = descargar(id).url;
            }
        },
    });
};

const columnas = computed((): ColumnaDeTabla<FilaHistorial>[] => [
    {
        id: 'generado_en',
        etiqueta: trans('reportes.historial.fecha'),
        valor: (fila) => formatearFechaHora(fila.generado_en),
        ordenPor: 'generado_en',
        fija: true,
        principal: true,
        mono: true,
        anchoMinimo: '11rem',
    },
    {
        id: 'tipo',
        etiqueta: trans('reportes.historial.tipo'),
        valor: (fila) => fila.tipo,
        filtro: {
            tipo: 'opciones',
            clave: 'tipo',
            opciones: props.tipos.map((opcion) => ({
                valor: opcion.valor,
                etiqueta: trans(opcion.etiqueta),
            })),
        },
        ajustada: true,
        prioritaria: true,
    },
    {
        id: 'periodo',
        etiqueta: trans('reportes.historial.periodo'),
        valor: (fila) => fila.periodo_consecutivo,
        filtro: {
            tipo: 'opciones',
            clave: 'periodo_id',
            opciones: props.periodos.map((periodo) => ({
                valor: String(periodo.id),
                etiqueta: trans('tareas.periodo_numero', {
                    consecutivo: String(periodo.consecutivo),
                }),
            })),
        },
        mono: true,
        prioritaria: true,
    },
    {
        id: 'generado_por',
        etiqueta: trans('reportes.historial.generado_por'),
        valor: (fila) => fila.generado_por,
        anchoMinimo: '10rem',
    },
    {
        id: 'total_tareas',
        etiqueta: trans('reportes.historial.total_tareas'),
        valor: (fila) => fila.total_tareas,
        ordenPor: 'total_tareas',
        alineacion: 'derecha',
        mono: true,
        ajustada: true,
    },
    {
        id: 'hash',
        etiqueta: trans('reportes.historial.hash'),
        valor: (fila) => fila.hash.slice(0, 10),
        mono: true,
    },
    {
        id: 'acciones',
        etiqueta: trans('comun.acciones'),
        alineacion: 'derecha',
        ajustada: true,
        prioritaria: true,
    },
]);
</script>

<template>
    <Head :title="$t('reportes.titulo')" />

    <div class="flex flex-col gap-seccion">
        <EncabezadoDeSeccion
            :icono="FileSpreadsheet"
            :titulo="$t('reportes.titulo')"
        >
        </EncabezadoDeSeccion>

        <EstadoVacio
            v-if="props.periodos.length === 0"
            :titulo="$t('reportes.sin_periodos')"
            :cuerpo="$t('reportes.sin_periodos_ayuda')"
        />

        <!--
            Generar. Los dos botones no pueden parecerse ni estar solo uno al
            lado del otro sin distinción: el completo lleva los pagos y es el
            único sitio de la aplicación donde un clic equivocado manda
            dinero a un tercero. Se diferencian por icono, por texto de
            ayuda explícito y por escalón de botón -primario el de cliente,
            que es la acción de todos los días; contorno el completo-, nunca
            solo por color.
        -->
        <section
            v-if="props.puede.generar && props.periodos.length > 0"
            class="flex flex-col gap-corto rounded-md border border-borde-fuerte bg-panel p-bloque"
        >
            <h2 class="text-cuerpo font-medium text-texto">
                {{ $t('reportes.generar.titulo') }}
            </h2>

            <div class="grid gap-fino sm:max-w-xs">
                <Label
                    for="reporte-periodo"
                    class="text-micro text-texto-suave"
                >
                    {{ $t('reportes.generar.periodo') }}
                </Label>
                <Select v-model="periodoElegidoId">
                    <SelectTrigger
                        id="reporte-periodo"
                        class="w-full"
                        :aria-invalid="!!formulario.errors.periodo_id"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="periodo in props.periodos"
                            :key="periodo.id"
                            :value="String(periodo.id)"
                        >
                            {{
                                $t('reportes.generar.periodo_numero', {
                                    consecutivo: String(periodo.consecutivo),
                                })
                            }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="formulario.errors.periodo_id" />
            </div>

            <p v-if="periodoElegido" class="text-fino text-texto-suave">
                {{
                    $t('reportes.generar.preview', {
                        total: String(periodoElegido.total_tareas),
                    })
                }}
            </p>

            <div class="grid gap-corto sm:grid-cols-2">
                <div
                    class="flex flex-col gap-fino rounded-md border border-borde p-celda"
                >
                    <div class="flex items-center gap-fino text-texto">
                        <Send
                            class="size-4 text-texto-suave"
                            aria-hidden="true"
                        />
                        <span class="font-medium">{{
                            $t('enums.tipo_reporte.cliente')
                        }}</span>
                    </div>
                    <p class="text-fino text-texto-suave">
                        {{ $t('reportes.generar.cliente_ayuda') }}
                    </p>
                    <Button
                        size="sm"
                        class="self-start"
                        :disabled="!periodoElegido || formulario.processing"
                        @click="generar('cliente')"
                    >
                        {{ $t('reportes.generar.boton') }}
                    </Button>
                </div>

                <div
                    class="flex flex-col gap-fino rounded-md border border-dashed border-borde-fuerte p-celda"
                >
                    <div class="flex items-center gap-fino text-texto">
                        <ShieldAlert
                            class="size-4 text-texto-suave"
                            aria-hidden="true"
                        />
                        <span class="font-medium">{{
                            $t('enums.tipo_reporte.completo')
                        }}</span>
                    </div>
                    <p class="text-fino text-texto-suave">
                        {{ $t('reportes.generar.completo_ayuda') }}
                    </p>
                    <Button
                        size="sm"
                        variant="outline"
                        class="self-start"
                        :disabled="!periodoElegido || formulario.processing"
                        @click="generar('completo')"
                    >
                        {{ $t('reportes.generar.boton') }}
                    </Button>
                </div>
            </div>
        </section>

        <TablaDatos
            v-if="props.periodos.length > 0"
            :url="index(props.proyecto.id).url"
            :filas="props.reportes.data"
            :columnas="columnas"
            :clave="(fila) => fila.id"
            :orden="props.orden"
            :filtros="props.filtros"
            :columnas-visibles="props.columnasVisibles"
            :paginacion="props.reportes"
            :etiqueta="$t('reportes.historial.titulo')"
            :busqueda="{
                clave: 'buscar',
                marcador: $t('reportes.historial.buscar'),
            }"
            :vacio="{
                titulo: $t('reportes.historial.vacio'),
                cuerpo: $t('reportes.historial.vacio_ayuda'),
            }"
        >
            <template #celda-tipo="{ fila }">
                <Badge :variant="tonoDeTipoReporte(fila.tipo)">
                    <FileSpreadsheet aria-hidden="true" />
                    {{ $t(`enums.tipo_reporte.${fila.tipo}`) }}
                </Badge>
            </template>

            <template #celda-periodo="{ fila }">
                {{
                    $t('tareas.periodo_numero', {
                        consecutivo: String(fila.periodo_consecutivo),
                    })
                }}
            </template>

            <template #celda-hash="{ fila }">
                <div class="flex items-center gap-fino">
                    <span class="font-mono text-fino text-texto-suave">{{
                        fila.hash.slice(0, 10)
                    }}</span>
                    <Badge
                        v-if="fila.duplicado"
                        variant="aviso"
                        :title="$t('reportes.historial.duplicado')"
                    >
                        <Copy aria-hidden="true" />
                        {{ $t('reportes.historial.duplicado') }}
                    </Badge>
                </div>
            </template>

            <template #celda-acciones="{ fila }">
                <div class="flex items-center justify-end">
                    <BotonDeAccion
                        :href="descargar(fila.id).url"
                        nativo
                        :icono="Download"
                        tono="info"
                        :etiqueta="$t('reportes.historial.descargar_historico')"
                    />
                </div>
            </template>
        </TablaDatos>
    </div>
</template>
