<script setup lang="ts">
import { Eye, EyeOff, Lock } from '@lucide/vue';
import { ref, useTemplateRef } from 'vue';
import type { HTMLAttributes } from 'vue';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from '@/components/ui/input-group';

// Los atributos (`id`, `name`, `autocomplete`, `placeholder`, `required`,
// `aria-invalid`, `data-valido`, `v-model`…) van al control; `class`, al grupo,
// que es quien ocupa el sitio en el formulario.
defineOptions({ inheritAttrs: false });

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();

const verContrasena = ref(false);
const inputRef = useTemplateRef('inputRef');

defineExpose({
    $el: inputRef,
    focus: () => inputRef.value?.$el?.focus(),
});
</script>

<template>
    <InputGroup :class="props.class">
        <InputGroupInput
            ref="inputRef"
            v-bind="$attrs"
            :type="verContrasena ? 'text' : 'password'"
        />
        <InputGroupAddon>
            <Lock aria-hidden="true" />
        </InputGroupAddon>
        <InputGroupAddon align="inline-end">
            <InputGroupButton
                type="button"
                size="icon-xs"
                :aria-label="
                    $t(
                        verContrasena
                            ? 'comun.ocultar_contrasena'
                            : 'comun.mostrar_contrasena',
                    )
                "
                :aria-pressed="verContrasena"
                @click="verContrasena = !verContrasena"
            >
                <EyeOff v-if="verContrasena" aria-hidden="true" />
                <Eye v-else aria-hidden="true" />
            </InputGroupButton>
        </InputGroupAddon>
    </InputGroup>
</template>
