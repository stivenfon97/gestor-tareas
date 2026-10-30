<script setup lang="ts">
import { Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { FiltroDeColumna } from './tipos';

/**
 * El control de un filtro de columna. Sirve a los dos sitios donde aparece: la
 * capa flotante que cuelga de la cabecera (`FiltroDeCabecera`) y la lista
 * apilada del teléfono (`PanelDeFiltros`).
 *
 * El de texto guarda el valor localmente y avisa con retraso: sin eso, cada
 * pulsación de tecla sería una petición. El de opciones no lo necesita —un
 * `Select` cambia una vez— y viaja al momento.
 *
 * **No lleva ancho propio.** Ocupa el de su contenedor, y ese contenedor nunca
 * es una celda de la tabla: es lo que impide que una columna estrecha lo
 * estruje hasta dejarlo ilegible. El atributo `data-filtro-de-columna` es lo
 * que busca `make humo` para comprobarlo en el navegador.
 *
 * **La etiqueta visible la pone quien lo usa** (`FiltroDeCabecera`,
 * `PanelDeFiltros`), con un `Label` cuyo `for` es `campoId`.
 */
const props = defineProps<{
    filtro: FiltroDeColumna;
    etiqueta: string;
    valor: string;
    /** `id` del control, para el `Label` que pone el contenedor. */
    campoId: string;
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

/*
 * El `Select` de shadcn no admite un elemento con valor vacío —lo reserva para
 * «sin selección»—, y «todos» es justo el filtro vacío. Se traduce en la
 * frontera: dentro del `Select` es este centinela, hacia fuera sigue siendo ''.
 */
const TODOS = '__todos__';

const alEscribir = (valor: string | number): void => {
    local.value = String(valor);
    emit('cambiar', local.value, true);
};

const alElegir = (valor: unknown): void => {
    local.value = valor === TODOS ? '' : String(valor ?? '');
    emit('cambiar', local.value, false);
};
</script>

<template>
    <InputGroup v-if="props.filtro.tipo === 'texto'">
        <InputGroupInput
            :id="props.campoId"
            :model-value="local"
            type="search"
            data-filtro-de-columna
            :placeholder="props.filtro.marcador ?? ''"
            :aria-label="
                $t('comun.tabla.filtrar_por', { columna: props.etiqueta })
            "
            @update:model-value="alEscribir"
            @search="
                emit(
                    'cambiar',
                    ($event.target as HTMLInputElement).value,
                    false,
                )
            "
        />
        <InputGroupAddon>
            <Search aria-hidden="true" />
        </InputGroupAddon>
    </InputGroup>

    <Select
        v-else
        :model-value="local === '' ? TODOS : local"
        @update:model-value="alElegir"
    >
        <SelectTrigger
            :id="props.campoId"
            data-filtro-de-columna
            class="w-full"
            :aria-label="
                $t('comun.tabla.filtrar_por', { columna: props.etiqueta })
            "
        >
            <SelectValue />
        </SelectTrigger>
        <SelectContent>
            <SelectItem :value="TODOS">{{
                $t('comun.tabla.todos')
            }}</SelectItem>
            <SelectItem
                v-for="opcion in props.filtro.opciones"
                :key="opcion.valor"
                :value="opcion.valor"
            >
                {{ opcion.etiqueta }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
