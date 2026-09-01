<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { FiltroDeColumna } from './tipos';

/**
 * El control de un filtro de columna. Sirve a los dos sitios donde aparece: la
 * capa flotante que cuelga de la cabecera (`FiltroDeCabecera`) y la lista
 * apilada del teléfono (`PanelDeFiltros`).
 *
 * El de texto guarda el valor localmente y avisa con retraso: sin eso, cada
 * pulsación de tecla sería una petición. El de opciones no lo necesita —un
 * `<select>` cambia una vez— y viaja al momento.
 *
 * **No lleva ancho propio.** Ocupa el de su contenedor, y ese contenedor nunca
 * es una celda de la tabla: es lo que impide que una columna estrecha lo
 * estruje hasta dejarlo ilegible. El atributo `data-filtro-de-columna` es lo
 * que busca `make humo` para comprobarlo en el navegador.
 */
const props = withDefaults(
    defineProps<{
        filtro: FiltroDeColumna;
        etiqueta: string;
        valor: string;
        /** Alto de control de formulario, no de celda de tabla. */
        comodo?: boolean;
    }>(),
    { comodo: false },
);

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

// Clases enteras y estáticas: el escáner de Tailwind lee el código como texto.
const alto = computed(() =>
    props.comodo ? 'h-8 px-corto text-cuerpo' : 'h-6 px-fino text-fino',
);
</script>

<template>
    <input
        v-if="props.filtro.tipo === 'texto'"
        v-model="local"
        type="search"
        data-filtro-de-columna
        class="w-full min-w-0 rounded-sm border border-borde bg-panel text-texto placeholder:text-texto-tenue"
        :class="alto"
        :placeholder="props.filtro.marcador ?? ''"
        :aria-label="$t('comun.tabla.filtrar_por', { columna: props.etiqueta })"
        @input="emit('cambiar', local, true)"
        @search="emit('cambiar', local, false)"
    />

    <select
        v-else
        v-model="local"
        data-filtro-de-columna
        class="w-full min-w-0 rounded-sm border border-borde bg-panel text-texto"
        :class="alto"
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
