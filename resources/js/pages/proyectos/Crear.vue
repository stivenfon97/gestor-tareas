<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import ProyectoController from '@/actions/App/Http/Controllers/ProyectoController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { aSlug } from '@/lib/slug';
import { index } from '@/routes/proyectos';

const props = defineProps<{
    clientes: Array<{ id: number; nombre: string }>;
    estados: Array<{ valor: string; etiqueta: string }>;
}>();

const form = useForm({
    cliente_id: props.clientes[0]?.id ?? '',
    nombre: '',
    slug: '',
    prefijo_tarea: '',
    descripcion: '',
    estado: props.estados[0]?.valor ?? 'activo',
});

/*
 * El slug se sugiere mientras el usuario no lo toque. En cuanto lo edita a
 * mano, deja de seguir al nombre: reescribírselo sería perder su decisión.
 */
let slugEditadoAMano = false;

watch(
    () => form.nombre,
    (nombre) => {
        if (!slugEditadoAMano) {
            form.slug = aSlug(nombre);
        }
    },
);

const marcarSlugManual = (): void => {
    slugEditadoAMano = true;
};

const ejemploDeCodigo = computed(() => `${form.prefijo_tarea || 'ABC'}-0001`);

const enviar = (): void => {
    form.submit(ProyectoController.store());
};
</script>

<template>
    <Head :title="$t('proyectos.crear')" />

    <div class="flex max-w-xl flex-col gap-6">
        <Heading variant="small" :title="$t('proyectos.crear')" />

        <form class="space-y-6" @submit.prevent="enviar">
            <div class="grid gap-2">
                <Label for="cliente_id">{{ $t('proyectos.cliente') }}</Label>
                <select
                    id="cliente_id"
                    v-model="form.cliente_id"
                    required
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                >
                    <option
                        v-for="cliente in props.clientes"
                        :key="cliente.id"
                        :value="cliente.id"
                    >
                        {{ cliente.nombre }}
                    </option>
                </select>
                <InputError :message="form.errors.cliente_id" />
            </div>

            <div class="grid gap-2">
                <Label for="nombre">{{ $t('comun.nombre') }}</Label>
                <Input id="nombre" v-model="form.nombre" required />
                <InputError :message="form.errors.nombre" />
            </div>

            <div class="grid gap-2">
                <Label for="slug">{{ $t('proyectos.slug') }}</Label>
                <Input
                    id="slug"
                    v-model="form.slug"
                    @input="marcarSlugManual"
                />
                <p class="text-xs text-muted-foreground">
                    {{ $t('proyectos.slug_ayuda') }}
                </p>
                <InputError :message="form.errors.slug" />
            </div>

            <div class="grid gap-2">
                <Label for="prefijo_tarea">{{ $t('proyectos.prefijo') }}</Label>
                <Input
                    id="prefijo_tarea"
                    v-model="form.prefijo_tarea"
                    required
                    maxlength="10"
                />
                <p class="text-xs text-muted-foreground">
                    {{
                        $t('proyectos.prefijo_ayuda', {
                            ejemplo: ejemploDeCodigo,
                        })
                    }}
                </p>
                <!-- Se avisa antes de crear, no después: luego ya no hay vuelta atrás. -->
                <p class="text-xs font-medium text-muted-foreground">
                    {{ $t('proyectos.prefijo_inmutable_aviso') }}
                </p>
                <InputError :message="form.errors.prefijo_tarea" />
            </div>

            <div class="grid gap-2">
                <Label for="descripcion">{{
                    $t('proyectos.descripcion')
                }}</Label>
                <textarea
                    id="descripcion"
                    v-model="form.descripcion"
                    rows="4"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                />
                <InputError :message="form.errors.descripcion" />
            </div>

            <div class="grid gap-2">
                <Label for="estado">{{ $t('comun.estado') }}</Label>
                <select
                    id="estado"
                    v-model="form.estado"
                    required
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                >
                    <option
                        v-for="opcion in props.estados"
                        :key="opcion.valor"
                        :value="opcion.valor"
                    >
                        {{ $t(opcion.etiqueta) }}
                    </option>
                </select>
                <InputError :message="form.errors.estado" />
            </div>

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="form.processing">{{
                    $t('comun.guardar')
                }}</Button>
                <Button as-child variant="ghost">
                    <Link :href="index()">{{ $t('comun.cancelar') }}</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
