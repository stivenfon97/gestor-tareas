<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { FileText, Users, UsersRound } from '@lucide/vue';
import EquipoController from '@/actions/App/Http/Controllers/Gestion/EquipoController';
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
import { index } from '@/routes/equipos';
</script>

<template>
    <Head :title="$t('gestion.equipos.crear')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :icono="UsersRound"
            :titulo="$t('gestion.equipos.crear')"
        />

        <Form
            novalidate
            :validation-timeout="500"
            v-bind="EquipoController.store.form()"
            v-slot="{ errors, processing, validate, valid }"
        >
            <TarjetaDeFormulario>
                <SeccionDeFormulario
                    :titulo="$t('gestion.equipos.formulario.datos')"
                    :descripcion="$t('gestion.equipos.formulario.datos_ayuda')"
                >
                    <Field>
                        <Label for="nombre">{{ $t('comun.nombre') }}</Label>
                        <InputGroup>
                            <InputGroupInput
                                id="nombre"
                                name="nombre"
                                required
                                :placeholder="
                                    $t('gestion.equipos.nombre_marcador')
                                "
                                :aria-invalid="!!errors.nombre"
                                @change="validate('nombre')"
                                :data-valido="valid('nombre')"
                            />
                            <InputGroupAddon>
                                <Users aria-hidden="true" />
                            </InputGroupAddon>
                        </InputGroup>
                        <InputError :message="errors.nombre" />
                    </Field>
                    <Field>
                        <Label for="descripcion">{{
                            $t('comun.descripcion')
                        }}</Label>
                        <InputGroup>
                            <InputGroupInput
                                id="descripcion"
                                name="descripcion"
                                :placeholder="
                                    $t('gestion.equipos.descripcion_marcador')
                                "
                                :aria-invalid="!!errors.descripcion"
                                @change="validate('descripcion')"
                                :data-valido="valid('descripcion')"
                            />
                            <InputGroupAddon>
                                <FileText aria-hidden="true" />
                            </InputGroupAddon>
                        </InputGroup>
                        <InputError :message="errors.descripcion" />
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
