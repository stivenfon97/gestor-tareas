<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FileText, Type } from '@lucide/vue';
import { computed, watch } from 'vue';
import PieDeFormulario from '@/components/formularios/PieDeFormulario.vue';
import SeccionDeFormulario from '@/components/formularios/SeccionDeFormulario.vue';
import TarjetaDeFormulario from '@/components/formularios/TarjetaDeFormulario.vue';
import InputError from '@/components/InputError.vue';
import { Field, FieldDescription } from '@/components/ui/field';
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
import type {
    OpcionesDeTarea,
    PrioridadTarea,
    TipoTarea,
} from '@/types/tareas';
import type { RouteDefinition } from '@/wayfinder';

/**
 * Formulario general de la tarea.
 *
 * **El estado no está aquí.** Tiene su propio control (`ControlDeEstado.vue`) y
 * su propio endpoint: mezclarlo con el resto de campos permitiría saltarse la
 * matriz de transiciones por descuido.
 */
const props = defineProps<{
    opciones: OpcionesDeTarea;
    /** Acción de Wayfinder a la que enviar. */
    accion: RouteDefinition<'post'> | RouteDefinition<'put'>;
    volverA: string;
    /** Al editar, el tipo ya está fijado y no se ofrece. */
    tipoFijado?: TipoTarea;
    inicial?: {
        nombre: string;
        descripcion: string | null;
        tipo: TipoTarea;
        prioridad: PrioridadTarea;
        modulo_id: number | null;
        periodo_id: number | null;
        tarea_origen_id: number | null;
    };
    /** Prioridad que precarga cada tipo, para reflejarlo al elegir. */
    prioridadPorTipo?: Record<string, string>;
    moduloSugerido?: number | null;
    tareasDeOrigen?: Array<{ id: number; codigo: string; nombre: string }>;
}>();

const esEdicion = computed(() => props.tipoFijado !== undefined);

const form = useForm({
    nombre: props.inicial?.nombre ?? '',
    descripcion: props.inicial?.descripcion ?? '',
    tipo: props.inicial?.tipo ?? props.opciones.tipos[0]?.valor ?? 'desarrollo',
    prioridad: props.inicial?.prioridad ?? 'media',
    modulo_id: props.inicial?.modulo_id ?? props.moduloSugerido ?? '',
    periodo_id: props.inicial?.periodo_id ?? '',
    tarea_origen_id: props.inicial?.tarea_origen_id ?? '',
})
    .withPrecognition(props.accion)
    .setValidationTimeout(500);

/*
 * Al elegir el tipo, la prioridad se precarga con la que ese tipo sugiere. Es un
 * valor por defecto y no una restricción: se puede cambiar justo después.
 */
watch(
    () => form.tipo,
    (tipo) => {
        if (!esEdicion.value && props.prioridadPorTipo?.[tipo]) {
            form.prioridad = props.prioridadPorTipo[tipo] as PrioridadTarea;
        }
    },
);

// `tarea_origen_id` solo tiene sentido en corrección y soporte.
const admiteOrigen = computed(() =>
    ['correccion', 'soporte'].includes(form.tipo),
);

watch(admiteOrigen, (admite) => {
    if (!admite) {
        form.tarea_origen_id = '';
    }
});

/*
 * `SelectItem` no admite el valor vacío (reka-ui lo reserva para «sin
 * selección»), así que las opciones «Sin módulo», «Sin periodo» y «Sin tarea
 * de origen» usan este centinela, y el formulario sigue enviando `''`.
 */
const NINGUNO = '__ninguno__';

const comoOpcion = (valor: string | number | null): string =>
    valor === '' || valor === null ? NINGUNO : String(valor);

const desdeOpcion = (valor: unknown): number | '' =>
    valor === NINGUNO || valor === null || valor === undefined
        ? ''
        : Number(valor);

const enviar = (): void => {
    form.submit(props.accion);
};
</script>

<template>
    <form novalidate @submit.prevent="enviar">
        <TarjetaDeFormulario>
            <SeccionDeFormulario
                :titulo="$t('tareas.formulario.trabajo')"
                :descripcion="$t('tareas.formulario.trabajo_ayuda')"
            >
                <Field>
                    <Label for="nombre">{{ $t('comun.nombre') }}</Label>
                    <InputGroup>
                        <InputGroupInput
                            id="nombre"
                            v-model="form.nombre"
                            required
                            :placeholder="$t('tareas.marcadores.nombre')"
                            :aria-invalid="!!form.errors.nombre"
                            @change="form.validate('nombre')"
                            :data-valido="form.valid('nombre')"
                        />
                        <InputGroupAddon>
                            <Type aria-hidden="true" />
                        </InputGroupAddon>
                    </InputGroup>
                    <InputError :message="form.errors.nombre" />
                </Field>
                <Field>
                    <Label for="descripcion">{{
                        $t('tareas.descripcion')
                    }}</Label>
                    <InputGroup>
                        <InputGroupTextarea
                            id="descripcion"
                            v-model="form.descripcion"
                            rows="5"
                            :placeholder="$t('tareas.marcadores.descripcion')"
                            :aria-invalid="!!form.errors.descripcion"
                            @change="form.validate('descripcion')"
                            :data-valido="form.valid('descripcion')"
                        />
                        <InputGroupAddon align="block-start">
                            <FileText aria-hidden="true" />
                        </InputGroupAddon>
                    </InputGroup>
                    <InputError :message="form.errors.descripcion" />
                </Field>
            </SeccionDeFormulario>
            <SeccionDeFormulario
                :titulo="$t('tareas.formulario.clasificacion')"
                :descripcion="$t('tareas.formulario.clasificacion_ayuda')"
            >
                <div class="grid gap-bloque md:grid-cols-2">
                    <Field>
                        <Label for="tipo">{{ $t('tareas.tipo') }}</Label>
                        <Select
                            v-model="form.tipo"
                            :disabled="esEdicion"
                            required
                        >
                            <SelectTrigger
                                id="tipo"
                                class="w-full"
                                :aria-invalid="!!form.errors.tipo"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="opcion in props.opciones.tipos"
                                    :key="opcion.valor"
                                    :value="opcion.valor"
                                >
                                    {{ $t(opcion.etiqueta) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <FieldDescription>
                            {{ $t('tareas.tipo_no_cambia') }}
                        </FieldDescription>
                        <InputError :message="form.errors.tipo" />
                    </Field>
                    <Field>
                        <Label for="prioridad">{{
                            $t('tareas.prioridad')
                        }}</Label>
                        <Select v-model="form.prioridad" required>
                            <SelectTrigger
                                id="prioridad"
                                class="w-full"
                                :aria-invalid="!!form.errors.prioridad"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="opcion in props.opciones.prioridades"
                                    :key="opcion.valor"
                                    :value="opcion.valor"
                                >
                                    {{ $t(opcion.etiqueta) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.prioridad" />
                    </Field>
                </div>
                <div class="grid gap-bloque md:grid-cols-2">
                    <Field>
                        <Label for="modulo_id">{{ $t('tareas.modulo') }}</Label>
                        <Select
                            :model-value="comoOpcion(form.modulo_id)"
                            @update:model-value="
                                (valor) => (form.modulo_id = desdeOpcion(valor))
                            "
                        >
                            <SelectTrigger
                                id="modulo_id"
                                class="w-full"
                                :aria-invalid="!!form.errors.modulo_id"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NINGUNO">
                                    {{ $t('tareas.sin_modulo') }}
                                </SelectItem>
                                <SelectItem
                                    v-for="modulo in props.opciones.modulos"
                                    :key="modulo.id"
                                    :value="String(modulo.id)"
                                >
                                    {{ modulo.ruta }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.modulo_id" />
                    </Field>
                    <Field v-if="esEdicion">
                        <Label for="periodo_id">{{
                            $t('tareas.periodo')
                        }}</Label>
                        <Select
                            :model-value="comoOpcion(form.periodo_id)"
                            @update:model-value="
                                (valor) =>
                                    (form.periodo_id = desdeOpcion(valor))
                            "
                        >
                            <SelectTrigger
                                id="periodo_id"
                                class="w-full"
                                :aria-invalid="!!form.errors.periodo_id"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NINGUNO">
                                    {{ $t('tareas.sin_periodo') }}
                                </SelectItem>
                                <SelectItem
                                    v-for="periodo in props.opciones.periodos"
                                    :key="periodo.id"
                                    :value="String(periodo.id)"
                                >
                                    {{ periodo.consecutivo }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.periodo_id" />
                    </Field>
                </div>
                <Field v-if="admiteOrigen && props.tareasDeOrigen">
                    <Label for="tarea_origen_id">{{
                        $t('tareas.origen')
                    }}</Label>
                    <Select
                        :model-value="comoOpcion(form.tarea_origen_id)"
                        @update:model-value="
                            (valor) =>
                                (form.tarea_origen_id = desdeOpcion(valor))
                        "
                    >
                        <SelectTrigger
                            id="tarea_origen_id"
                            class="w-full"
                            :aria-invalid="!!form.errors.tarea_origen_id"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="NINGUNO">
                                {{ $t('tareas.sin_origen') }}
                            </SelectItem>
                            <SelectItem
                                v-for="candidata in props.tareasDeOrigen"
                                :key="candidata.id"
                                :value="String(candidata.id)"
                            >
                                {{ candidata.codigo }} — {{ candidata.nombre }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <FieldDescription>
                        {{ $t('tareas.origen_ayuda') }}
                    </FieldDescription>
                    <InputError :message="form.errors.tarea_origen_id" />
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
