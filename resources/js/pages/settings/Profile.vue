<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { Mail, User } from '@lucide/vue';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'ajustes.perfil.titulo',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head :title="$t('ajustes.perfil.titulo')" />

    <h1 class="sr-only">{{ $t('ajustes.perfil.titulo') }}</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            :title="$t('ajustes.perfil.titulo')"
            :description="$t('ajustes.perfil.descripcion')"
        />

        <Form
            novalidate
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">{{ $t('ajustes.perfil.nombre') }}</Label>
                <InputGroup>
                    <InputGroupInput
                        id="name"
                        name="name"
                        :default-value="user.name"
                        required
                        autocomplete="name"
                        :placeholder="$t('ajustes.perfil.nombre_marcador')"
                        :aria-invalid="!!errors.name"
                    />
                    <InputGroupAddon>
                        <User aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">{{ $t('ajustes.perfil.correo') }}</Label>
                <InputGroup>
                    <InputGroupInput
                        id="email"
                        type="email"
                        name="email"
                        :default-value="user.email"
                        required
                        autocomplete="username"
                        :placeholder="$t('ajustes.perfil.correo_marcador')"
                        :aria-invalid="!!errors.email"
                    />
                    <InputGroupAddon>
                        <Mail aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
                <InputError :message="errors.email" />
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                    {{ $t('ajustes.perfil.sin_verificar') }}
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        {{ $t('ajustes.perfil.reenviar') }}
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    role="status"
                    class="mt-2 text-sm font-medium text-exito"
                >
                    {{ $t('ajustes.perfil.reenviado') }}
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-profile-button"
                    >{{ $t('comun.guardar') }}</Button
                >
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
