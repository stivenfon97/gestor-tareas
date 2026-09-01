<script setup lang="ts">
import { Rows2, Rows3, SlidersHorizontal } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { ColumnaDeTabla, Densidad } from './tipos';

/**
 * Los controles de la barra de herramientas, agrupados en un menú.
 *
 * Solo por debajo de `md`. Columnas, densidad, limpiar filtros y limpiar orden
 * son cuatro controles que en 360px no caben en una fila: o desbordan o se
 * parten en tres líneas que ocupan más que la propia tabla.
 */
const props = defineProps<{
    columnas: ColumnaDeTabla<unknown>[];
    visibles: string[];
    densidad: Densidad;
    hayFiltrosActivos: boolean;
    hayOrdenMultiple: boolean;
}>();

const emit = defineEmits<{
    alternarColumna: [id: string, visible: boolean];
    restablecerColumnas: [];
    fijarDensidad: [densidad: Densidad];
    limpiarFiltros: [];
    limpiarOrden: [];
}>();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm" class="gap-fino">
                <SlidersHorizontal class="size-4" aria-hidden="true" />
                {{ $t('comun.tabla.opciones') }}
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuSub>
                <DropdownMenuSubTrigger class="text-cuerpo">
                    {{ $t('comun.tabla.columnas') }}
                </DropdownMenuSubTrigger>
                <DropdownMenuSubContent class="w-56">
                    <DropdownMenuLabel
                        class="text-fino font-normal text-texto-suave"
                    >
                        {{ $t('comun.tabla.columnas_ayuda') }}
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuCheckboxItem
                        v-for="columna in props.columnas"
                        :key="columna.id"
                        :model-value="props.visibles.includes(columna.id)"
                        :disabled="columna.fija"
                        class="text-cuerpo"
                        @select="(evento: Event) => evento.preventDefault()"
                        @update:model-value="
                            (visible: boolean) =>
                                emit('alternarColumna', columna.id, visible)
                        "
                    >
                        {{ columna.etiqueta }}
                    </DropdownMenuCheckboxItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        class="text-cuerpo"
                        @select="emit('restablecerColumnas')"
                    >
                        {{ $t('comun.tabla.restablecer_columnas') }}
                    </DropdownMenuItem>
                </DropdownMenuSubContent>
            </DropdownMenuSub>

            <DropdownMenuSeparator />
            <DropdownMenuLabel class="text-fino font-normal text-texto-suave">
                {{ $t('comun.tabla.densidad') }}
            </DropdownMenuLabel>
            <DropdownMenuCheckboxItem
                :model-value="props.densidad === 'comoda'"
                class="gap-fino text-cuerpo"
                @select="emit('fijarDensidad', 'comoda')"
            >
                <Rows2 class="size-4" aria-hidden="true" />
                {{ $t('comun.tabla.densidad_comoda') }}
            </DropdownMenuCheckboxItem>
            <DropdownMenuCheckboxItem
                :model-value="props.densidad === 'compacta'"
                class="gap-fino text-cuerpo"
                @select="emit('fijarDensidad', 'compacta')"
            >
                <Rows3 class="size-4" aria-hidden="true" />
                {{ $t('comun.tabla.densidad_compacta') }}
            </DropdownMenuCheckboxItem>

            <template v-if="props.hayFiltrosActivos || props.hayOrdenMultiple">
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    v-if="props.hayFiltrosActivos"
                    class="text-cuerpo"
                    @select="emit('limpiarFiltros')"
                >
                    {{ $t('comun.tabla.limpiar_filtros') }}
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-if="props.hayOrdenMultiple"
                    class="text-cuerpo"
                    @select="emit('limpiarOrden')"
                >
                    {{ $t('comun.tabla.limpiar_orden') }}
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
