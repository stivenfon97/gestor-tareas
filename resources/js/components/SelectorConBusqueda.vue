<script setup lang="ts">
/**
 * Un `Select` para listas que crecen con el uso: clientes, personas, módulos,
 * periodos, tareas. Se abre con un buscador arriba y filtra al escribir.
 *
 * Un `Select` normal sirve para un conjunto cerrado (estado, rol, prioridad):
 * cinco o seis valores que no cambian. Con cuarenta módulos o setenta tareas la
 * lista ya no se recorre con la vista y hay que hacer scroll a ciegas; este
 * componente es la respuesta, y se usa **siempre** que la lista sale de
 * registros, aunque hoy tenga tres: mañana tendrá cincuenta.
 *
 * Se compone sobre el `Combobox` de shadcn-vue (reka-ui), así que el teclado
 * es el de siempre: flechas para moverse, Intro para elegir, Escape para
 * cerrar. El disparador lleva las mismas clases que `SelectTrigger`, de modo
 * que foco, error (`invalido` → `aria-invalid`) y éxito (`valido` →
 * `data-valido`) se pintan igual que en cualquier otro campo.
 *
 * - El filtro es propio (`ignore-filter`): no distingue mayúsculas ni tildes
 *   («modulo» encuentra «Módulo») y busca también en `detalle`.
 * - Se pintan como mucho `LIMITE_VISIBLE` opciones. Con mil tareas, montar mil
 *   filas al abrir sería lento; con el aviso de «escribe para afinar» nadie
 *   echa en falta las que no salen.
 * - `nula` añade una primera opción «ninguno» (o «todos» en un filtro) que
 *   devuelve `null`.
 * - `name` añade un `<input type="hidden">` para los `<Form>` de Inertia, que
 *   leen el formulario del DOM.
 */
import { Check, ChevronDown } from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref, watch } from 'vue';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
    ComboboxViewport,
} from '@/components/ui/combobox';
import { trans } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type Valor = string | number;

export type OpcionDeSelector = {
    valor: Valor;
    etiqueta: string;
    /** Texto secundario que también se busca (un código, un correo…). */
    detalle?: string | null;
};

const props = withDefaults(
    defineProps<{
        id: string;
        opciones: OpcionDeSelector[];
        /** Lo que dice el disparador cuando no hay nada elegido. */
        marcador: string;
        /** Etiqueta de la opción vacía; sin ella no se puede volver a «nada». */
        nula?: string | null;
        icono?: Component | null;
        invalido?: boolean;
        valido?: boolean;
        deshabilitado?: boolean;
        name?: string | null;
        class?: string;
    }>(),
    {
        nula: null,
        icono: null,
        invalido: false,
        valido: false,
        deshabilitado: false,
        name: null,
        class: '',
    },
);

/*
 * Los atributos de fuera (`aria-label`, `data-*`) van al disparador, que es el
 * control: no al envoltorio, que no ocupa caja (`contents`).
 */
defineOptions({ inheritAttrs: false });

const modelo = defineModel<Valor | null>({ default: null });

const LIMITE_VISIBLE = 100;

/*
 * reka-ui compara por igualdad estricta y el servidor a veces manda el id como
 * número y la opción como texto: se trabaja con la clave en texto y se devuelve
 * el valor original de la opción.
 */
const NINGUNO = '__ninguno__';
const clave = (valor: Valor | null | undefined): string =>
    valor === null || valor === undefined || valor === ''
        ? NINGUNO
        : String(valor);

const abierto = ref(false);
const busqueda = ref('');

watch(abierto, (estaAbierto) => {
    if (!estaAbierto) {
        busqueda.value = '';
    }
});

const seleccionada = computed(() =>
    props.opciones.find(
        (opcion) => clave(opcion.valor) === clave(modelo.value),
    ),
);

/** Sin mayúsculas ni tildes: «modulo» encuentra «Módulo». */
const normalizar = (texto: string): string =>
    texto
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLocaleLowerCase();

const coincidentes = computed(() => {
    const termino = normalizar(busqueda.value.trim());

    if (termino === '') {
        return props.opciones;
    }

    return props.opciones.filter((opcion) =>
        normalizar(`${opcion.etiqueta} ${opcion.detalle ?? ''}`).includes(
            termino,
        ),
    );
});

const visibles = computed(() => coincidentes.value.slice(0, LIMITE_VISIBLE));

const elegir = (valor: unknown): void => {
    const elegida = props.opciones.find(
        (opcion) => clave(opcion.valor) === valor,
    );

    modelo.value = elegida?.valor ?? null;
};
</script>

<template>
    <div class="contents">
        <Combobox
            v-model:open="abierto"
            :model-value="clave(modelo)"
            :disabled="props.deshabilitado"
            ignore-filter
            @update:model-value="elegir"
        >
            <ComboboxAnchor as-child>
                <ComboboxTrigger
                    v-bind="$attrs"
                    :id="props.id"
                    :aria-invalid="props.invalido || undefined"
                    :data-valido="props.valido || undefined"
                    :class="
                        cn(
                            'flex h-9 w-full min-w-0 items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-2 text-sm whitespace-nowrap shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-campo-foco focus-visible:ring-[3px] focus-visible:ring-campo-foco/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 data-[valido=true]:border-exito data-[valido=true]:ring-exito/20 dark:bg-input/30 dark:hover:bg-input/50 dark:aria-invalid:ring-destructive/40 dark:data-[valido=true]:ring-exito/40 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=\'size-\'])]:size-4 [&_svg:not([class*=\'text-\'])]:text-muted-foreground',
                            props.class,
                        )
                    "
                >
                    <span class="flex min-w-0 items-center gap-2">
                        <component
                            :is="props.icono"
                            v-if="props.icono"
                            aria-hidden="true"
                        />
                        <span
                            class="truncate"
                            :class="
                                seleccionada
                                    ? 'text-texto'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{
                                seleccionada?.etiqueta ??
                                (modelo === null && props.nula
                                    ? props.nula
                                    : props.marcador)
                            }}
                        </span>
                    </span>
                    <ChevronDown class="size-4 opacity-50" aria-hidden="true" />
                </ComboboxTrigger>
            </ComboboxAnchor>

            <ComboboxList
                align="start"
                class="w-(--reka-combobox-trigger-width) min-w-64"
            >
                <!--
                    `display-value` vacío: sin él, reka-ui rellena el buscador
                    con el valor elegido (el id, «96») y lo que se escribe se
                    pega detrás («96factur»), así que nunca coincide nada.
                -->
                <ComboboxInput
                    v-model="busqueda"
                    :display-value="() => ''"
                    :placeholder="trans('comun.selector.buscar')"
                    :aria-label="trans('comun.selector.buscar')"
                    auto-focus
                />

                <ComboboxViewport class="max-h-72 p-1">
                    <ComboboxGroup>
                        <ComboboxItem
                            v-if="props.nula && busqueda.trim() === ''"
                            :value="NINGUNO"
                            class="text-muted-foreground"
                        >
                            {{ props.nula }}
                            <ComboboxItemIndicator>
                                <Check aria-hidden="true" />
                            </ComboboxItemIndicator>
                        </ComboboxItem>

                        <ComboboxItem
                            v-for="opcion in visibles"
                            :key="clave(opcion.valor)"
                            :value="clave(opcion.valor)"
                            :text-value="opcion.etiqueta"
                        >
                            <span class="flex min-w-0 flex-col">
                                <span class="truncate">{{
                                    opcion.etiqueta
                                }}</span>
                                <span
                                    v-if="opcion.detalle"
                                    class="truncate text-fino text-texto-suave"
                                >
                                    {{ opcion.detalle }}
                                </span>
                            </span>
                            <ComboboxItemIndicator>
                                <Check aria-hidden="true" />
                            </ComboboxItemIndicator>
                        </ComboboxItem>
                    </ComboboxGroup>

                    <p
                        v-if="coincidentes.length === 0"
                        class="px-2 py-6 text-center text-cuerpo text-texto-suave"
                    >
                        {{
                            trans('comun.selector.sin_coincidencias', {
                                termino: busqueda.trim(),
                            })
                        }}
                    </p>
                </ComboboxViewport>

                <p
                    v-if="coincidentes.length > visibles.length"
                    class="border-t border-borde px-3 py-2 text-fino text-texto-suave"
                >
                    {{
                        trans('comun.selector.recortadas', {
                            visibles: String(visibles.length),
                            total: String(coincidentes.length),
                        })
                    }}
                </p>
            </ComboboxList>
        </Combobox>

        <input
            v-if="props.name"
            type="hidden"
            :name="props.name"
            :value="modelo ?? ''"
        />
    </div>
</template>
