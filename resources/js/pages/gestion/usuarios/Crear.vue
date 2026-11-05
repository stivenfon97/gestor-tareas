<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Lock, Mail, User, Users } from '@lucide/vue';
import UsuarioController from '@/actions/App/Http/Controllers/Gestion/UsuarioController';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import PieDeFormulario from '@/components/formularios/PieDeFormulario.vue';
import SeccionDeFormulario from '@/components/formularios/SeccionDeFormulario.vue';
import TarjetaDeFormulario from '@/components/formularios/TarjetaDeFormulario.vue';
import InputError from '@/components/InputError.vue';
import { Field } from '@/components/ui/field';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index } from '@/routes/usuarios';

const props = defineProps<{
    roles: Array<{ valor: string; etiqueta: string }>;
}>();
</script>

<template>
    <Head :title="$t('gestion.usuarios.crear')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :icono="Users"
            :titulo="$t('gestion.usuarios.crear')"
        />

        <Form
            novalidate
            v-bind="UsuarioController.store.form()"
            v-slot="{ errors, processing, validate, valid }"
        >
            <TarjetaDeFormulario>
                <SeccionDeFormulario
                    :titulo="$t('gestion.usuarios.formulario.cuenta')"
                    :descripcion="$t('gestion.usuarios.formulario.cuenta_ayuda')"
                >
                    <div class="grid gap-bloque md:grid-cols-2">
                        <Field>
                            <Label for="name">{{ $t('comun.nombre') }}</Label>
                            <InputGroup>
                                <InputGroupInput
                                    id="name"
                                    name="name"
                                    required
                                    autocomplete="name"
                                    :placeholder="
                                        $t('gestion.usuarios.nombre_marcador')
                                    "
                                    :aria-invalid="!!errors.name"
                                    @change="validate('name')"
                                    :data-valido="valid('name')"
                                />
                                <InputGroupAddon>
                                    <User aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>
                            <InputError :message="errors.name" />
                        </Field>
                        <Field>
                            <Label for="email">{{ $t('comun.correo') }}</Label>
                            <InputGroup>
                                <InputGroupInput
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                    autocomplete="email"
                                    :placeholder="
                                        $t('gestion.usuarios.correo_marcador')
                                    "
                                    :aria-invalid="!!errors.email"
                                    @change="validate('email')"
                                    :data-valido="valid('email')"
                                />
                                <InputGroupAddon>
                                    <Mail aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>
                            <InputError :message="errors.email" />
                        </Field>
                    </div>
                </SeccionDeFormulario>
                <SeccionDeFormulario
                    :titulo="$t('gestion.usuarios.formulario.permisos')"
                    :descripcion="
                        $t('gestion.usuarios.formulario.permisos_ayuda')
                    "
                >
                    <Field>
                        <Label for="rol_global">{{
                            $t('gestion.usuarios.rol_global')
                        }}</Label>
                        <!-- Como el <select> nativo, arranca en la primera opción. -->
                        <Select
                            name="rol_global"
                            required
                            :default-value="props.roles[0]?.valor"
                        >
                            <SelectTrigger
                                id="rol_global"
                                class="w-full"
                                :aria-invalid="!!errors.rol_global"
                            >
                                <SelectValue
                                    :placeholder="
                                        $t(
                                            'gestion.usuarios.rol_global_marcador',
                                        )
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="rol in props.roles"
                                    :key="rol.valor"
                                    :value="rol.valor"
                                >
                                    {{ $t(rol.etiqueta) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.rol_global" />
                    </Field>
                </SeccionDeFormulario>
                <SeccionDeFormulario
                    :titulo="$t('gestion.usuarios.formulario.contrasena')"
                    :descripcion="
                        $t('gestion.usuarios.formulario.contrasena_ayuda')
                    "
                >
                    <div class="grid gap-bloque md:grid-cols-2">
                        <Field>
                            <Label for="password">{{
                                $t('gestion.usuarios.contrasena')
                            }}</Label>
                            <InputGroup>
                                <InputGroupInput
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    autocomplete="new-password"
                                    :placeholder="
                                        $t(
                                            'gestion.usuarios.contrasena_marcador',
                                        )
                                    "
                                    :aria-invalid="!!errors.password"
                                    @change="validate('password')"
                                    :data-valido="valid('password')"
                                />
                                <InputGroupAddon>
                                    <Lock aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>
                            <InputError :message="errors.password" />
                        </Field>
                        <Field>
                            <Label for="password_confirmation">{{
                                $t('gestion.usuarios.confirmar_contrasena')
                            }}</Label>
                            <InputGroup>
                                <InputGroupInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    required
                                    autocomplete="new-password"
                                    :placeholder="
                                        $t(
                                            'gestion.usuarios.confirmar_contrasena_marcador',
                                        )
                                    "
                                    :aria-invalid="
                                        !!errors.password_confirmation
                                    "
                                    @change="validate('password_confirmation')"
                                    :data-valido="
                                        valid('password_confirmation')
                                    "
                                />
                                <InputGroupAddon>
                                    <Lock aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>
                            <InputError
                                :message="errors.password_confirmation"
                            />
                        </Field>
                    </div>
                </SeccionDeFormulario>
                <template #pie>
                    <PieDeFormulario
                        :procesando="processing"
                        :cancelar="index()"
                    />
                </template>
            </TarjetaDeFormulario>
        </Form>
    </div>
</template>
