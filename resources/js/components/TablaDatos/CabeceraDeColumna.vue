<script setup lang="ts">
import { ArrowDown, ArrowUp, ChevronsUpDown } from '@lucide/vue';
import { computed } from 'vue';
import type { ColumnaDeTabla, OrdenDeColumna } from './tipos';

/**
 * Cabecera de una columna: etiqueta, control de orden y **prioridad de orden
 * visible**.
 *
 * El número que aparece junto a la flecha cuando hay más de un campo ordenado
 * no es decoración: sin él, «ordenado por prioridad y por código» y «ordenado
 * por código y por prioridad» se ven exactamente igual y dan resultados
 * distintos.
 */
const props = defineProps<{
    columna: ColumnaDeTabla<unknown>;
    orden: OrdenDeColumna[];
}>();

const emit = defineEmits<{ alternar: [acumular: boolean] }>();

const entrada = computed(() =>
    props.orden.find((registro) => registro.campo === props.columna.ordenPor),
);

const posicion = computed(
    () =>
        props.orden.findIndex(
            (registro) => registro.campo === props.columna.ordenPor,
        ) + 1,
);

/** `aria-sort` va en el `<th>`, no aquí: lo lee el padre. */
const descripcion = computed(() => {
    if (entrada.value === undefined) {
        return null;
    }

    const direccion =
        entrada.value.direccion === 'asc'
            ? 'comun.tabla.orden_ascendente'
            : 'comun.tabla.orden_descendente';

    return { direccion, mostrarPosicion: props.orden.length > 1 };
});
</script>

<template>
    <button
        v-if="props.columna.ordenPor"
        type="button"
        class="group -mx-fino inline-flex items-center gap-fino rounded-sm px-fino py-[1px] hover:text-texto"
        :title="
            $t('comun.tabla.ordenar_por', { columna: props.columna.etiqueta })
        "
        @click="emit('alternar', $event.shiftKey)"
    >
        <span>{{ props.columna.etiqueta }}</span>

        <component
            :is="entrada?.direccion === 'desc' ? ArrowDown : ArrowUp"
            v-if="entrada"
            class="size-3 shrink-0"
            aria-hidden="true"
        />
        <ChevronsUpDown
            v-else
            class="size-3 shrink-0 opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100"
            aria-hidden="true"
        />

        <!-- La posición solo aparece cuando hay más de un campo: con uno solo
             sería un «1» que no informa de nada. -->
        <span
            v-if="descripcion?.mostrarPosicion"
            class="inline-flex size-3.5 items-center justify-center rounded-full bg-borde-fuerte text-[9px] leading-none font-medium text-texto"
        >
            {{ posicion }}
        </span>

        <span v-if="descripcion" class="sr-only">
            {{ $t(descripcion.direccion) }}
            <template v-if="descripcion.mostrarPosicion">
                —
                {{
                    $t('comun.tabla.orden_posicion', {
                        posicion: String(posicion),
                    })
                }}
            </template>
        </span>
    </button>

    <span v-else>{{ props.columna.etiqueta }}</span>
</template>
