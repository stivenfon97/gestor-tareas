<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    FileText,
    FolderGit2,
    FolderKanban,
    Link as Enlace,
    Tag,
} from '@lucide/vue';
import { computed, watch } from 'vue';
import ProyectoController from '@/actions/App/Http/Controllers/ProyectoController';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
    InputGroupTextarea,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { aSlug } from '@/lib/slug';
import { index } from '@/routes/proyectos';

const props = defineProps<{
    clientes: Array<{ id: number; nombre: string }>;
    estados: Array<{ valor: string; etiqueta: string }>;
}>();

const form = useForm({
    // Texto y no número: el `Select` de shadcn-vue trabaja con valores string.
    cliente_id: props.clientes[0] ? String(props.clientes[0].id) : '',
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
        <CabeceraDePagina :icono="FolderGit2" :titulo="$t('proyectos.crear')" />

        <form class="space-y-6" @submit.prevent="enviar">
            <div class="grid gap-2">
                <Label for="cliente_id">{{ $t('proyectos.cliente') }}</Label>
                <Select v-model="form.cliente_id" required>
                    <SelectTrigger
                        id="cliente_id"
                        class="w-full"
                        :aria-invalid="!!form.errors.cliente_id"
                    >
                        <SelectValue
                            :placeholder="$t('proyectos.cliente_marcador')"
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="cliente in props.clientes"
                            :key="cliente.id"
                            :value="String(cliente.id)"
                        >
                            {{ cliente.nombre }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.cliente_id" />
            </div>

            <div class="grid gap-2">
                <Label for="nombre">{{ $t('comun.nombre') }}</Label>
                <InputGroup>
                    <InputGroupInput
                        id="nombre"
                        v-model="form.nombre"
                        required
                        :placeholder="$t('proyectos.nombre_marcador')"
                        :aria-invalid="!!form.errors.nombre"
                    />
                    <InputGroupAddon>
                        <FolderKanban aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
                <InputError :message="form.errors.nombre" />
            </div>

            <div class="grid gap-2">
                <Label for="slug">{{ $t('proyectos.slug') }}</Label>
                <InputGroup>
                    <InputGroupInput
                        id="slug"
                        v-model="form.slug"
                        :placeholder="$t('proyectos.slug_marcador')"
                        :aria-invalid="!!form.errors.slug"
                        @input="marcarSlugManual"
                    />
                    <InputGroupAddon>
                        <Enlace aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
                <p class="text-xs text-muted-foreground">
                    {{ $t('proyectos.slug_ayuda') }}
                </p>
                <InputError :message="form.errors.slug" />
            </div>

            <div class="grid gap-2">
                <Label for="prefijo_tarea">{{ $t('proyectos.prefijo') }}</Label>
                <InputGroup>
                    <InputGroupInput
                        id="prefijo_tarea"
                        v-model="form.prefijo_tarea"
                        required
                        maxlength="10"
                        :placeholder="$t('proyectos.prefijo_marcador')"
                        :aria-invalid="!!form.errors.prefijo_tarea"
                    />
                    <InputGroupAddon>
                        <Tag aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
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
                <InputGroup>
                    <InputGroupTextarea
                        id="descripcion"
                        v-model="form.descripcion"
                        rows="4"
                        :placeholder="$t('proyectos.descripcion_marcador')"
                        :aria-invalid="!!form.errors.descripcion"
                    />
                    <InputGroupAddon align="block-start">
                        <FileText aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
                <InputError :message="form.errors.descripcion" />
            </div>

            <div class="grid gap-2">
                <Label for="estado">{{ $t('comun.estado') }}</Label>
                <Select v-model="form.estado" required>
                    <SelectTrigger
                        id="estado"
                        class="w-full"
                        :aria-invalid="!!form.errors.estado"
                    >
                        <SelectValue
                            :placeholder="$t('proyectos.estado_marcador')"
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="opcion in props.estados"
                            :key="opcion.valor"
                            :value="opcion.valor"
                        >
                            {{ $t(opcion.etiqueta) }}
                        </SelectItem>
                    </SelectContent>
                </Select>
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
