<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Building2, FileText, Mail, Phone, User } from '@lucide/vue';
import ClienteController from '@/actions/App/Http/Controllers/ClienteController';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import PieDeFormulario from '@/components/formularios/PieDeFormulario.vue';
import SeccionDeFormulario from '@/components/formularios/SeccionDeFormulario.vue';
import TarjetaDeFormulario from '@/components/formularios/TarjetaDeFormulario.vue';
import InputError from '@/components/InputError.vue';
import { Field, FieldDescription } from '@/components/ui/field';
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

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :icono="Building2"
            :titulo="$t('clientes.editar')"
            :descripcion="props.cliente.nombre"
        />

        <Form
            novalidate
            v-bind="ClienteController.update.form(props.cliente.id)"
            v-slot="{ errors, processing, validate, valid }"
        >
            <TarjetaDeFormulario>
                <SeccionDeFormulario
                    :titulo="$t('clientes.formulario.datos')"
                    :descripcion="$t('clientes.formulario.datos_ayuda')"
                >
                    <Field>
                        <Label for="nombre">{{ $t('comun.nombre') }}</Label>
                        <InputGroup>
                            <InputGroupInput
                                id="nombre"
                                name="nombre"
                                required
                                :default-value="props.cliente.nombre"
                                :placeholder="$t('clientes.nombre_marcador')"
                                :aria-invalid="!!errors.nombre"
                                @change="validate('nombre')"
                                :data-valido="valid('nombre')"
                            />
                            <InputGroupAddon>
                                <Building2 aria-hidden="true" />
                            </InputGroupAddon>
                        </InputGroup>
                        <InputError :message="errors.nombre" />
                    </Field>
                </SeccionDeFormulario>
                <SeccionDeFormulario
                    :titulo="$t('clientes.formulario.contacto')"
                    :descripcion="$t('clientes.formulario.contacto_ayuda')"
                >
                    <div class="grid gap-bloque md:grid-cols-2">
                        <Field>
                            <Label for="contacto_nombre">{{
                                $t('clientes.contacto_nombre')
                            }}</Label>
                            <InputGroup>
                                <InputGroupInput
                                    id="contacto_nombre"
                                    name="contacto_nombre"
                                    :default-value="
                                        props.cliente.contacto_nombre ?? ''
                                    "
                                    :placeholder="
                                        $t('clientes.contacto_nombre_marcador')
                                    "
                                    :aria-invalid="!!errors.contacto_nombre"
                                    @change="validate('contacto_nombre')"
                                    :data-valido="valid('contacto_nombre')"
                                />
                                <InputGroupAddon>
                                    <User aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>
                            <InputError :message="errors.contacto_nombre" />
                        </Field>
                        <Field>
                            <Label for="contacto_email">{{
                                $t('clientes.contacto_email')
                            }}</Label>
                            <InputGroup>
                                <InputGroupInput
                                    id="contacto_email"
                                    name="contacto_email"
                                    type="email"
                                    :default-value="
                                        props.cliente.contacto_email ?? ''
                                    "
                                    :placeholder="
                                        $t('clientes.contacto_email_marcador')
                                    "
                                    :aria-invalid="!!errors.contacto_email"
                                    @change="validate('contacto_email')"
                                    :data-valido="valid('contacto_email')"
                                />
                                <InputGroupAddon>
                                    <Mail aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>
                            <InputError :message="errors.contacto_email" />
                        </Field>
                    </div>
                    <Field>
                        <Label for="contacto_telefono">{{
                            $t('clientes.contacto_telefono')
                        }}</Label>
                        <InputGroup>
                            <InputGroupInput
                                id="contacto_telefono"
                                name="contacto_telefono"
                                :default-value="
                                    props.cliente.contacto_telefono ?? ''
                                "
                                :placeholder="
                                    $t('clientes.contacto_telefono_marcador')
                                "
                                :aria-invalid="!!errors.contacto_telefono"
                                @change="validate('contacto_telefono')"
                                :data-valido="valid('contacto_telefono')"
                            />
                            <InputGroupAddon>
                                <Phone aria-hidden="true" />
                            </InputGroupAddon>
                        </InputGroup>
                        <InputError :message="errors.contacto_telefono" />
                    </Field>
                </SeccionDeFormulario>
                <SeccionDeFormulario
                    :titulo="$t('clientes.formulario.notas')"
                    :descripcion="$t('clientes.formulario.notas_ayuda')"
                >
                    <Field>
                        <Label for="notas">{{ $t('clientes.notas') }}</Label>
                        <InputGroup>
                            <InputGroupTextarea
                                id="notas"
                                name="notas"
                                rows="4"
                                :default-value="props.cliente.notas ?? ''"
                                :placeholder="$t('clientes.notas_marcador')"
                                :aria-invalid="!!errors.notas"
                                @change="validate('notas')"
                                :data-valido="valid('notas')"
                            />
                            <InputGroupAddon align="block-start">
                                <FileText aria-hidden="true" />
                            </InputGroupAddon>
                        </InputGroup>
                        <FieldDescription>
                            {{ $t('clientes.notas_ayuda') }}
                        </FieldDescription>
                        <InputError :message="errors.notas" />
                    </Field>
                </SeccionDeFormulario>
                <template #pie>
                    <PieDeFormulario
                        :procesando="processing"
                        :cancelar="show(props.cliente.id)"
                    />
                </template>
            </TarjetaDeFormulario>
        </Form>
    </div>
</template>
