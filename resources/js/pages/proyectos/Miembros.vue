<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import AsignarEquipoAProyectoController from '@/actions/App/Http/Controllers/AsignarEquipoAProyectoController';
import ProyectoUsuarioController from '@/actions/App/Http/Controllers/ProyectoUsuarioController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

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
                        class="flex flex-wrap items-center gap-2"
                        v-slot="{ errors, processing }"
                    >
                        <Label :for="`rol-${miembro.id}`">{{
                            $t('comun.rol')
                        }}</Label>
                        <Select
                            name="rol"
                            required
                            :default-value="miembro.rol"
                        >
                            <SelectTrigger
                                :id="`rol-${miembro.id}`"
                                size="sm"
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
                        <Button
                            type="submit"
                            size="sm"
                            variant="outline"
                            :disabled="processing"
                        >
                            {{ $t('comun.guardar') }}
                        </Button>
                        <InputError class="basis-full" :message="errors.rol" />
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
                v-slot="{ errors, processing }"
            >
                <div class="grid flex-1 gap-2">
                    <Label for="usuario_id">{{ $t('comun.nombre') }}</Label>
                    <!--
                        Arranca en la primera persona, como el <select> nativo.
                        La clave lo remonta cuando cambia la lista: si no, tras
                        asignar a alguien seguiría apuntando a quien ya no está.
                    -->
                    <Select
                        :key="props.candidatos.map((p) => p.id).join()"
                        name="usuario_id"
                        required
                        :default-value="
                            props.candidatos[0]
                                ? String(props.candidatos[0].id)
                                : undefined
                        "
                    >
                        <SelectTrigger
                            id="usuario_id"
                            class="w-full"
                            :aria-invalid="!!errors.usuario_id"
                        >
                            <SelectValue
                                :placeholder="
                                    $t('proyectos.miembros.persona_marcador')
                                "
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="persona in props.candidatos"
                                :key="persona.id"
                                :value="String(persona.id)"
                            >
                                {{ persona.name }} · {{ persona.email }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
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
                    <Select
                        :key="props.equipos.map((e) => e.id).join()"
                        name="equipo_id"
                        required
                        :default-value="
                            props.equipos[0]
                                ? String(props.equipos[0].id)
                                : undefined
                        "
                    >
                        <SelectTrigger
                            id="equipo_id"
                            class="w-full"
                            :aria-invalid="!!errors.equipo_id"
                        >
                            <SelectValue
                                :placeholder="
                                    $t('proyectos.miembros.equipo_marcador')
                                "
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="equipo in props.equipos"
                                :key="equipo.id"
                                :value="String(equipo.id)"
                            >
                                {{ equipo.nombre }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
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
