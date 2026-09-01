<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

const enviar = (): void => {
    form.submit(props.accion);
};
</script>

<template>
    <form class="space-y-6" @submit.prevent="enviar">
        <div class="grid gap-2">
            <Label for="nombre">{{ $t('comun.nombre') }}</Label>
            <Input id="nombre" v-model="form.nombre" required />
            <InputError :message="form.errors.nombre" />
        </div>

        <div class="grid gap-2">
            <Label for="descripcion">{{ $t('tareas.descripcion') }}</Label>
            <textarea
                id="descripcion"
                v-model="form.descripcion"
                rows="5"
                class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
            />
            <InputError :message="form.errors.descripcion" />
        </div>

        <div class="grid gap-2">
            <Label for="tipo">{{ $t('tareas.tipo') }}</Label>
            <select
                id="tipo"
                v-model="form.tipo"
                :disabled="esEdicion"
                required
                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
            >
                <option
                    v-for="opcion in props.opciones.tipos"
                    :key="opcion.valor"
                    :value="opcion.valor"
                >
                    {{ $t(opcion.etiqueta) }}
                </option>
            </select>
            <p class="text-xs text-muted-foreground">
                {{ $t('tareas.tipo_no_cambia') }}
            </p>
            <InputError :message="form.errors.tipo" />
        </div>

        <div class="grid gap-2">
            <Label for="prioridad">{{ $t('tareas.prioridad') }}</Label>
            <select
                id="prioridad"
                v-model="form.prioridad"
                required
                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
            >
                <option
                    v-for="opcion in props.opciones.prioridades"
                    :key="opcion.valor"
                    :value="opcion.valor"
                >
                    {{ $t(opcion.etiqueta) }}
                </option>
            </select>
            <InputError :message="form.errors.prioridad" />
        </div>

        <div class="grid gap-2">
            <Label for="modulo_id">{{ $t('tareas.modulo') }}</Label>
            <select
                id="modulo_id"
                v-model="form.modulo_id"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
            >
                <option value="">{{ $t('tareas.sin_modulo') }}</option>
                <option
                    v-for="modulo in props.opciones.modulos"
                    :key="modulo.id"
                    :value="modulo.id"
                >
                    {{ modulo.ruta }}
                </option>
            </select>
            <InputError :message="form.errors.modulo_id" />
        </div>

        <div v-if="esEdicion" class="grid gap-2">
            <Label for="periodo_id">{{ $t('tareas.periodo') }}</Label>
            <select
                id="periodo_id"
                v-model="form.periodo_id"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
            >
                <option value="">{{ $t('tareas.sin_periodo') }}</option>
                <option
                    v-for="periodo in props.opciones.periodos"
                    :key="periodo.id"
                    :value="periodo.id"
                >
                    {{ periodo.consecutivo }}
                </option>
            </select>
            <InputError :message="form.errors.periodo_id" />
        </div>

        <div v-if="admiteOrigen && props.tareasDeOrigen" class="grid gap-2">
            <Label for="tarea_origen_id">{{ $t('tareas.origen') }}</Label>
            <select
                id="tarea_origen_id"
                v-model="form.tarea_origen_id"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
            >
                <option value="">{{ $t('tareas.filtros.todos') }}</option>
                <option
                    v-for="candidata in props.tareasDeOrigen"
                    :key="candidata.id"
                    :value="candidata.id"
                >
                    {{ candidata.codigo }} — {{ candidata.nombre }}
                </option>
            </select>
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
