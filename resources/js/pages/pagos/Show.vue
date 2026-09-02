<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import { Button } from '@/components/ui/button';
import { trans } from '@/lib/i18n';
import { edit } from '@/routes/pagos';
import { show as periodoShow } from '@/routes/periodos';

const props = defineProps<{
    proyecto: { id: number; nombre: string; slug: string; prefijo_tarea: string };
    pago: {
        id: number;
        periodo_id: number;
        periodo_consecutivo: number;
        fecha_pago: string;
        monto_usd: string;
        monto_cop: string;
        tasa_cambio: string;
        origen_tasa: string;
        referencia: string | null;
        notas: string | null;
    };
    puede: { editar: boolean };
}>();

const campos = [
    { clave: 'fecha_pago', valor: () => props.pago.fecha_pago },
    { clave: 'monto_usd', valor: () => `${trans('pagos.usd')} ${props.pago.monto_usd}` },
    { clave: 'monto_cop', valor: () => `${trans('pagos.cop')} ${props.pago.monto_cop}` },
    { clave: 'tasa_cambio', valor: () => props.pago.tasa_cambio },
    {
        clave: 'origen_tasa',
        valor: () => props.pago.origen_tasa,
        traducir: (v: string) => `enums.origen_tasa_cambio.${v}`,
    },
    { clave: 'referencia', valor: () => props.pago.referencia ?? '—' },
];
</script>

<template>
    <Head :title="$t('recursos.pago')" />

    <div class="flex max-w-xl flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="$t('recursos.pago')"
            :descripcion="
                $t('tareas.periodo_numero', {
                    consecutivo: String(props.pago.periodo_consecutivo),
                })
            "
        >
            <template #acciones>
                <Button as-child variant="outline" size="sm">
                    <Link :href="periodoShow(props.pago.periodo_id)">{{
                        $t('comun.volver')
                    }}</Link>
                </Button>
                <Button v-if="props.puede.editar" as-child size="sm">
                    <Link :href="edit(props.pago.id)">{{
                        $t('comun.editar')
                    }}</Link>
                </Button>
            </template>
        </CabeceraDePagina>

        <dl
            class="divide-y divide-borde rounded-md border border-borde-fuerte bg-panel"
        >
            <div
                v-for="campo in campos"
                :key="campo.clave"
                class="flex justify-between gap-bloque px-celda py-corto text-cuerpo"
            >
                <dt class="text-texto-suave">
                    {{ $t(`recursos.columnas.${campo.clave}`) }}
                </dt>
                <dd class="text-right text-texto">
                    {{
                        campo.traducir
                            ? $t(campo.traducir(campo.valor() as string))
                            : campo.valor()
                    }}
                </dd>
            </div>
            <div
                v-if="props.pago.notas"
                class="flex flex-col gap-fino px-celda py-corto text-cuerpo"
            >
                <dt class="text-texto-suave">{{ $t('pagos.notas') }}</dt>
                <dd class="text-texto">{{ props.pago.notas }}</dd>
            </div>
        </dl>
    </div>
</template>
