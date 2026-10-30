<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { FileText, Type } from '@lucide/vue';
import { computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
});

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
    <form class="space-y-6" @submit.prevent="enviar">
        <div class="grid gap-2">
            <Label for="nombre">{{ $t('comun.nombre') }}</Label>
            <InputGroup>
                <InputGroupInput
                    id="nombre"
                    v-model="form.nombre"
                    required
                    :placeholder="$t('tareas.marcadores.nombre')"
                    :aria-invalid="!!form.errors.nombre"
                />
                <InputGroupAddon>
                    <Type aria-hidden="true" />
                </InputGroupAddon>
            </InputGroup>
            <InputError :message="form.errors.nombre" />
        </div>

        <div class="grid gap-2">
            <Label for="descripcion">{{ $t('tareas.descripcion') }}</Label>
            <InputGroup>
                <InputGroupTextarea
                    id="descripcion"
                    v-model="form.descripcion"
                    rows="5"
                    :placeholder="$t('tareas.marcadores.descripcion')"
                    :aria-invalid="!!form.errors.descripcion"
                />
                <InputGroupAddon align="block-start">
                    <FileText aria-hidden="true" />
                </InputGroupAddon>
            </InputGroup>
            <InputError :message="form.errors.descripcion" />
        </div>

        <div class="grid gap-2">
            <Label for="tipo">{{ $t('tareas.tipo') }}</Label>
            <Select v-model="form.tipo" :disabled="esEdicion" required>
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
            <p class="text-xs text-muted-foreground">
                {{ $t('tareas.tipo_no_cambia') }}
            </p>
            <InputError :message="form.errors.tipo" />
        </div>

        <div class="grid gap-2">
            <Label for="prioridad">{{ $t('tareas.prioridad') }}</Label>
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
        </div>

        <div class="grid gap-2">
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
        </div>

        <div v-if="esEdicion" class="grid gap-2">
            <Label for="periodo_id">{{ $t('tareas.periodo') }}</Label>
            <Select
                :model-value="comoOpcion(form.periodo_id)"
                @update:model-value="
                    (valor) => (form.periodo_id = desdeOpcion(valor))
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
        </div>

        <div v-if="admiteOrigen && props.tareasDeOrigen" class="grid gap-2">
            <Label for="tarea_origen_id">{{ $t('tareas.origen') }}</Label>
            <Select
                :model-value="comoOpcion(form.tarea_origen_id)"
                @update:model-value="
                    (valor) => (form.tarea_origen_id = desdeOpcion(valor))
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
            <p class="text-xs text-muted-foreground">
                {{ $t('tareas.origen_ayuda') }}
            </p>
            <InputError :message="form.errors.tarea_origen_id" />
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
