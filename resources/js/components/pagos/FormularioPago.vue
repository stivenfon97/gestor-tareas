<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    Calendar,
    CalendarRange,
    DollarSign,
    FileText,
    Hash,
} from '@lucide/vue';
import { computed, toRef } from 'vue';
import PieDeFormulario from '@/components/formularios/PieDeFormulario.vue';
import SeccionDeFormulario from '@/components/formularios/SeccionDeFormulario.vue';
import TarjetaDeFormulario from '@/components/formularios/TarjetaDeFormulario.vue';
import InputError from '@/components/InputError.vue';
import SelectorConBusqueda from '@/components/SelectorConBusqueda.vue';
import { Field } from '@/components/ui/field';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
    InputGroupTextarea,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
})
    .withPrecognition(props.accion)
    .setValidationTimeout(500);

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
    <form novalidate @submit.prevent="enviar">
        <TarjetaDeFormulario>
            <SeccionDeFormulario
                :titulo="$t('pagos.formulario.periodo')"
                :descripcion="$t('pagos.formulario.periodo_ayuda')"
            >
                <div class="grid gap-bloque md:grid-cols-2">
                    <Field v-if="esCreacion">
                        <Label for="periodo_id">{{
                            $t('pagos.periodo')
                        }}</Label>
                        <SelectorConBusqueda
                            id="periodo_id"
                            :model-value="
                                form.periodo_id === '' ? null : form.periodo_id
                            "
                            :opciones="
                                (props.periodos ?? []).map((periodo) => ({
                                    valor: periodo.id,
                                    etiqueta: $t('tareas.periodo_numero', {
                                        consecutivo: String(
                                            periodo.consecutivo,
                                        ),
                                    }),
                                }))
                            "
                            :marcador="$t('pagos.elegir_periodo')"
                            :icono="CalendarRange"
                            :invalido="!!form.errors.periodo_id"
                            @update:model-value="
                                (valor) =>
                                    (form.periodo_id =
                                        valor === null ? '' : Number(valor))
                            "
                        />
                        <p
                            v-if="props.periodos && props.periodos.length === 0"
                            class="text-fino text-texto-suave"
                        >
                            {{ $t('pagos.sin_periodos_disponibles') }}
                        </p>
                        <InputError :message="form.errors.periodo_id" />
                    </Field>
                    <Field>
                        <Label for="fecha_pago">{{
                            $t('pagos.fecha_pago')
                        }}</Label>
                        <InputGroup>
                            <InputGroupInput
                                id="fecha_pago"
                                v-model="form.fecha_pago"
                                type="date"
                                required
                                :aria-invalid="!!form.errors.fecha_pago"
                                @change="form.validate('fecha_pago')"
                                :data-valido="form.valid('fecha_pago')"
                            />
                            <InputGroupAddon>
                                <Calendar aria-hidden="true" />
                            </InputGroupAddon>
                        </InputGroup>
                        <p class="text-fino text-texto-suave">
                            {{ $t('pagos.fecha_pago_ayuda') }}
                        </p>
                        <InputError :message="form.errors.fecha_pago" />
                    </Field>
                </div>
            </SeccionDeFormulario>
            <SeccionDeFormulario
                :titulo="$t('pagos.formulario.montos')"
                :descripcion="$t('pagos.formulario.montos_ayuda')"
            >
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="monto_usd">{{
                            $t('pagos.monto_usd')
                        }}</Label>
                        <InputGroup>
                            <InputGroupInput
                                id="monto_usd"
                                v-model="form.monto_usd"
                                type="number"
                                step="0.01"
                                min="0"
                                inputmode="decimal"
                                required
                                :placeholder="$t('pagos.monto_usd_marcador')"
                                :aria-invalid="!!form.errors.monto_usd"
                                @change="form.validate('monto_usd')"
                                :data-valido="form.valid('monto_usd')"
                            />
                            <InputGroupAddon>
                                <DollarSign aria-hidden="true" />
                            </InputGroupAddon>
                        </InputGroup>
                        <InputError :message="form.errors.monto_usd" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="tasa_cambio">{{
                            $t('pagos.tasa_cambio')
                        }}</Label>
                        <InputGroup>
                            <InputGroupInput
                                id="tasa_cambio"
                                v-model="form.tasa_cambio"
                                type="number"
                                step="0.0001"
                                min="0"
                                inputmode="decimal"
                                required
                                :placeholder="$t('pagos.tasa_cambio_marcador')"
                                :aria-invalid="!!form.errors.tasa_cambio"
                                @change="form.validate('tasa_cambio')"
                                :data-valido="form.valid('tasa_cambio')"
                            />
                            <InputGroupAddon>
                                <ArrowLeftRight aria-hidden="true" />
                            </InputGroupAddon>
                        </InputGroup>
                        <InputError :message="form.errors.tasa_cambio" />
                    </div>
                </div>
                <Field>
                    <Label for="origen_tasa">{{
                        $t('pagos.origen_tasa')
                    }}</Label>
                    <Select v-model="form.origen_tasa" required>
                        <SelectTrigger
                            id="origen_tasa"
                            class="w-full"
                            :aria-invalid="!!form.errors.origen_tasa"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opcion in props.origenesTasa"
                                :key="opcion.valor"
                                :value="opcion.valor"
                            >
                                {{ opcion.etiqueta }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-fino text-texto-suave">
                        {{ $t('pagos.origen_tasa_ayuda') }}
                    </p>
                    <InputError :message="form.errors.origen_tasa" />
                </Field>
            </SeccionDeFormulario>
            <SeccionDeFormulario
                :titulo="$t('pagos.formulario.referencia')"
                :descripcion="$t('pagos.formulario.referencia_ayuda')"
            >
                <Field>
                    <Label for="referencia">{{ $t('pagos.referencia') }}</Label>
                    <InputGroup>
                        <InputGroupInput
                            id="referencia"
                            v-model="form.referencia"
                            :placeholder="$t('pagos.referencia_marcador')"
                            :aria-invalid="!!form.errors.referencia"
                            @change="form.validate('referencia')"
                            :data-valido="form.valid('referencia')"
                        />
                        <InputGroupAddon>
                            <Hash aria-hidden="true" />
                        </InputGroupAddon>
                    </InputGroup>
                    <InputError :message="form.errors.referencia" />
                </Field>
                <Field>
                    <Label for="notas">{{ $t('pagos.notas') }}</Label>
                    <InputGroup>
                        <InputGroupTextarea
                            id="notas"
                            v-model="form.notas"
                            rows="3"
                            :placeholder="$t('pagos.notas_marcador')"
                            :aria-invalid="!!form.errors.notas"
                            @change="form.validate('notas')"
                            :data-valido="form.valid('notas')"
                        />
                        <InputGroupAddon>
                            <FileText aria-hidden="true" />
                        </InputGroupAddon>
                    </InputGroup>
                    <InputError :message="form.errors.notas" />
                </Field>
            </SeccionDeFormulario>
            <template #pie>
                <PieDeFormulario
                    :procesando="form.processing"
                    :cancelar="props.volverA"
                />
            </template>
        </TarjetaDeFormulario>
    </form>
</template>
