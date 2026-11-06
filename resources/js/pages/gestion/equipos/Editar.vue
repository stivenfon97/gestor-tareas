<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { FileText, User, UserMinus, Users, UsersRound } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import EquipoController from '@/actions/App/Http/Controllers/Gestion/EquipoController';
import MiembroEquipoController from '@/actions/App/Http/Controllers/Gestion/MiembroEquipoController';
import BotonDeAccion from '@/components/BotonDeAccion.vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import PieDeFormulario from '@/components/formularios/PieDeFormulario.vue';
import SeccionDeFormulario from '@/components/formularios/SeccionDeFormulario.vue';
import TarjetaDeFormulario from '@/components/formularios/TarjetaDeFormulario.vue';
import Heading from '@/components/Heading.vue';
import HistorialDelRegistro from '@/components/historial/HistorialDelRegistro.vue';
import InputError from '@/components/InputError.vue';
import SelectorConBusqueda from '@/components/SelectorConBusqueda.vue';
import { TablaLocal } from '@/components/TablaDatos';
import type { ColumnaDeTabla } from '@/components/TablaDatos';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import { trans } from '@/lib/i18n';
import { index } from '@/routes/equipos';

type Persona = { id: number; name: string; email: string };

const props = defineProps<{
    puedeVerHistorial: boolean;
    equipo: { id: number; nombre: string; descripcion: string | null };
    miembros: Persona[];
    candidatos: Persona[];
}>();

/*
 * Las personas crecen con el uso: `SelectorConBusqueda`, controlado. Arranca en
 * la primera, como el `<select>` nativo, y se recoloca cuando la lista cambia:
 * tras agregar a alguien no puede seguir apuntando a quien ya no es candidato.
 */
const primeraPersona = (): string | null =>
    props.candidatos[0] ? String(props.candidatos[0].id) : null;

const personaElegida = ref<string | number | null>(primeraPersona());

watch(
    () => props.candidatos,
    () => (personaElegida.value = primeraPersona()),
);

const opcionesDePersona = computed(() =>
    props.candidatos.map((persona) => ({
        valor: String(persona.id),
        etiqueta: persona.name,
        detalle: persona.email,
    })),
);

const columnasDeMiembros = computed((): ColumnaDeTabla<Persona>[] => [
    {
        id: 'nombre',
        etiqueta: trans('comun.nombre'),
        valor: (fila) => fila.name,
        ordenPor: 'nombre',
        fija: true,
        principal: true,
    },
    {
        id: 'correo',
        etiqueta: trans('comun.correo'),
        valor: (fila) => fila.email,
        ordenPor: 'correo',
        prioritaria: true,
    },
    {
        id: 'acciones',
        etiqueta: trans('comun.acciones'),
        alineacion: 'derecha',
        ajustada: true,
        prioritaria: true,
    },
]);
</script>

<template>
    <Head :title="$t('gestion.equipos.editar')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :icono="UsersRound"
            :titulo="$t('gestion.equipos.editar')"
            :descripcion="$t('gestion.equipos.nota_no_otorga_permisos')"
        >
            <template #acciones>
                <HistorialDelRegistro
                    v-if="props.puedeVerHistorial"
                    tipo="equipo"
                    :id="props.equipo.id"
                />
            </template>
        </CabeceraDePagina>

        <Form
            novalidate
            :validation-timeout="500"
            v-bind="EquipoController.update.form(props.equipo.id)"
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
                                :default-value="props.equipo.nombre"
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
                                :default-value="props.equipo.descripcion ?? ''"
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

        <section class="flex flex-col gap-4">
            <Heading variant="small" :title="$t('gestion.equipos.miembros')" />

            <TablaLocal
                :filas="props.miembros"
                :columnas="columnasDeMiembros"
                :clave="(fila) => fila.id"
                :etiqueta="$t('gestion.equipos.miembros')"
                :busqueda="$t('gestion.usuarios.buscar')"
                :vacio="{
                    titulo: $t('gestion.equipos.sin_miembros'),
                    cuerpo: $t('gestion.equipos.sin_miembros_ayuda'),
                }"
            >
                <template #celda-correo="{ fila }">
                    <span class="break-all text-texto-suave">{{
                        fila.email
                    }}</span>
                </template>
                <template #celda-acciones="{ fila }">
                    <Form
                        novalidate
                        v-bind="
                            MiembroEquipoController.destroy.form([
                                props.equipo.id,
                                fila.id,
                            ])
                        "
                        v-slot="{ processing }"
                        class="flex justify-end"
                    >
                        <BotonDeAccion
                            type="submit"
                            :disabled="processing"
                            :icono="UserMinus"
                            tono="aviso"
                            :etiqueta="$t('gestion.equipos.retirar')"
                        />
                    </Form>
                </template>
            </TablaLocal>

            <Form
                novalidate
                v-bind="MiembroEquipoController.store.form(props.equipo.id)"
                class="flex items-end gap-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid flex-1 gap-2">
                    <Label for="usuario_id">{{ $t('comun.agregar') }}</Label>
                    <!--
                        Arranca en la primera persona y se recoloca cuando la
                        lista cambia (ver `personaElegida`). Las personas crecen
                        con el uso: selector con búsqueda, por nombre o correo.
                    -->
                    <SelectorConBusqueda
                        id="usuario_id"
                        v-model="personaElegida"
                        name="usuario_id"
                        :opciones="opcionesDePersona"
                        :marcador="$t('gestion.equipos.persona_marcador')"
                        :icono="User"
                        :invalido="!!errors.usuario_id"
                    />
                    <InputError :message="errors.usuario_id" />
                </div>
                <Button type="submit" variant="outline" :disabled="processing">
                    {{ $t('comun.agregar') }}
                </Button>
            </Form>
        </section>
    </div>
</template>
