<script setup lang="ts">
/**
 * Contenedor de todo formulario de alta o edición.
 *
 * Es el patrón de formulario por secciones de shadcn (Card + Field): una
 * tarjeta, una `SeccionDeFormulario` por cada grupo de datos que se piensan
 * juntos, separadas por una línea, y un pie fijo con las acciones.
 *
 * Por qué así y no campos sueltos uno debajo de otro: con seis campos
 * apilados no se sabe dónde acaba un tema y empieza otro, y los botones
 * quedaban flotando bajo el último campo. La tarjeta da un objeto claro, la
 * sección dice para qué sirve cada bloque y el pie deja las acciones siempre
 * en el mismo sitio.
 */
import { Card, CardFooter } from '@/components/ui/card';
</script>

<template>
    <Card class="w-full max-w-4xl gap-0 overflow-hidden py-0">
        <div class="divide-y divide-borde">
            <slot />
        </div>

        <CardFooter
            v-if="$slots.pie"
            class="border-t border-borde bg-panel-cabecera px-seccion py-bloque"
        >
            <slot name="pie" />
        </CardFooter>
    </Card>
</template>
