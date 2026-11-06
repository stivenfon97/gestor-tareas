<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ExternalLink, Eye, History } from '@lucide/vue';
import { computed, ref } from 'vue';
import BotonDeAccion from '@/components/BotonDeAccion.vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import EntradaDeHistorial from '@/components/historial/EntradaDeHistorial.vue';
import { iconoDeTipoDeRegistro } from '@/components/historial/iconos';
import type { EntradaDeHistorial as Entrada } from '@/components/historial/tipos';
import { TablaDatos } from '@/components/TablaDatos';
import type { ColumnaDeTabla, OrdenDeColumna } from '@/components/TablaDatos';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatearFechaHora } from '@/lib/formato';
import { trans, transChoice } from '@/lib/i18n';
import { tonoDeEventoHistorial, tonoDeTipoDeRegistro } from '@/lib/tonos';
import { index } from '@/routes/historial';
import type { RespuestaPaginada } from '@/types/paginacion';

type Fila = Entrada & { registro: NonNullable<Entrada['registro']> };

const props = defineProps<{
    historial: RespuestaPaginada<Fila>;
    filtros: Record<string, string>;
    orden: OrdenDeColumna[];
    columnasVisibles: string[] | null;
    tipos: string[];
    eventos: string[];
    usuarios: Array<{ id: number; name: string }>;
}>();

const columnas = computed((): ColumnaDeTabla<Fila>[] => [
    {
        id: 'registro',
        etiqueta: trans('historial.columnas.registro'),
        valor: (fila) => fila.registro.etiqueta,
        fija: true,
        anclada: true,
        principal: true,
        anchoMinimo: '16rem',
    },
    {
        id: 'tipo',
        etiqueta: trans('historial.columnas.tipo'),
        valor: (fila) => fila.registro.tipo,
        ajustada: true,
        prioritaria: true,
        filtro: {
            tipo: 'opciones',
            clave: 'tipo',
            opciones: props.tipos.map((tipo) => ({
                valor: tipo,
                etiqueta: trans(`historial.tipos.${tipo}`),
            })),
        },
    },
    {
        id: 'evento',
        etiqueta: trans('historial.columnas.evento'),
        valor: (fila) => fila.evento,
        ajustada: true,
        prioritaria: true,
        filtro: {
            tipo: 'opciones',
            clave: 'evento',
            opciones: props.eventos.map((evento) => ({
                valor: evento,
                etiqueta: trans(`enums.evento_historial.${evento}`),
            })),
        },
    },
    {
        id: 'cambios',
        etiqueta: trans('historial.columnas.cambios'),
        valor: (fila) => fila.cambios.length,
        anchoMinimo: '14rem',
    },
    {
        id: 'usuario',
        etiqueta: trans('historial.columnas.usuario'),
        valor: (fila) => fila.usuario?.name ?? null,
        prioritaria: true,
        anchoMinimo: '10rem',
        filtro: {
            tipo: 'opciones',
            clave: 'usuario',
            opciones: props.usuarios.map((usuario) => ({
                valor: String(usuario.id),
                etiqueta: usuario.name,
            })),
        },
    },
    {
        id: 'fecha',
        etiqueta: trans('historial.columnas.fecha'),
        valor: (fila) => fila.fecha,
        ordenPor: 'created_at',
        mono: true,
        ajustada: true,
        prioritaria: true,
    },
    {
        id: 'acciones',
        etiqueta: trans('comun.acciones'),
        alineacion: 'derecha',
        ajustada: true,
        prioritaria: true,
    },
]);

/** Cuántos campos se nombran en la celda antes de resumir el resto. */
const CAMPOS_VISIBLES = 3;

const nombreDeCampo = (cambio: Entrada['cambios'][number]): string =>
    cambio.etiqueta ? trans(cambio.etiqueta) : cambio.campo;

const enDetalle = ref<Fila | null>(null);

const abrir = (fila: Fila): void => {
    enDetalle.value = fila;
};

const irAlRegistro = (fila: Fila): void => {
    if (fila.registro.url) {
        router.visit(fila.registro.url);
    }
};
</script>

<template>
    <Head :title="$t('historial.titulo')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :icono="History"
            :titulo="$t('historial.titulo')"
            :descripcion="$t('historial.descripcion')"
        />

        <TablaDatos
            :url="index().url"
            :filas="props.historial.data"
            :columnas="columnas"
            :clave="(fila) => fila.id"
            :orden="props.orden"
            :filtros="props.filtros"
            :columnas-visibles="props.columnasVisibles"
            :paginacion="props.historial"
            :etiqueta="$t('historial.titulo')"
            :busqueda="{ clave: 'buscar', marcador: $t('historial.buscar') }"
            :vacio="{
                titulo: $t('historial.sin_cambios'),
                cuerpo: $t('historial.sin_cambios_ayuda'),
            }"
            @abrir="abrir"
        >
            <template #celda-registro="{ fila }">
                <Link
                    v-if="fila.registro.url"
                    :href="fila.registro.url"
                    class="underline-offset-4 hover:underline"
                >
                    {{ fila.registro.etiqueta }}
                </Link>
                <span v-else class="text-texto-suave">
                    {{
                        fila.registro.etiqueta ??
                        `${$t('historial.registro_eliminado')} #${fila.registro.id}`
                    }}
                </span>
            </template>

            <template #celda-tipo="{ fila }">
                <Badge :variant="tonoDeTipoDeRegistro(fila.registro.tipo)">
                    <component
                        :is="iconoDeTipoDeRegistro(fila.registro.tipo)"
                        aria-hidden="true"
                    />
                    {{ $t(`historial.tipos.${fila.registro.tipo}`) }}
                </Badge>
            </template>

            <template #celda-evento="{ fila }">
                <Badge :variant="tonoDeEventoHistorial(fila.evento)">
                    <span
                        class="size-1.5 rounded-full bg-current"
                        aria-hidden="true"
                    />
                    {{ $t(`enums.evento_historial.${fila.evento}`) }}
                </Badge>
            </template>

            <template #celda-cambios="{ fila }">
                <span v-if="fila.cambios.length === 0" class="text-texto-tenue"
                    >—</span
                >
                <span v-else class="text-texto-suave">
                    {{
                        fila.cambios
                            .slice(0, CAMPOS_VISIBLES)
                            .map(nombreDeCampo)
                            .join(', ')
                    }}<template v-if="fila.cambios.length > CAMPOS_VISIBLES">
                        ·
                        {{
                            transChoice(
                                'historial.campos_modificados',
                                fila.cambios.length,
                                { cantidad: String(fila.cambios.length) },
                            )
                        }}</template
                    >
                </span>
            </template>

            <template #celda-usuario="{ fila }">
                <span v-if="fila.usuario">{{ fila.usuario.name }}</span>
                <span v-else class="text-texto-tenue">{{
                    $t('historial.sistema')
                }}</span>
            </template>

            <template #celda-fecha="{ fila }">
                <time
                    v-if="fila.fecha"
                    :datetime="fila.fecha"
                    class="whitespace-nowrap"
                    >{{ formatearFechaHora(fila.fecha) }}</time
                >
            </template>

            <template #celda-acciones="{ fila }">
                <div class="flex items-center justify-end gap-minimo">
                    <BotonDeAccion
                        :icono="Eye"
                        :etiqueta="$t('historial.ver_detalle')"
                        @click="abrir(fila)"
                    />
                    <BotonDeAccion
                        v-if="fila.registro.url"
                        :icono="ExternalLink"
                        tono="info"
                        :etiqueta="$t('historial.ir_al_registro')"
                        @click="irAlRegistro(fila)"
                    />
                </div>
            </template>
        </TablaDatos>

        <Dialog
            :open="enDetalle !== null"
            @update:open="(abierto) => !abierto && (enDetalle = null)"
        >
            <DialogContent class="max-h-[85dvh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{
                        $t('historial.detalle_titulo')
                    }}</DialogTitle>
                    <DialogDescription v-if="enDetalle">
                        {{ $t(`historial.tipos.${enDetalle.registro.tipo}`) }}
                        ·
                        {{
                            enDetalle.registro.etiqueta ??
                            `${$t('historial.registro_eliminado')} #${enDetalle.registro.id}`
                        }}
                    </DialogDescription>
                </DialogHeader>
                <EntradaDeHistorial v-if="enDetalle" :entrada="enDetalle" />
            </DialogContent>
        </Dialog>
    </div>
</template>
