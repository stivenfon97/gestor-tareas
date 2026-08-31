<script setup lang="ts">
import { ref, watch } from 'vue';
import type { FiltroDeColumna } from './tipos';

/**
 * Filtro de una columna, en su propia fila bajo la cabecera.
 *
 * El de texto guarda el valor localmente y avisa con retraso: sin eso, cada
 * pulsación de tecla sería una petición. El de opciones no lo necesita —un
 * `<select>` cambia una vez— y viaja al momento.
 */
const props = defineProps<{
    filtro: FiltroDeColumna;
    etiqueta: string;
    valor: string;
}>();

const emit = defineEmits<{ cambiar: [valor: string, retrasar: boolean] }>();

const local = ref(props.valor);

// El servidor manda: si la navegación cambia el filtro (botón «atrás», enlace
// compartido, «limpiar filtros»), el campo se resincroniza.
watch(
    () => props.valor,
    (valor) => {
        local.value = valor;
    },
);
</script>

<template>
    <input
        v-if="props.filtro.tipo === 'texto'"
        v-model="local"
        type="search"
        class="h-6 w-full min-w-0 rounded-sm border border-borde bg-panel px-fino text-fino text-texto placeholder:text-texto-tenue"
        :placeholder="props.filtro.marcador ?? ''"
        :aria-label="$t('comun.tabla.filtrar_por', { columna: props.etiqueta })"
        @input="emit('cambiar', local, true)"
        @search="emit('cambiar', local, false)"
    />

    <select
        v-else
        v-model="local"
        class="h-6 w-full min-w-0 rounded-sm border border-borde bg-panel px-fino text-fino text-texto"
        :aria-label="$t('comun.tabla.filtrar_por', { columna: props.etiqueta })"
        @change="emit('cambiar', local, false)"
    >
        <option value="">{{ $t('comun.tabla.todos') }}</option>
        <option
            v-for="opcion in props.filtro.opciones"
            :key="opcion.valor"
            :value="opcion.valor"
        >
            {{ opcion.etiqueta }}
        </option>
    </select>
</template>
