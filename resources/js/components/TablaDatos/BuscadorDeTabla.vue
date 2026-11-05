<script setup lang="ts">
/**
 * Búsqueda general del listado, siempre visible en la barra de la tabla.
 *
 * Es un filtro más —viaja como `filter[clave]` y la página dice qué clave—,
 * pero no cuelga de ninguna columna: se busca «Ana» sin saber si está en el
 * nombre o en el correo. Qué columnas recorre lo decide el controlador.
 *
 * Guarda una copia local del texto **solo mientras se escribe**: el valor que
 * manda es el de la URL, y en cuanto cambia (atrás, limpiar filtros) la copia
 * se sobrescribe.
 *
 * `type="text"` y no `search`: el navegador pinta su propia equis en los
 * campos de búsqueda y salían dos.
 */
import { Search, X } from '@lucide/vue';
import { ref, watch } from 'vue';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from '@/components/ui/input-group';

const props = defineProps<{
    valor: string;
    marcador: string;
    etiqueta: string;
}>();

const emit = defineEmits<{ cambiar: [valor: string, retrasar: boolean] }>();

const local = ref(props.valor);

watch(
    () => props.valor,
    (valor) => {
        local.value = valor;
    },
);

const alEscribir = (valor: string | number): void => {
    local.value = String(valor);
    emit('cambiar', local.value, true);
};

const limpiar = (): void => {
    local.value = '';
    emit('cambiar', '', false);
};
</script>

<template>
    <InputGroup class="w-full sm:w-72">
        <InputGroupInput
            :model-value="local"
            type="text"
            enterkeyhint="search"
            data-buscador-de-tabla
            :placeholder="props.marcador"
            :aria-label="props.etiqueta"
            @update:model-value="alEscribir"
            @keydown.enter.prevent="emit('cambiar', local, false)"
        />
        <InputGroupAddon>
            <Search aria-hidden="true" />
        </InputGroupAddon>
        <InputGroupAddon v-if="local !== ''" align="inline-end">
            <InputGroupButton
                type="button"
                size="icon-xs"
                :aria-label="$t('comun.tabla.limpiar_busqueda')"
                @click="limpiar"
            >
                <X aria-hidden="true" />
            </InputGroupButton>
        </InputGroupAddon>
    </InputGroup>
</template>
