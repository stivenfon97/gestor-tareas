<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PagoController from '@/actions/App/Http/Controllers/PagoController';
import Heading from '@/components/Heading.vue';
import FormularioPago from '@/components/pagos/FormularioPago.vue';
import { show } from '@/routes/pagos';

const props = defineProps<{
    proyecto: {
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
    };
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
    origenesTasa: Array<{ valor: string; etiqueta: string }>;
}>();
</script>

<template>
    <Head :title="$t('pagos.editar')" />

    <div class="flex max-w-xl flex-col gap-6">
        <Heading
            variant="small"
            :title="$t('pagos.editar')"
            :description="
                $t('tareas.periodo_numero', {
                    consecutivo: String(props.pago.periodo_consecutivo),
                })
            "
        />

        <FormularioPago
            :accion="PagoController.update(props.pago.id)"
            :volver-a="show(props.pago.id).url"
            :origenes-tasa="props.origenesTasa"
            :inicial="{
                fecha_pago: props.pago.fecha_pago,
                monto_usd: props.pago.monto_usd,
                tasa_cambio: props.pago.tasa_cambio,
                origen_tasa: props.pago.origen_tasa,
                referencia: props.pago.referencia,
                notas: props.pago.notas,
            }"
        />
    </div>
</template>
