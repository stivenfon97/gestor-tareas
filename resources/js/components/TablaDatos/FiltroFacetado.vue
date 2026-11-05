<script setup lang="ts">
/**
 * Filtro de opciones de una columna, **visible en la barra de la tabla**.
 *
 * Hasta aquí los filtros solo existían como un icono de 14px en la cabecera de
 * cada columna y nadie los encontraba. Ahora cada filtro de opciones es un
 * botón con su nombre, y cuando tiene un valor lo enseña dentro: la barra dice
 * qué se está filtrando sin abrir nada.
 *
 * Con más de ocho opciones (clientes, proyectos) la lista lleva su propio
 * buscador: cincuenta clientes en una lista sin buscar no se recorren.
 */
import { Check, ListFilter, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import type { OpcionDeFiltro } from './tipos';

const props = defineProps<{
    etiqueta: string;
    opciones: OpcionDeFiltro[];
    valor: string;
}>();

const emit = defineEmits<{ cambiar: [valor: string] }>();

const abierto = ref(false);
const busqueda = ref('');

const UMBRAL_DE_BUSQUEDA = 8;

const seleccionada = computed(() =>
    props.opciones.find((opcion) => opcion.valor === props.valor),
);

const visibles = computed(() => {
    const termino = busqueda.value.trim().toLocaleLowerCase();

    return termino === ''
        ? props.opciones
        : props.opciones.filter((opcion) =>
              opcion.etiqueta.toLocaleLowerCase().includes(termino),
          );
});

const elegir = (valor: string): void => {
    emit('cambiar', valor === props.valor ? '' : valor);
    abierto.value = false;
    busqueda.value = '';
};
</script>

<template>
    <Popover v-model:open="abierto">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                size="sm"
                data-filtro-facetado
                :class="cn('border-dashed', seleccionada && 'border-solid')"
            >
                <ListFilter aria-hidden="true" />
                {{ props.etiqueta }}
                <template v-if="seleccionada">
                    <span
                        class="mx-fino h-4 w-px bg-borde"
                        aria-hidden="true"
                    />
                    <Badge
                        variant="info"
                        class="max-w-40 truncate rounded-sm px-ajustado font-normal"
                    >
                        {{ seleccionada.etiqueta }}
                    </Badge>
                </template>
            </Button>
        </PopoverTrigger>

        <PopoverContent
            align="start"
            class="flex w-64 flex-col gap-fino p-fino"
        >
            <InputGroup
                v-if="props.opciones.length > UMBRAL_DE_BUSQUEDA"
                class="mb-fino"
            >
                <InputGroupInput
                    v-model="busqueda"
                    type="search"
                    :placeholder="$t('comun.tabla.buscar_opcion')"
                    :aria-label="
                        $t('comun.tabla.filtrar_por', {
                            columna: props.etiqueta,
                        })
                    "
                />
                <InputGroupAddon>
                    <Search aria-hidden="true" />
                </InputGroupAddon>
            </InputGroup>

            <ul
                role="listbox"
                :aria-label="props.etiqueta"
                class="flex max-h-64 flex-col overflow-y-auto"
            >
                <li
                    v-for="opcion in visibles"
                    :key="opcion.valor"
                    role="option"
                    :aria-selected="opcion.valor === props.valor"
                >
                    <button
                        type="button"
                        class="flex w-full items-center gap-corto rounded-sm px-corto py-ajustado text-left text-cuerpo text-texto hover:bg-fila-hover focus-visible:bg-fila-hover focus-visible:outline-none"
                        @click="elegir(opcion.valor)"
                    >
                        <span
                            :class="
                                cn(
                                    'flex size-4 shrink-0 items-center justify-center rounded-sm border border-borde-fuerte',
                                    opcion.valor === props.valor &&
                                        'border-marca bg-marca text-marca-contraste',
                                )
                            "
                            aria-hidden="true"
                        >
                            <Check
                                v-if="opcion.valor === props.valor"
                                class="size-3"
                            />
                        </span>
                        <span class="truncate">{{ opcion.etiqueta }}</span>
                    </button>
                </li>
                <li
                    v-if="visibles.length === 0"
                    class="px-corto py-ajustado text-fino text-texto-suave"
                >
                    {{ $t('comun.tabla.sin_opciones') }}
                </li>
            </ul>

            <template v-if="seleccionada">
                <div class="my-fino h-px bg-borde" aria-hidden="true" />
                <Button
                    variant="ghost"
                    size="sm"
                    class="justify-center text-texto-suave"
                    @click="elegir(props.valor)"
                >
                    {{ $t('comun.tabla.limpiar_filtro') }}
                </Button>
            </template>
        </PopoverContent>
    </Popover>
</template>
