<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { FileText, Users, UsersRound } from '@lucide/vue';
import EquipoController from '@/actions/App/Http/Controllers/Gestion/EquipoController';
import MiembroEquipoController from '@/actions/App/Http/Controllers/Gestion/MiembroEquipoController';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import Heading from '@/components/Heading.vue';
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
import { index } from '@/routes/equipos';

type Persona = { id: number; name: string; email: string };

const props = defineProps<{
    equipo: { id: number; nombre: string; descripcion: string | null };
    miembros: Persona[];
    candidatos: Persona[];
}>();
</script>

<template>
    <Head :title="$t('gestion.equipos.editar')" />

    <div class="flex max-w-2xl flex-col gap-8">
        <CabeceraDePagina
            :icono="UsersRound"
            :titulo="$t('gestion.equipos.editar')"
            :descripcion="$t('gestion.equipos.nota_no_otorga_permisos')"
        />

        <Form
            v-bind="EquipoController.update.form(props.equipo.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="nombre">{{ $t('comun.nombre') }}</Label>
                <InputGroup>
                    <InputGroupInput
                        id="nombre"
                        name="nombre"
                        :default-value="props.equipo.nombre"
                        required
                        :placeholder="$t('gestion.equipos.nombre_marcador')"
                        :aria-invalid="!!errors.nombre"
                    />
                    <InputGroupAddon>
                        <Users aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
                <InputError :message="errors.nombre" />
            </div>

            <div class="grid gap-2">
                <Label for="descripcion">{{ $t('comun.descripcion') }}</Label>
                <InputGroup>
                    <InputGroupInput
                        id="descripcion"
                        name="descripcion"
                        :default-value="props.equipo.descripcion ?? ''"
                        :placeholder="
                            $t('gestion.equipos.descripcion_marcador')
                        "
                        :aria-invalid="!!errors.descripcion"
                    />
                    <InputGroupAddon>
                        <FileText aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
                <InputError :message="errors.descripcion" />
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

        <section class="flex flex-col gap-4">
            <Heading variant="small" :title="$t('gestion.equipos.miembros')" />

            <ul class="divide-y divide-border rounded-lg border border-border">
                <li
                    v-for="miembro in props.miembros"
                    :key="miembro.id"
                    class="flex items-center justify-between px-4 py-2 text-sm"
                >
                    <span>
                        {{ miembro.name }}
                        <span class="text-muted-foreground"
                            >· {{ miembro.email }}</span
                        >
                    </span>
                    <Form
                        v-bind="
                            MiembroEquipoController.destroy.form([
                                props.equipo.id,
                                miembro.id,
                            ])
                        "
                        v-slot="{ processing }"
                    >
                        <Button
                            variant="ghost"
                            size="sm"
                            type="submit"
                            :disabled="processing"
                        >
                            {{ $t('comun.quitar') }}
                        </Button>
                    </Form>
                </li>
                <li
                    v-if="props.miembros.length === 0"
                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    {{ $t('comun.sin_resultados') }}
                </li>
            </ul>

            <Form
                v-bind="MiembroEquipoController.store.form(props.equipo.id)"
                class="flex items-end gap-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid flex-1 gap-2">
                    <Label for="usuario_id">{{ $t('comun.agregar') }}</Label>
                    <!--
                        Arranca en la primera persona, como el <select> nativo.
                        La clave lo remonta cuando cambia la lista: si no, tras
                        agregar a alguien seguiría apuntando a quien ya no está.
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
                                    $t('gestion.equipos.persona_marcador')
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
                <Button type="submit" variant="outline" :disabled="processing">
                    {{ $t('comun.agregar') }}
                </Button>
            </Form>
        </section>
    </div>
</template>
