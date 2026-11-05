<script setup lang="ts">
/**
 * Acción de una fila, una tarjeta o una cabecera: **un icono con color y con
 * globo de ayuda**, nunca un texto suelto.
 *
 * El color dice qué tipo de cosa va a pasar antes de leer nada, y se toma de
 * los tonos del tema (`tono-*-solido` en reposo, `tono-*-fondo` al pasar el
 * ratón), nunca de la marca: el esmeralda es de la
 * acción primaria de la pantalla.
 *
 * | Tono      | Para                                   | Icono típico            |
 * |-----------|----------------------------------------|-------------------------|
 * | `neutro`  | Ver, abrir, copiar                     | `Eye`, `Copy`           |
 * | `info`    | Editar, ir a un listado relacionado    | `Pencil`, `ListChecks`  |
 * | `exito`   | Activar, aprobar, reabrir              | `Power`, `UserCheck`    |
 * | `aviso`   | Desactivar, pausar, archivar           | `PowerOff`, `UserX`     |
 * | `peligro` | Eliminar (con confirmación)            | `Trash2`                |
 *
 * El rótulo es obligatorio: es el `aria-label` y el texto del globo, así que
 * un icono sin rótulo no existe. Con `href` se pinta un enlace de Inertia; sin
 * él, un botón (`type="submit"` para usarlo dentro de un `<Form>`).
 */
import type { UrlMethodPair } from '@inertiajs/core';
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

export type TonoDeAccion = 'neutro' | 'info' | 'exito' | 'aviso' | 'peligro';

const props = withDefaults(
    defineProps<{
        etiqueta: string;
        icono: Component;
        tono?: TonoDeAccion;
        href?: string | UrlMethodPair;
        type?: 'button' | 'submit';
        disabled?: boolean;
    }>(),
    {
        tono: 'neutro',
        href: undefined,
        type: 'button',
        disabled: false,
    },
);

defineEmits<{ click: [evento: MouseEvent] }>();

// Clases enteras y estáticas: el escáner de Tailwind lee el código como texto.
const clasesPorTono: Record<TonoDeAccion, string> = {
    neutro: 'text-tono-neutro-solido hover:bg-fila-activa hover:text-texto',
    info: 'text-tono-info-solido hover:bg-tono-info-fondo hover:text-tono-info-texto',
    exito: 'text-tono-exito-solido hover:bg-tono-exito-fondo hover:text-tono-exito-texto',
    aviso: 'text-tono-aviso-solido hover:bg-tono-aviso-fondo hover:text-tono-aviso-texto',
    peligro:
        'text-tono-peligro-solido hover:bg-tono-peligro-fondo hover:text-tono-peligro-texto',
};
</script>

<template>
    <TooltipProvider :delay-duration="150">
        <Tooltip>
            <TooltipTrigger as-child>
                <Button
                    v-if="props.href !== undefined"
                    as-child
                    variant="ghost"
                    size="icon-sm"
                    :class="cn('size-7', clasesPorTono[props.tono])"
                >
                    <Link :href="props.href" :aria-label="props.etiqueta">
                        <component
                            :is="props.icono"
                            class="size-4"
                            aria-hidden="true"
                        />
                    </Link>
                </Button>
                <Button
                    v-else
                    variant="ghost"
                    size="icon-sm"
                    :type="props.type"
                    :disabled="props.disabled"
                    :aria-label="props.etiqueta"
                    :class="cn('size-7', clasesPorTono[props.tono])"
                    @click="$emit('click', $event)"
                >
                    <component
                        :is="props.icono"
                        class="size-4"
                        aria-hidden="true"
                    />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{{ props.etiqueta }}</TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
