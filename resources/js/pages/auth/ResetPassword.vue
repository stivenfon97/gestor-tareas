<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Mail } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'auth.restablecer.titulo',
        description: 'auth.restablecer.descripcion',
    },
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const inputEmail = ref(props.email);

// Solo para el estado de éxito de «Repite la contraseña»: se lee del evento
// `input` y no con `v-model`, porque el `reset` del `<Form>` de Inertia
// escribe en el DOM sin avisar a Vue y un `v-model` volvería a rellenarlo.
const nueva = ref('');
const repetida = ref('');
const coinciden = computed(
    () => repetida.value !== '' && repetida.value === nueva.value,
);

const valorDe = (evento: Event) => (evento.target as HTMLInputElement).value;

const limpiar = () => {
    nueva.value = '';
    repetida.value = '';
};
</script>

<template>
    <Head :title="$t('auth.restablecer.titulo')" />

    <Form
        novalidate
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        @success="limpiar"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">{{ $t('auth.acceso.correo') }}</Label>
                <InputGroup>
                    <InputGroupInput
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        v-model="inputEmail"
                        readonly
                        :aria-invalid="!!errors.email"
                    />
                    <InputGroupAddon>
                        <Mail aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">{{
                    $t('auth.restablecer.contrasena')
                }}</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    autofocus
                    :placeholder="$t('auth.restablecer.contrasena')"
                    :passwordrules="passwordRules"
                    :aria-invalid="!!errors.password"
                    @input="nueva = valorDe($event)"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">{{
                    $t('auth.restablecer.confirmar')
                }}</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    :placeholder="$t('auth.restablecer.confirmar')"
                    :passwordrules="passwordRules"
                    :aria-invalid="!!errors.password_confirmation"
                    :data-valido="coinciden && !errors.password_confirmation"
                    @input="repetida = valorDe($event)"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :disabled="processing"
                data-test="reset-password-button"
            >
                <Spinner v-if="processing" />
                {{ $t('auth.restablecer.guardar') }}
            </Button>
        </div>
    </Form>
</template>
