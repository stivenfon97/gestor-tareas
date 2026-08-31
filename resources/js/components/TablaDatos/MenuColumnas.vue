<script setup lang="ts">
import { Columns3 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { ColumnaDeTabla } from './tipos';

/**
 * Menú de visibilidad de columnas.
 *
 * Las columnas marcadas como `fija` salen deshabilitadas en vez de ocultarse
 * del menú: si desaparecieran, la lista de aquí no coincidiría con la de la
 * tabla y quien busca «Código» pensaría que se ha perdido.
 */
const props = defineProps<{
    columnas: ColumnaDeTabla<unknown>[];
    visibles: string[];
}>();

const emit = defineEmits<{
    alternar: [id: string, visible: boolean];
    restablecer: [];
}>();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm" class="gap-fino">
                <Columns3 class="size-4" aria-hidden="true" />
                {{ $t('comun.tabla.columnas') }}
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuLabel class="text-fino font-normal text-texto-suave">
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
                    (visible: boolean) => emit('alternar', columna.id, visible)
                "
            >
                {{ columna.etiqueta }}
            </DropdownMenuCheckboxItem>

            <DropdownMenuSeparator />
            <DropdownMenuItem class="text-cuerpo" @select="emit('restablecer')">
                {{ $t('comun.tabla.restablecer_columnas') }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
