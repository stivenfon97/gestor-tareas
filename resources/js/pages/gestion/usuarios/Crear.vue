<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import UsuarioController from '@/actions/App/Http/Controllers/Gestion/UsuarioController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/usuarios';

defineProps<{
    roles: Array<{ valor: string; etiqueta: string }>;
}>();
</script>

<template>
    <Head :title="$t('gestion.usuarios.crear')" />

    <div class="flex max-w-xl flex-col gap-6">
        <Heading variant="small" :title="$t('gestion.usuarios.crear')" />

        <Form v-bind="UsuarioController.store.form()" class="space-y-6" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="name">{{ $t('comun.nombre') }}</Label>
                <Input id="name" name="name" required autocomplete="name" />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">{{ $t('comun.correo') }}</Label>
                <Input id="email" name="email" type="email" required autocomplete="email" />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="rol_global">{{ $t('gestion.usuarios.rol_global') }}</Label>
                <select
                    id="rol_global"
                    name="rol_global"
                    required
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                >
                    <option v-for="rol in roles" :key="rol.valor" :value="rol.valor">
                        {{ $t(rol.etiqueta) }}
                    </option>
                </select>
                <InputError :message="errors.rol_global" />
            </div>

            <div class="grid gap-2">
                <Label for="password">{{ $t('gestion.usuarios.contrasena') }}</Label>
                <Input id="password" name="password" type="password" required autocomplete="new-password" />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">{{ $t('gestion.usuarios.confirmar_contrasena') }}</Label>
                <Input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="processing">{{ $t('comun.guardar') }}</Button>
                <Button as-child variant="ghost">
                    <Link :href="index()">{{ $t('comun.cancelar') }}</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
