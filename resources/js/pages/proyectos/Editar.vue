<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    FileText,
    FolderGit2,
    FolderKanban,
    Link as Enlace,
    Tag,
} from '@lucide/vue';
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
import { show } from '@/routes/proyectos';

const props = defineProps<{
    proyecto: {
        id: number;
        cliente_id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
        descripcion: string | null;
        estado: string;
        prefijo_bloqueado: boolean;
    };
    clientes: Array<{ id: number; nombre: string }>;
    estados: Array<{ valor: string; etiqueta: string }>;
}>();

const form = useForm({
    // Texto y no número: el `Select` de shadcn-vue trabaja con valores string.
    cliente_id: String(props.proyecto.cliente_id),
    nombre: props.proyecto.nombre,
    slug: props.proyecto.slug,
    prefijo_tarea: props.proyecto.prefijo_tarea,
    descripcion: props.proyecto.descripcion ?? '',
    estado: props.proyecto.estado,
});

const enviar = (): void => {
    form.submit(ProyectoController.update(props.proyecto.id));
};
</script>

<template>
    <Head :title="$t('proyectos.editar')" />

    <div class="flex max-w-xl flex-col gap-6">
        <CabeceraDePagina
            :icono="FolderGit2"
            :titulo="$t('proyectos.editar')"
            :descripcion="props.proyecto.nombre"
        />

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
                <InputGroup
                    :data-disabled="
                        props.proyecto.prefijo_bloqueado || undefined
                    "
                >
                    <InputGroupInput
                        id="prefijo_tarea"
                        v-model="form.prefijo_tarea"
                        required
                        maxlength="10"
                        :disabled="props.proyecto.prefijo_bloqueado"
                        :placeholder="$t('proyectos.prefijo_marcador')"
                        :aria-invalid="!!form.errors.prefijo_tarea"
                    />
                    <InputGroupAddon>
                        <Tag aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
                <!--
                    Deshabilitar el campo no es la cerradura: `ProyectoObserver`
                    aborta el guardado y `ProyectoRequest` devuelve un 422.
                    Esto solo explica el porqué donde se ve.
                -->
                <p
                    v-if="props.proyecto.prefijo_bloqueado"
                    class="text-xs font-medium text-muted-foreground"
                >
                    {{ $t('proyectos.prefijo_bloqueado') }}
                </p>
                <p v-else class="text-xs text-muted-foreground">
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
                    <Link :href="show(props.proyecto.id)">{{
                        $t('comun.cancelar')
                    }}</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
