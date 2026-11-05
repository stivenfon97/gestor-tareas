<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Mail, User, Users } from '@lucide/vue';
import UsuarioController from '@/actions/App/Http/Controllers/Gestion/UsuarioController';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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

    <div class="flex max-w-xl flex-col gap-6">
        <CabeceraDePagina
            :icono="Users"
            :titulo="$t('gestion.usuarios.editar')"
            :descripcion="props.usuario.email"
        />

        <Form
            v-bind="UsuarioController.update.form(props.usuario.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">{{ $t('comun.nombre') }}</Label>
                <InputGroup>
                    <InputGroupInput
                        id="name"
                        name="name"
                        :default-value="props.usuario.name"
                        required
                        :placeholder="$t('gestion.usuarios.nombre_marcador')"
                        :aria-invalid="!!errors.name"
                    />
                    <InputGroupAddon>
                        <User aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">{{ $t('comun.correo') }}</Label>
                <InputGroup>
                    <InputGroupInput
                        id="email"
                        name="email"
                        type="email"
                        :default-value="props.usuario.email"
                        required
                        :placeholder="$t('gestion.usuarios.correo_marcador')"
                        :aria-invalid="!!errors.email"
                    />
                    <InputGroupAddon>
                        <Mail aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="rol_global">{{
                    $t('gestion.usuarios.rol_global')
                }}</Label>
                <!-- Sin rol guardado, como el <select> nativo: la primera opción. -->
                <Select
                    name="rol_global"
                    required
                    :default-value="
                        props.usuario.rol_global ?? props.roles[0]?.valor
                    "
                >
                    <SelectTrigger
                        id="rol_global"
                        class="w-full"
                        :aria-invalid="!!errors.rol_global"
                    >
                        <SelectValue
                            :placeholder="
                                $t('gestion.usuarios.rol_global_marcador')
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
            </div>

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="processing">{{
                    $t('comun.guardar')
                }}</Button>
                <Button as-child variant="ghost">
                    <Link :href="index()">{{ $t('comun.cancelar') }}</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
