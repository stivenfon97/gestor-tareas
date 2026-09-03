<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Send, ShieldAlert } from '@lucide/vue';
import { computed, ref } from 'vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import EstadoVacio from '@/components/EstadoVacio.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { show as mostrarProyecto } from '@/routes/proyectos';
import { store } from '@/routes/proyectos/reportes';
import { descargar } from '@/routes/reportes';

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
};

const props = defineProps<{
    proyecto: {
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
    };
    periodos: Periodo[];
    reportes: FilaHistorial[];
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

/** Dos filas con el mismo hash: se regeneró exactamente lo mismo. */
const conteoPorHash = computed<Map<string, number>>(() => {
    const conteo = new Map<string, number>();

    for (const reporte of props.reportes) {
        conteo.set(reporte.hash, (conteo.get(reporte.hash) ?? 0) + 1);
    }

    return conteo;
});

const esDuplicado = (reporte: FilaHistorial): boolean =>
    (conteoPorHash.value.get(reporte.hash) ?? 0) > 1;
</script>

<template>
    <Head :title="$t('reportes.titulo')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="$t('reportes.titulo')"
            :descripcion="props.proyecto.nombre"
        >
            <template #acciones>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="mostrarProyecto(props.proyecto.id)">{{
                        $t('comun.volver')
                    }}</Link>
                </Button>
            </template>
        </CabeceraDePagina>

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
                <label
                    for="reporte-periodo"
                    class="text-micro text-texto-suave"
                >
                    {{ $t('reportes.generar.periodo') }}
                </label>
                <Select v-model="periodoElegidoId">
                    <SelectTrigger id="reporte-periodo">
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

        <EstadoVacio
            v-if="props.periodos.length > 0 && props.reportes.length === 0"
            :titulo="$t('reportes.historial.vacio')"
            :cuerpo="$t('reportes.historial.vacio_ayuda')"
        />

        <div
            v-else-if="props.reportes.length > 0"
            class="overflow-x-auto rounded-md border border-borde-fuerte bg-panel"
        >
            <table class="w-full min-w-3xl text-left text-cuerpo">
                <thead class="bg-panel-cabecera text-texto-suave">
                    <tr>
                        <th class="px-celda py-corto font-medium">
                            {{ $t('reportes.historial.fecha') }}
                        </th>
                        <th class="px-celda py-corto font-medium">
                            {{ $t('reportes.historial.tipo') }}
                        </th>
                        <th class="px-celda py-corto font-medium">
                            {{ $t('reportes.historial.periodo') }}
                        </th>
                        <th class="px-celda py-corto font-medium">
                            {{ $t('reportes.historial.generado_por') }}
                        </th>
                        <th class="px-celda py-corto text-right font-medium">
                            {{ $t('reportes.historial.total_tareas') }}
                        </th>
                        <th class="px-celda py-corto font-medium">
                            {{ $t('reportes.historial.hash') }}
                        </th>
                        <th class="px-celda py-corto font-medium">
                            {{ $t('comun.acciones') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="reporte in props.reportes"
                        :key="reporte.id"
                        class="border-t border-borde hover:bg-fila-hover"
                    >
                        <td
                            class="px-celda py-corto font-mono text-texto-suave"
                        >
                            {{ reporte.generado_en }}
                        </td>
                        <td class="px-celda py-corto">
                            <Badge
                                :variant="
                                    reporte.tipo === 'completo'
                                        ? 'outline'
                                        : 'secondary'
                                "
                            >
                                {{ $t(`enums.tipo_reporte.${reporte.tipo}`) }}
                            </Badge>
                        </td>
                        <td
                            class="px-celda py-corto font-mono text-texto-suave"
                        >
                            {{ reporte.periodo_consecutivo }}
                        </td>
                        <td class="px-celda py-corto">
                            {{ reporte.generado_por }}
                        </td>
                        <td
                            class="px-celda py-corto text-right font-mono text-texto-suave"
                        >
                            {{ reporte.total_tareas }}
                        </td>
                        <td class="px-celda py-corto">
                            <div class="flex items-center gap-fino">
                                <span
                                    class="font-mono text-fino text-texto-suave"
                                    >{{ reporte.hash.slice(0, 10) }}</span
                                >
                                <Badge
                                    v-if="esDuplicado(reporte)"
                                    variant="outline"
                                    :title="$t('reportes.historial.duplicado')"
                                >
                                    {{ $t('reportes.historial.duplicado') }}
                                </Badge>
                            </div>
                        </td>
                        <td class="px-celda py-corto">
                            <Button as-child size="sm" variant="outline">
                                <a :href="descargar(reporte.id).url">{{
                                    $t('reportes.historial.descargar_historico')
                                }}</a>
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
