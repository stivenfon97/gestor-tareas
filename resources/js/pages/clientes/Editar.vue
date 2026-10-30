<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Building2, FileText, Mail, Phone, User } from '@lucide/vue';
import ClienteController from '@/actions/App/Http/Controllers/ClienteController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
    InputGroupTextarea,
} from '@/components/ui/input-group';
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
        <Heading
            variant="small"
            :title="$t('clientes.editar')"
            :description="props.cliente.nombre"
        />

        <Form
            v-bind="ClienteController.update.form(props.cliente.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="nombre">{{ $t('comun.nombre') }}</Label>
                <InputGroup>
                    <InputGroupInput
                        id="nombre"
                        name="nombre"
                        required
                        :default-value="props.cliente.nombre"
                        :placeholder="$t('clientes.nombre_marcador')"
                        :aria-invalid="!!errors.nombre"
                    />
                    <InputGroupAddon>
                        <Building2 aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
                <InputError :message="errors.nombre" />
            </div>

            <div class="grid gap-2">
                <Label for="contacto_nombre">{{
                    $t('clientes.contacto_nombre')
                }}</Label>
                <InputGroup>
                    <InputGroupInput
                        id="contacto_nombre"
                        name="contacto_nombre"
                        :default-value="props.cliente.contacto_nombre ?? ''"
                        :placeholder="$t('clientes.contacto_nombre_marcador')"
                        :aria-invalid="!!errors.contacto_nombre"
                    />
                    <InputGroupAddon>
                        <User aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
                <InputError :message="errors.contacto_nombre" />
            </div>

            <div class="grid gap-2">
                <Label for="contacto_email">{{
                    $t('clientes.contacto_email')
                }}</Label>
                <InputGroup>
                    <InputGroupInput
                        id="contacto_email"
                        name="contacto_email"
                        type="email"
                        :default-value="props.cliente.contacto_email ?? ''"
                        :placeholder="$t('clientes.contacto_email_marcador')"
                        :aria-invalid="!!errors.contacto_email"
                    />
                    <InputGroupAddon>
                        <Mail aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
                <InputError :message="errors.contacto_email" />
            </div>

            <div class="grid gap-2">
                <Label for="contacto_telefono">{{
                    $t('clientes.contacto_telefono')
                }}</Label>
                <InputGroup>
                    <InputGroupInput
                        id="contacto_telefono"
                        name="contacto_telefono"
                        :default-value="props.cliente.contacto_telefono ?? ''"
                        :placeholder="$t('clientes.contacto_telefono_marcador')"
                        :aria-invalid="!!errors.contacto_telefono"
                    />
                    <InputGroupAddon>
                        <Phone aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
                <InputError :message="errors.contacto_telefono" />
            </div>

            <div class="grid gap-2">
                <Label for="notas">{{ $t('clientes.notas') }}</Label>
                <InputGroup>
                    <InputGroupTextarea
                        id="notas"
                        name="notas"
                        rows="4"
                        :default-value="props.cliente.notas ?? ''"
                        :placeholder="$t('clientes.notas_marcador')"
                        :aria-invalid="!!errors.notas"
                    />
                    <InputGroupAddon align="block-start">
                        <FileText aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
                <p class="text-xs text-muted-foreground">
                    {{ $t('clientes.notas_ayuda') }}
                </p>
                <InputError :message="errors.notas" />
            </div>

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="processing">{{
                    $t('comun.guardar')
                }}</Button>
                <Button as-child variant="ghost">
                    <Link :href="show(props.cliente.id)">{{
                        $t('comun.cancelar')
                    }}</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
