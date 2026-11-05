<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Mail, User, Users } from '@lucide/vue';
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
    usuario: {
        id: number;
        name: string;
        email: string;
        activo: boolean;
        rol_global: string | null;
    };
    roles: Array<{ valor: string; etiqueta: string }>;
}>();
</script>

<template>
    <Head :title="$t('gestion.usuarios.editar')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :icono="Users"
            :titulo="$t('gestion.usuarios.editar')"
            :descripcion="props.usuario.email"
        />

        <Form
            novalidate
            :validation-timeout="500"
            v-bind="UsuarioController.update.form(props.usuario.id)"
            v-slot="{ errors, processing, validate, valid }"
        >
            <TarjetaDeFormulario>
                <SeccionDeFormulario
                    :titulo="$t('gestion.usuarios.formulario.cuenta')"
                    :descripcion="
                        $t('gestion.usuarios.formulario.cuenta_ayuda')
                    "
                >
                    <div class="grid gap-bloque md:grid-cols-2">
                        <Field>
                            <Label for="name">{{ $t('comun.nombre') }}</Label>
                            <InputGroup>
                                <InputGroupInput
                                    id="name"
                                    name="name"
                                    :default-value="props.usuario.name"
                                    required
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
                                    :default-value="props.usuario.email"
                                    required
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
                        <!-- Sin rol guardado, como el <select> nativo: la primera opción. -->
                        <Select
                            name="rol_global"
                            required
                            :default-value="
                                props.usuario.rol_global ??
                                props.roles[0]?.valor
                            "
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
