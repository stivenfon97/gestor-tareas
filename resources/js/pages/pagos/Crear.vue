<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PagoController from '@/actions/App/Http/Controllers/PagoController';
import Heading from '@/components/Heading.vue';
import FormularioPago from '@/components/pagos/FormularioPago.vue';
import { index as pagosIndex } from '@/routes/proyectos/pagos';

const props = defineProps<{
    proyecto: { id: number; nombre: string; slug: string; prefijo_tarea: string };
    periodos: Array<{ id: number; consecutivo: number; estado: string }>;
    periodoPreseleccionado: number | null;
    origenesTasa: Array<{ valor: string; etiqueta: string }>;
}>();
</script>

<template>
    <Head :title="$t('pagos.registrar')" />

    <div class="flex max-w-xl flex-col gap-6">
        <Heading
            variant="small"
            :title="$t('pagos.registrar')"
            :description="props.proyecto.nombre"
        />

        <FormularioPago
            :accion="PagoController.store(props.proyecto.id)"
            :volver-a="pagosIndex(props.proyecto.id).url"
            :origenes-tasa="props.origenesTasa"
            :periodos="props.periodos"
            :periodo-preseleccionado="props.periodoPreseleccionado"
        />
    </div>
</template>
