<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import AsignarEquipoAProyectoController from '@/actions/App/Http/Controllers/AsignarEquipoAProyectoController';
import ProyectoUsuarioController from '@/actions/App/Http/Controllers/ProyectoUsuarioController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type Persona = { id: number; name: string; email: string };
type Miembro = Persona & { rol: string };

const props = defineProps<{
    proyecto: { id: number; nombre: string; slug: string };
    miembros: Miembro[];
    candidatos: Persona[];
    equipos: Array<{ id: number; nombre: string }>;
    roles: Array<{ valor: string; etiqueta: string }>;
}>();
</script>

<template>
    <Head :title="$t('proyectos.miembros.titulo')" />

    <div class="flex max-w-3xl flex-col gap-8">
        <Heading
            variant="small"
            :title="$t('proyectos.miembros.titulo')"
            :description="props.proyecto.nombre"
        />

        <ul class="divide-y divide-border rounded-lg border border-border">
            <li
                v-for="miembro in props.miembros"
                :key="miembro.id"
                class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm"
            >
                <span>
                    {{ miembro.name }}
                    <span class="text-muted-foreground"
                        >· {{ miembro.email }}</span
                    >
                </span>

                <div class="flex items-center gap-2">
                    <Form
                        v-bind="
                            ProyectoUsuarioController.update.form([
                                props.proyecto.id,
                                miembro.id,
                            ])
                        "
                        class="flex items-center gap-2"
                        v-slot="{ processing }"
                    >
                        <select
                            :id="`rol-${miembro.id}`"
                            name="rol"
                            class="h-8 rounded-md border border-input bg-background px-2 text-sm text-foreground"
                        >
                            <option
                                v-for="rol in props.roles"
                                :key="rol.valor"
                                :value="rol.valor"
                                :selected="rol.valor === miembro.rol"
                            >
                                {{ $t(rol.etiqueta) }}
                            </option>
                        </select>
                        <Button
                            type="submit"
                            size="sm"
                            variant="secondary"
                            :disabled="processing"
                        >
                            {{ $t('comun.guardar') }}
                        </Button>
                    </Form>

                    <Form
                        v-bind="
                            ProyectoUsuarioController.destroy.form([
                                props.proyecto.id,
                                miembro.id,
                            ])
                        "
                        v-slot="{ processing }"
                    >
                        <Button
                            type="submit"
                            size="sm"
                            variant="ghost"
                            :disabled="processing"
                        >
                            {{ $t('comun.quitar') }}
                        </Button>
                    </Form>
                </div>
            </li>
            <li
                v-if="props.miembros.length === 0"
                class="px-4 py-6 text-center text-sm text-muted-foreground"
            >
                {{ $t('comun.sin_resultados') }}
            </li>
        </ul>

        <section class="flex flex-col gap-3">
            <Heading
                variant="small"
                :title="$t('proyectos.miembros.asignar')"
            />

            <Form
                v-bind="ProyectoUsuarioController.store.form(props.proyecto.id)"
                class="flex flex-wrap items-end gap-3"
                v-slot="{ processing }"
            >
                <div class="grid flex-1 gap-2">
                    <Label for="usuario_id">{{ $t('comun.nombre') }}</Label>
                    <select
                        id="usuario_id"
                        name="usuario_id"
                        required
                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground"
                    >
                        <option
                            v-for="persona in props.candidatos"
                            :key="persona.id"
                            :value="persona.id"
                        >
                            {{ persona.name }} · {{ persona.email }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="rol">{{ $t('comun.rol') }}</Label>
                    <select
                        id="rol"
                        name="rol"
                        required
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                    >
                        <option
                            v-for="rol in props.roles"
                            :key="rol.valor"
                            :value="rol.valor"
                        >
                            {{ $t(rol.etiqueta) }}
                        </option>
                    </select>
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
                v-bind="
                    AsignarEquipoAProyectoController.store.form(
                        props.proyecto.id,
                    )
                "
                class="flex flex-wrap items-end gap-3"
                v-slot="{ processing }"
            >
                <div class="grid flex-1 gap-2">
                    <Label for="equipo_id">{{
                        $t('gestion.equipos.titulo')
                    }}</Label>
                    <select
                        id="equipo_id"
                        name="equipo_id"
                        required
                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground"
                    >
                        <option
                            v-for="equipo in props.equipos"
                            :key="equipo.id"
                            :value="equipo.id"
                        >
                            {{ equipo.nombre }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="rol_equipo">{{ $t('comun.rol') }}</Label>
                    <select
                        id="rol_equipo"
                        name="rol"
                        required
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                    >
                        <option
                            v-for="rol in props.roles"
                            :key="rol.valor"
                            :value="rol.valor"
                        >
                            {{ $t(rol.etiqueta) }}
                        </option>
                    </select>
                </div>
                <Button
                    type="submit"
                    variant="secondary"
                    :disabled="processing"
                >
                    {{ $t('comun.agregar') }}
                </Button>
            </Form>
        </section>
    </div>
</template>
