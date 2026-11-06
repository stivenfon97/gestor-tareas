<script setup lang="ts">
/**
 * Una entrada del historial de cambios: qué pasó, quién, cuándo y, campo a
 * campo, el valor antes y después.
 *
 * Una creación solo tiene «después» y un borrado solo «antes» (la foto de
 * cómo quedó): la columna que no existe no se pinta, en lugar de llenarse de
 * guiones.
 */
import { ArrowRight, UserRound } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { formatearFechaHora } from '@/lib/formato';
import { tonoDeEventoHistorial } from '@/lib/tonos';
import type { EntradaDeHistorial } from './tipos';
import ValorDeHistorial from './ValorDeHistorial.vue';

const props = defineProps<{ entrada: EntradaDeHistorial }>();

const tieneAntes = computed(() =>
    props.entrada.cambios.some((cambio) => cambio.anterior !== null),
);
const tieneDespues = computed(() =>
    props.entrada.cambios.some((cambio) => cambio.nuevo !== null),
);
</script>

<template>
    <article
        class="flex flex-col gap-celda rounded-lg border border-borde bg-panel p-bloque"
    >
        <header class="flex flex-wrap items-center gap-x-celda gap-y-minimo">
            <Badge :variant="tonoDeEventoHistorial(props.entrada.evento)">
                <span
                    class="size-1.5 rounded-full bg-current"
                    aria-hidden="true"
                />
                {{ $t(`enums.evento_historial.${props.entrada.evento}`) }}
            </Badge>
            <span
                class="inline-flex items-center gap-minimo text-cuerpo text-texto"
            >
                <UserRound
                    class="size-3.5 text-texto-suave"
                    aria-hidden="true"
                />
                {{ props.entrada.usuario?.name ?? $t('historial.sistema') }}
            </span>
            <time
                v-if="props.entrada.fecha"
                :datetime="props.entrada.fecha"
                class="font-mono text-fino text-texto-suave"
            >
                {{ formatearFechaHora(props.entrada.fecha) }}
            </time>
        </header>

        <p
            v-if="props.entrada.cambios.length === 0"
            class="text-cuerpo text-texto-suave"
        >
            {{ $t('historial.sin_campos') }}
        </p>

        <dl v-else class="flex flex-col divide-y divide-borde">
            <div
                v-for="cambio in props.entrada.cambios"
                :key="cambio.campo"
                class="grid gap-minimo py-corto first:pt-0 last:pb-0 sm:grid-cols-[10rem_1fr] sm:gap-celda"
            >
                <dt class="text-fino font-medium text-texto-suave">
                    {{ cambio.etiqueta ? $t(cambio.etiqueta) : cambio.campo }}
                </dt>
                <dd
                    class="flex min-w-0 flex-wrap items-baseline gap-x-corto gap-y-minimo text-cuerpo"
                >
                    <template v-if="tieneAntes && cambio.anterior">
                        <span class="sr-only" v-text="$t('historial.antes')" />
                        <span
                            :class="
                                tieneDespues
                                    ? 'line-through decoration-texto-tenue'
                                    : ''
                            "
                        >
                            <ValorDeHistorial :valor="cambio.anterior" />
                        </span>
                    </template>
                    <ArrowRight
                        v-if="cambio.anterior && cambio.nuevo"
                        class="size-3.5 shrink-0 self-center text-texto-tenue"
                        aria-hidden="true"
                    />
                    <template v-if="cambio.nuevo">
                        <span
                            class="sr-only"
                            v-text="$t('historial.despues')"
                        />
                        <span class="font-medium">
                            <ValorDeHistorial :valor="cambio.nuevo" />
                        </span>
                    </template>
                </dd>
            </div>
        </dl>
    </article>
</template>
