<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, toRef } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTrm } from '@/composables/useTrm';
import type { RouteDefinition } from '@/wayfinder';

/**
 * Fecha, monto, tasa y referencia de un pago. Igual para crear y editar.
 *
 * **`periodo_id` solo aparece al crear** (`props.periodos` presente): un pago
 * no cambia de periodo. La tasa se precarga por fecha con `useTrm` y **queda
 * siempre editable** — la tasa que aplicó el banco no tiene por qué ser la
 * del mercado, y la que manda es la real.
 */
const props = defineProps<{
    accion: RouteDefinition<'post'> | RouteDefinition<'put'>;
    volverA: string;
    origenesTasa: Array<{ valor: string; etiqueta: string }>;
    /** Solo al crear: periodos del proyecto que todavía no tienen pago. */
    periodos?: Array<{ id: number; consecutivo: number }>;
    periodoPreseleccionado?: number | null;
    inicial?: {
        fecha_pago: string;
        monto_usd: string;
        tasa_cambio: string;
        origen_tasa: string;
        referencia: string | null;
        notas: string | null;
    };
}>();

const esCreacion = computed(() => props.periodos !== undefined);

const form = useForm({
    periodo_id: props.periodoPreseleccionado ?? '',
    fecha_pago: props.inicial?.fecha_pago ?? '',
    monto_usd: props.inicial?.monto_usd ?? '',
    tasa_cambio: props.inicial?.tasa_cambio ?? '',
    origen_tasa: props.inicial?.origen_tasa ?? 'manual',
    referencia: props.inicial?.referencia ?? '',
    notas: props.inicial?.notas ?? '',
});

useTrm(
    toRef(form, 'fecha_pago'),
    toRef(form, 'tasa_cambio'),
    toRef(form, 'origen_tasa'),
);

const enviar = (): void => {
    form.submit(props.accion);
};
</script>

<template>
    <form class="space-y-6" @submit.prevent="enviar">
        <div v-if="esCreacion" class="grid gap-2">
            <Label for="periodo_id">{{ $t('pagos.periodo') }}</Label>
            <select
                id="periodo_id"
                v-model="form.periodo_id"
                required
                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
            >
                <option value="" disabled>
                    {{ $t('pagos.elegir_periodo') }}
                </option>
                <option
                    v-for="periodo in props.periodos"
                    :key="periodo.id"
                    :value="periodo.id"
                >
                    {{
                        $t('tareas.periodo_numero', {
                            consecutivo: String(periodo.consecutivo),
                        })
                    }}
                </option>
            </select>
            <p
                v-if="props.periodos && props.periodos.length === 0"
                class="text-fino text-texto-suave"
            >
                {{ $t('pagos.sin_periodos_disponibles') }}
            </p>
            <InputError :message="form.errors.periodo_id" />
        </div>

        <div class="grid gap-2">
            <Label for="fecha_pago">{{ $t('pagos.fecha_pago') }}</Label>
            <Input
                id="fecha_pago"
                v-model="form.fecha_pago"
                type="date"
                required
            />
            <p class="text-fino text-texto-suave">
                {{ $t('pagos.fecha_pago_ayuda') }}
            </p>
            <InputError :message="form.errors.fecha_pago" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="monto_usd">{{ $t('pagos.monto_usd') }}</Label>
                <Input
                    id="monto_usd"
                    v-model="form.monto_usd"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                />
                <InputError :message="form.errors.monto_usd" />
            </div>

            <div class="grid gap-2">
                <Label for="tasa_cambio">{{ $t('pagos.tasa_cambio') }}</Label>
                <Input
                    id="tasa_cambio"
                    v-model="form.tasa_cambio"
                    type="number"
                    step="0.0001"
                    min="0"
                    required
                />
                <InputError :message="form.errors.tasa_cambio" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="origen_tasa">{{ $t('pagos.origen_tasa') }}</Label>
            <select
                id="origen_tasa"
                v-model="form.origen_tasa"
                required
                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
            >
                <option
                    v-for="opcion in props.origenesTasa"
                    :key="opcion.valor"
                    :value="opcion.valor"
                >
                    {{ opcion.etiqueta }}
                </option>
            </select>
            <p class="text-fino text-texto-suave">
                {{ $t('pagos.origen_tasa_ayuda') }}
            </p>
            <InputError :message="form.errors.origen_tasa" />
        </div>

        <div class="grid gap-2">
            <Label for="referencia">{{ $t('pagos.referencia') }}</Label>
            <Input id="referencia" v-model="form.referencia" />
            <InputError :message="form.errors.referencia" />
        </div>

        <div class="grid gap-2">
            <Label for="notas">{{ $t('pagos.notas') }}</Label>
            <textarea
                id="notas"
                v-model="form.notas"
                rows="3"
                class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
            />
            <InputError :message="form.errors.notas" />
        </div>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">{{
                $t('comun.guardar')
            }}</Button>
            <Button as-child variant="ghost">
                <Link :href="props.volverA">{{ $t('comun.cancelar') }}</Link>
            </Button>
        </div>
    </form>
</template>
