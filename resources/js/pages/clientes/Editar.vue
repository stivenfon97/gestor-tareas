<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import ClienteController from '@/actions/App/Http/Controllers/ClienteController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { show } from '@/routes/clientes';

const props = defineProps<{
    cliente: {
        id: number;
        nombre: string;
        contacto_nombre: string | null;
        contacto_email: string | null;
        contacto_telefono: string | null;
        notas: string | null;
        activo: boolean;
    };
}>();
</script>

<template>
    <Head :title="$t('clientes.editar')" />

    <div class="flex max-w-xl flex-col gap-6">
        <Heading variant="small" :title="$t('clientes.editar')" :description="props.cliente.nombre" />

        <Form
            v-bind="ClienteController.update.form(props.cliente.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="nombre">{{ $t('comun.nombre') }}</Label>
                <Input id="nombre" name="nombre" required :default-value="props.cliente.nombre" />
                <InputError :message="errors.nombre" />
            </div>

            <div class="grid gap-2">
                <Label for="contacto_nombre">{{ $t('clientes.contacto_nombre') }}</Label>
                <Input
                    id="contacto_nombre"
                    name="contacto_nombre"
                    :default-value="props.cliente.contacto_nombre ?? ''"
                />
                <InputError :message="errors.contacto_nombre" />
            </div>

            <div class="grid gap-2">
                <Label for="contacto_email">{{ $t('clientes.contacto_email') }}</Label>
                <Input
                    id="contacto_email"
                    name="contacto_email"
                    type="email"
                    :default-value="props.cliente.contacto_email ?? ''"
                />
                <InputError :message="errors.contacto_email" />
            </div>

            <div class="grid gap-2">
                <Label for="contacto_telefono">{{ $t('clientes.contacto_telefono') }}</Label>
                <Input
                    id="contacto_telefono"
                    name="contacto_telefono"
                    :default-value="props.cliente.contacto_telefono ?? ''"
                />
                <InputError :message="errors.contacto_telefono" />
            </div>

            <div class="grid gap-2">
                <Label for="notas">{{ $t('clientes.notas') }}</Label>
                <textarea
                    id="notas"
                    name="notas"
                    rows="4"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                    :value="props.cliente.notas ?? ''"
                />
                <p class="text-xs text-muted-foreground">{{ $t('clientes.notas_ayuda') }}</p>
                <InputError :message="errors.notas" />
            </div>

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="processing">{{ $t('comun.guardar') }}</Button>
                <Button as-child variant="ghost">
                    <Link :href="show(props.cliente.id)">{{ $t('comun.cancelar') }}</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
