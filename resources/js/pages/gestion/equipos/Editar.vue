<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import EquipoController from '@/actions/App/Http/Controllers/Gestion/EquipoController';
import MiembroEquipoController from '@/actions/App/Http/Controllers/Gestion/MiembroEquipoController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
        <Heading
            variant="small"
            :title="$t('gestion.equipos.editar')"
            :description="$t('gestion.equipos.nota_no_otorga_permisos')"
        />

        <Form
            v-bind="EquipoController.update.form(props.equipo.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="nombre">{{ $t('comun.nombre') }}</Label>
                <Input
                    id="nombre"
                    name="nombre"
                    :default-value="props.equipo.nombre"
                    required
                />
                <InputError :message="errors.nombre" />
            </div>

            <div class="grid gap-2">
                <Label for="descripcion">{{ $t('comun.descripcion') }}</Label>
                <Input
                    id="descripcion"
                    name="descripcion"
                    :default-value="props.equipo.descripcion ?? ''"
                />
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
                v-slot="{ processing }"
            >
                <div class="grid flex-1 gap-2">
                    <Label for="usuario_id">{{ $t('comun.agregar') }}</Label>
                    <select
                        id="usuario_id"
                        name="usuario_id"
                        required
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
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
