<script setup lang="ts">
import {
    ChevronDown,
    ChevronRight,
    GripVertical,
    Plus,
    Trash2,
} from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import draggable from 'vuedraggable';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { NodoModulo } from '@/types/modulos';

/**
 * Un nodo del árbol de módulos, que se renderiza a sí mismo para sus hijos.
 *
 * **La clave de renderizado es el id, nunca el índice.** Con el índice, mover un
 * nodo hace que Vue reutilice el DOM del hermano que ocupaba esa posición y el
 * estado local —rama colapsada, campo en edición— salta al nodo equivocado.
 */
const props = defineProps<{
    nodo: NodoModulo;
    puedeGestionar: boolean;
    colapsados: Set<number>;
    profundidad: number;
}>();

/*
 * Los hijos van por `v-model` y no leyendo `nodo.hijos` directamente: el
 * arrastre reordena ese array, y escribir dentro de una prop es justo lo que
 * hace que un árbol recursivo se descuadre sin que Vue avise.
 */
const hijos = defineModel<NodoModulo[]>('hijos', { required: true });

const emit = defineEmits<{
    (evento: 'alternar', id: number): void;
    (evento: 'renombrar', id: number, nombre: string): void;
    (evento: 'crear-hijo', id: number, nombre: string): void;
    (evento: 'eliminar', nodo: NodoModulo): void;
    (evento: 'reordenar'): void;
}>();

const colapsado = computed(() => props.colapsados.has(props.nodo.id));
const tieneHijos = computed(() => hijos.value.length > 0);

const editando = ref(false);
const nombreEditado = ref(props.nodo.nombre);
const campoNombre = ref<InstanceType<typeof Input> | null>(null);

const anadiendo = ref(false);
const nombreHijo = ref('');

const abrirEdicion = async (): Promise<void> => {
    if (!props.puedeGestionar) {
        return;
    }

    nombreEditado.value = props.nodo.nombre;
    editando.value = true;

    await nextTick();
    (campoNombre.value?.$el as HTMLInputElement | undefined)?.focus();
};

const confirmarEdicion = (): void => {
    const nombre = nombreEditado.value.trim();
    editando.value = false;

    if (nombre !== '' && nombre !== props.nodo.nombre) {
        emit('renombrar', props.nodo.id, nombre);
    }
};

const confirmarHijo = (): void => {
    const nombre = nombreHijo.value.trim();

    if (nombre === '') {
        anadiendo.value = false;

        return;
    }

    emit('crear-hijo', props.nodo.id, nombre);
    nombreHijo.value = '';
    anadiendo.value = false;
};

/** Etiqueta del contador, que ya incluye las tareas de los descendientes. */
const etiquetaTareas = computed(() => {
    if (props.nodo.tareas_totales === 0) {
        return 'modulos.tareas_contador_cero';
    }

    return props.nodo.tareas_totales === 1
        ? 'modulos.tareas_contador_una'
        : 'modulos.tareas_contador';
});
</script>

<template>
    <li class="flex flex-col">
        <div
            class="flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-accent/50"
        >
            <button
                v-if="tieneHijos"
                type="button"
                class="text-muted-foreground"
                :aria-label="
                    colapsado ? $t('modulos.expandir') : $t('modulos.colapsar')
                "
                :aria-expanded="!colapsado"
                @click="emit('alternar', props.nodo.id)"
            >
                <ChevronRight v-if="colapsado" class="size-4" />
                <ChevronDown v-else class="size-4" />
            </button>
            <span v-else class="size-4" aria-hidden="true" />

            <GripVertical
                v-if="props.puedeGestionar"
                class="tirador size-4 shrink-0 cursor-grab text-muted-foreground"
                :aria-label="$t('modulos.arrastrar')"
            />

            <Input
                v-if="editando"
                ref="campoNombre"
                v-model="nombreEditado"
                class="h-7 max-w-xs"
                @blur="confirmarEdicion"
                @keydown.enter.prevent="confirmarEdicion"
                @keydown.esc.prevent="editando = false"
            />
            <button
                v-else
                type="button"
                class="truncate text-left text-sm"
                :class="
                    props.puedeGestionar ? 'hover:underline' : 'cursor-default'
                "
                :title="
                    props.puedeGestionar ? $t('modulos.renombrar') : undefined
                "
                @click="abrirEdicion"
            >
                {{ props.nodo.nombre }}
            </button>

            <span
                class="ml-auto shrink-0 text-xs text-muted-foreground"
                :title="$t('modulos.tareas_incluye_hijos')"
            >
                {{
                    $t(etiquetaTareas, {
                        total: String(props.nodo.tareas_totales),
                    })
                }}
            </span>

            <template v-if="props.puedeGestionar">
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-7"
                    :aria-label="$t('modulos.crear_hijo')"
                    @click="anadiendo = !anadiendo"
                >
                    <Plus class="size-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-7"
                    :aria-label="$t('comun.eliminar')"
                    @click="emit('eliminar', props.nodo)"
                >
                    <Trash2 class="size-4" />
                </Button>
            </template>
        </div>

        <div v-if="anadiendo" class="flex items-center gap-2 py-1 pl-8">
            <Input
                v-model="nombreHijo"
                class="h-7 max-w-xs"
                :placeholder="$t('modulos.nombre_nuevo')"
                @keydown.enter.prevent="confirmarHijo"
                @keydown.esc.prevent="anadiendo = false"
            />
            <Button size="sm" variant="outline" @click="confirmarHijo">
                {{ $t('comun.agregar') }}
            </Button>
        </div>

        <draggable
            v-show="!colapsado"
            v-model="hijos"
            :group="{ name: 'modulos' }"
            item-key="id"
            handle=".tirador"
            :disabled="!props.puedeGestionar"
            tag="ul"
            class="ml-4 flex flex-col border-l border-border pl-2"
            @change="emit('reordenar')"
        >
            <template #item="{ element }">
                <ModuloNodo
                    :key="element.id"
                    v-model:hijos="element.hijos"
                    :nodo="element"
                    :puede-gestionar="props.puedeGestionar"
                    :colapsados="props.colapsados"
                    :profundidad="props.profundidad + 1"
                    @alternar="emit('alternar', $event)"
                    @renombrar="(id, nombre) => emit('renombrar', id, nombre)"
                    @crear-hijo="(id, nombre) => emit('crear-hijo', id, nombre)"
                    @eliminar="emit('eliminar', $event)"
                    @reordenar="emit('reordenar')"
                />
            </template>
        </draggable>
    </li>
</template>
