<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Check, User, UserMinus, Users, UsersRound } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AsignarEquipoAProyectoController from '@/actions/App/Http/Controllers/AsignarEquipoAProyectoController';
import ProyectoUsuarioController from '@/actions/App/Http/Controllers/ProyectoUsuarioController';
import BotonDeAccion from '@/components/BotonDeAccion.vue';
import EncabezadoDeSeccion from '@/components/EncabezadoDeSeccion.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import SelectorConBusqueda from '@/components/SelectorConBusqueda.vue';
import { TablaLocal } from '@/components/TablaDatos';
import type { ColumnaDeTabla } from '@/components/TablaDatos';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { trans } from '@/lib/i18n';

type Persona = { id: number; name: string; email: string };
type Miembro = Persona & { rol: string };

const props = defineProps<{
    proyecto: { id: number; nombre: string; slug: string };
    miembros: Miembro[];
    candidatos: Persona[];
    equipos: Array<{ id: number; nombre: string }>;
    roles: Array<{ valor: string; etiqueta: string }>;
}>();

/*
 * Persona y equipo crecen con el uso: van en `SelectorConBusqueda`, que es
 * controlado. Arrancan en el primero, como hacía el `<select>` nativo, y se
 * recolocan cuando la lista cambia: tras asignar a alguien, el valor no puede
 * seguir apuntando a quien ya no está entre los candidatos.
 */
const primero = (lista: Array<{ id: number }>): string | null =>
    lista[0] ? String(lista[0].id) : null;

const personaElegida = ref<string | number | null>(primero(props.candidatos));
const equipoElegido = ref<string | number | null>(primero(props.equipos));

watch(
    () => props.candidatos,
    (candidatos) => (personaElegida.value = primero(candidatos)),
);
watch(
    () => props.equipos,
    (equipos) => (equipoElegido.value = primero(equipos)),
);

const opcionesDePersona = computed(() =>
    props.candidatos.map((persona) => ({
        valor: String(persona.id),
        etiqueta: persona.name,
        detalle: persona.email,
    })),
);

const opcionesDeEquipo = computed(() =>
    props.equipos.map((equipo) => ({
        valor: String(equipo.id),
        etiqueta: equipo.nombre,
    })),
);

const columnasDeMiembros = computed((): ColumnaDeTabla<Miembro>[] => [
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
        id: 'rol',
        etiqueta: trans('comun.rol'),
        valor: (fila) => fila.rol,
        ordenPor: 'rol',
        filtro: {
            tipo: 'opciones',
            clave: 'rol',
            opciones: props.roles.map((rol) => ({
                valor: rol.valor,
                etiqueta: trans(rol.etiqueta),
            })),
        },
        ajustada: true,
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
    <Head :title="$t('proyectos.miembros.titulo')" />

    <div class="flex flex-col gap-seccion">
        <EncabezadoDeSeccion
            :icono="UsersRound"
            :titulo="$t('proyectos.miembros.titulo')"
        />

        <TablaLocal
            :filas="props.miembros"
            :columnas="columnasDeMiembros"
            :clave="(fila) => fila.id"
            :etiqueta="$t('proyectos.miembros.titulo')"
            :busqueda="$t('gestion.usuarios.buscar')"
            :vacio="{
                titulo: $t('proyectos.miembros.sin_miembros'),
                cuerpo: $t('proyectos.miembros.sin_miembros_ayuda'),
            }"
        >
            <template #celda-correo="{ fila }">
                <span class="break-all text-texto-suave">{{ fila.email }}</span>
            </template>
            <template #celda-rol="{ fila }">
                <Form
                    novalidate
                    v-bind="
                        ProyectoUsuarioController.update.form([
                            props.proyecto.id,
                            fila.id,
                        ])
                    "
                    class="flex items-center gap-fino"
                    v-slot="{ errors, processing }"
                >
                    <Label :for="`rol-${fila.id}`" class="sr-only">{{
                        $t('comun.rol')
                    }}</Label>
                    <Select name="rol" :default-value="fila.rol">
                        <SelectTrigger
                            :id="`rol-${fila.id}`"
                            size="sm"
                            class="w-40"
                            :aria-invalid="!!errors.rol"
                        >
                            <SelectValue
                                :placeholder="
                                    $t('proyectos.miembros.rol_marcador')
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
                    <BotonDeAccion
                        type="submit"
                        :disabled="processing"
                        :icono="Check"
                        tono="exito"
                        :etiqueta="$t('proyectos.miembros.guardar_rol')"
                    />
                    <InputError :message="errors.rol" />
                </Form>
            </template>
            <template #celda-acciones="{ fila }">
                <Form
                    novalidate
                    v-bind="
                        ProyectoUsuarioController.destroy.form([
                            props.proyecto.id,
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
                        :etiqueta="$t('proyectos.miembros.quitar')"
                    />
                </Form>
            </template>
        </TablaLocal>

        <section class="flex flex-col gap-3">
            <Heading
                variant="small"
                :title="$t('proyectos.miembros.asignar')"
            />

            <Form
                novalidate
                v-bind="ProyectoUsuarioController.store.form(props.proyecto.id)"
                class="flex flex-wrap items-end gap-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid flex-1 gap-2">
                    <Label for="usuario_id">{{ $t('comun.nombre') }}</Label>
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
                        :marcador="$t('proyectos.miembros.persona_marcador')"
                        :icono="User"
                        :invalido="!!errors.usuario_id"
                    />
                    <InputError :message="errors.usuario_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="rol">{{ $t('comun.rol') }}</Label>
                    <Select
                        name="rol"
                        required
                        :default-value="props.roles[0]?.valor"
                    >
                        <SelectTrigger id="rol" :aria-invalid="!!errors.rol">
                            <SelectValue
                                :placeholder="
                                    $t('proyectos.miembros.rol_marcador')
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
                    <InputError :message="errors.rol" />
                </div>
                <Button type="submit" :disabled="processing">{{
                    $t('comun.agregar')
                }}</Button>
            </Form>
        </section>

        <section class="flex flex-col gap-3">
            <Heading
                variant="small"
                :title="$t('proyectos.miembros.asignar_equipo')"
                :description="$t('proyectos.miembros.omitidos_explicacion')"
            />

            <Form
                novalidate
                v-bind="
                    AsignarEquipoAProyectoController.store.form(
                        props.proyecto.id,
                    )
                "
                class="flex flex-wrap items-end gap-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid flex-1 gap-2">
                    <Label for="equipo_id">{{
                        $t('gestion.equipos.titulo')
                    }}</Label>
                    <SelectorConBusqueda
                        id="equipo_id"
                        v-model="equipoElegido"
                        name="equipo_id"
                        :opciones="opcionesDeEquipo"
                        :marcador="$t('proyectos.miembros.equipo_marcador')"
                        :icono="Users"
                        :invalido="!!errors.equipo_id"
                    />
                    <InputError :message="errors.equipo_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="rol_equipo">{{ $t('comun.rol') }}</Label>
                    <Select
                        name="rol"
                        required
                        :default-value="props.roles[0]?.valor"
                    >
                        <SelectTrigger
                            id="rol_equipo"
                            :aria-invalid="!!errors.rol"
                        >
                            <SelectValue
                                :placeholder="
                                    $t('proyectos.miembros.rol_marcador')
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
                    <InputError :message="errors.rol" />
                </div>
                <Button type="submit" variant="outline" :disabled="processing">
                    {{ $t('comun.agregar') }}
                </Button>
            </Form>
        </section>
    </div>
</template>
