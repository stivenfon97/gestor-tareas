<script setup lang="ts">
/**
 * Una sección de un formulario: título y para qué sirve a la izquierda, los
 * campos a la derecha. Por debajo de `md` se apila.
 *
 * Es un `FieldSet` de shadcn. El título no es un `<legend>`: un `<legend>`
 * dentro de una rejilla deja de comportarse como elemento de la rejilla (el
 * navegador lo pinta sobre el borde del fieldset), así que es un `FieldTitle`
 * y el fieldset se nombra con `aria-labelledby`.
 *
 * Para poner campos cortos en la misma fila se envuelven en
 * `<div class="grid gap-bloque md:grid-cols-2">`, o en
 * `md:grid-cols-2 lg:grid-cols-3` cuando son tres de la misma familia
 * (persona, correo y teléfono). Un texto largo va siempre a todo el ancho.
 */
import { useId } from 'vue';
import {
    FieldDescription,
    FieldGroup,
    FieldSet,
    FieldTitle,
} from '@/components/ui/field';

defineProps<{
    titulo: string;
    descripcion?: string;
}>();

const id = useId();
</script>

<template>
    <FieldSet
        :aria-labelledby="id"
        class="grid gap-bloque px-seccion py-seccion md:grid-cols-[minmax(0,14rem)_minmax(0,1fr)] md:gap-pagina lg:grid-cols-[minmax(0,18rem)_minmax(0,1fr)] lg:gap-vacio"
    >
        <div class="flex flex-col gap-fino">
            <FieldTitle :id="id" class="text-medio text-texto">
                {{ titulo }}
            </FieldTitle>
            <FieldDescription
                v-if="descripcion"
                class="text-cuerpo text-texto-suave"
            >
                {{ descripcion }}
            </FieldDescription>
        </div>

        <FieldGroup class="gap-bloque">
            <slot />
        </FieldGroup>
    </FieldSet>
</template>
