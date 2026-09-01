<script setup lang="ts" generic="T extends Record<string, unknown>">
import { ChevronDown } from '@lucide/vue';
import { ref } from 'vue';
import { cn } from '@/lib/utils';
import type { ColumnaDeTabla } from './tipos';

/**
 * Una fila del listado, apilada como tarjeta.
 *
 * Es lo que sustituye a la tabla por debajo de `md`. Mantener seis columnas en
 * 360px con desplazamiento horizontal produce una tabla que nadie desplaza: se
 * ve la primera columna y media segunda, y el resto no existe.
 *
 * Qué se ve sin desplegar lo decide el listado, no este componente. Las
 * columnas `principal` van arriba **sin etiqueta**, una por línea: son lo que
 * identifica la fila, y ponerle «Nombre:» delante al nombre solo gasta ancho.
 * Las `prioritaria` van como pares etiqueta/valor, y todo lo demás queda tras
 * «ver más», que es un `<details>` de verdad —así se puede buscar en la página
 * y funciona sin JavaScript.
 */
const props = defineProps<{
    fila: T;
    principales: ColumnaDeTabla<T>[];
    prioritarias: ColumnaDeTabla<T>[];
    restantes: ColumnaDeTabla<T>[];
    seleccionable: boolean;
    seleccionada: boolean;
    etiquetaSeleccion: string;
    verMas: string;
}>();

const emit = defineEmits<{ alternarSeleccion: [] }>();

const desplegada = ref(false);
</script>

<template>
    <article
        :class="
            cn(
                'flex flex-col gap-corto border-t border-borde bg-panel px-celda py-corto first:border-t-0',
                props.seleccionada && 'bg-fila-activa',
            )
        "
    >
        <div class="flex items-start gap-corto">
            <input
                v-if="props.seleccionable"
                type="checkbox"
                class="mt-fino size-3.5 shrink-0 accent-marca"
                :aria-label="props.etiquetaSeleccion"
                :checked="props.seleccionada"
                @change="emit('alternarSeleccion')"
            />

            <div class="flex min-w-0 flex-1 flex-col gap-minimo">
                <div
                    v-for="(columna, posicion) in props.principales"
                    :key="columna.id"
                    :class="
                        cn(
                            'min-w-0 text-cuerpo',
                            // La primera identifica; las siguientes describen.
                            posicion === 0
                                ? 'font-medium text-texto'
                                : 'text-texto',
                            columna.mono && 'font-mono text-fino',
                        )
                    "
                >
                    <slot
                        :name="`celda-${columna.id}`"
                        :fila="props.fila"
                        :valor="columna.valor?.(props.fila) ?? null"
                    >
                        {{ columna.valor?.(props.fila) ?? '' }}
                    </slot>
                </div>
            </div>
        </div>

        <!--
            Etiqueta a la izquierda con ancho fijo y valor alineado a la
            izquierda: con el valor a la derecha, un texto largo se parte en
            líneas escalonadas contra el borde y se vuelve ilegible.
        -->
        <dl
            v-if="props.prioritarias.length"
            class="grid grid-cols-[5rem_1fr] gap-x-corto gap-y-fino"
        >
            <template v-for="columna in props.prioritarias" :key="columna.id">
                <dt class="pt-[2px] text-micro text-texto-suave">
                    {{ columna.etiqueta }}
                </dt>
                <dd
                    :class="
                        cn(
                            'min-w-0 text-cuerpo text-texto',
                            columna.mono && 'font-mono text-fino',
                        )
                    "
                >
                    <slot
                        :name="`celda-${columna.id}`"
                        :fila="props.fila"
                        :valor="columna.valor?.(props.fila) ?? null"
                    >
                        {{ columna.valor?.(props.fila) ?? '—' }}
                    </slot>
                </dd>
            </template>
        </dl>

        <details
            v-if="props.restantes.length"
            @toggle="desplegada = ($event.target as HTMLDetailsElement).open"
        >
            <summary
                class="flex cursor-pointer items-center gap-fino text-fino text-texto-suave marker:content-none"
            >
                <ChevronDown
                    class="size-3.5 transition-transform"
                    :class="desplegada && 'rotate-180'"
                    aria-hidden="true"
                />
                {{ props.verMas }}
            </summary>

            <dl
                class="mt-corto grid grid-cols-[5rem_1fr] gap-x-corto gap-y-fino"
            >
                <template v-for="columna in props.restantes" :key="columna.id">
                    <dt class="pt-[2px] text-micro text-texto-suave">
                        {{ columna.etiqueta }}
                    </dt>
                    <dd
                        :class="
                            cn(
                                'min-w-0 text-cuerpo text-texto',
                                columna.mono && 'font-mono text-fino',
                            )
                        "
                    >
                        <slot
                            :name="`celda-${columna.id}`"
                            :fila="props.fila"
                            :valor="columna.valor?.(props.fila) ?? null"
                        >
                            {{ columna.valor?.(props.fila) ?? '—' }}
                        </slot>
                    </dd>
                </template>
            </dl>
        </details>
    </article>
</template>
