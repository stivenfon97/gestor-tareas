<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CalendarRange, FileText, Layers, ListChecks, Type } from '@lucide/vue';
import { computed, watch } from 'vue';
import PieDeFormulario from '@/components/formularios/PieDeFormulario.vue';
import SeccionDeFormulario from '@/components/formularios/SeccionDeFormulario.vue';
import TarjetaDeFormulario from '@/components/formularios/TarjetaDeFormulario.vue';
import InputError from '@/components/InputError.vue';
import SelectorConBusqueda from '@/components/SelectorConBusqueda.vue';
import type { OpcionDeSelector } from '@/components/SelectorConBusqueda.vue';
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
import { trans } from '@/lib/i18n';
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
 * Módulo, periodo y tarea de origen salen de registros y crecen con el uso:
 * van en `SelectorConBusqueda`. Ese selector usa `null` para «ninguno» y el
 * formulario sigue enviando `''`.
 */
const comoValor = (valor: string | number | null): number | null =>
    valor === '' || valor === null ? null : Number(valor);

const desdeValor = (valor: string | number | null): number | '' =>
    valor === null ? '' : Number(valor);

const opcionesDeModulo = computed<OpcionDeSelector[]>(() =>
    props.opciones.modulos.map((modulo) => ({
        valor: modulo.id,
        etiqueta: modulo.ruta,
    })),
);

const opcionesDePeriodo = computed<OpcionDeSelector[]>(() =>
    props.opciones.periodos.map((periodo) => ({
        valor: periodo.id,
        etiqueta: trans('proyectos.resumen.periodo_numero', {
            consecutivo: String(periodo.consecutivo),
        }),
        detalle: trans(`enums.estado_periodo.${periodo.estado}`),
    })),
);

/** Código y nombre en la etiqueta: se encuentra escribiendo «0042» o una palabra. */
const opcionesDeOrigen = computed<OpcionDeSelector[]>(() =>
    (props.tareasDeOrigen ?? []).map((candidata) => ({
        valor: candidata.id,
        etiqueta: `${candidata.codigo} — ${candidata.nombre}`,
    })),
);

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
                        <InputGroupAddon>
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
                        <SelectorConBusqueda
                            id="modulo_id"
                            :model-value="comoValor(form.modulo_id)"
                            :opciones="opcionesDeModulo"
                            :marcador="$t('tareas.sin_modulo')"
                            :nula="$t('tareas.sin_modulo')"
                            :icono="Layers"
                            :invalido="!!form.errors.modulo_id"
                            @update:model-value="
                                (valor) => (form.modulo_id = desdeValor(valor))
                            "
                        />
                        <InputError :message="form.errors.modulo_id" />
                    </Field>
                    <Field v-if="esEdicion">
                        <Label for="periodo_id">{{
                            $t('tareas.periodo')
                        }}</Label>
                        <SelectorConBusqueda
                            id="periodo_id"
                            :model-value="comoValor(form.periodo_id)"
                            :opciones="opcionesDePeriodo"
                            :marcador="$t('tareas.sin_periodo')"
                            :nula="$t('tareas.sin_periodo')"
                            :icono="CalendarRange"
                            :invalido="!!form.errors.periodo_id"
                            @update:model-value="
                                (valor) => (form.periodo_id = desdeValor(valor))
                            "
                        />
                        <InputError :message="form.errors.periodo_id" />
                    </Field>
                </div>
                <Field v-if="admiteOrigen && props.tareasDeOrigen">
                    <Label for="tarea_origen_id">{{
                        $t('tareas.origen')
                    }}</Label>
                    <SelectorConBusqueda
                        id="tarea_origen_id"
                        :model-value="comoValor(form.tarea_origen_id)"
                        :opciones="opcionesDeOrigen"
                        :marcador="$t('tareas.sin_origen')"
                        :nula="$t('tareas.sin_origen')"
                        :icono="ListChecks"
                        :invalido="!!form.errors.tarea_origen_id"
                        @update:model-value="
                            (valor) =>
                                (form.tarea_origen_id = desdeValor(valor))
                        "
                    />
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
