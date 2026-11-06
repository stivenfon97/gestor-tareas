<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Building2,
    FileText,
    FolderGit2,
    FolderKanban,
    Link as Enlace,
    Tag,
} from '@lucide/vue';
import ProyectoController from '@/actions/App/Http/Controllers/ProyectoController';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import PieDeFormulario from '@/components/formularios/PieDeFormulario.vue';
import SeccionDeFormulario from '@/components/formularios/SeccionDeFormulario.vue';
import TarjetaDeFormulario from '@/components/formularios/TarjetaDeFormulario.vue';
import InputError from '@/components/InputError.vue';
import SelectorConBusqueda from '@/components/SelectorConBusqueda.vue';
import { Field, FieldDescription } from '@/components/ui/field';
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
})
    .withPrecognition(ProyectoController.update(props.proyecto.id))
    .setValidationTimeout(500);

const enviar = (): void => {
    form.submit(ProyectoController.update(props.proyecto.id));
};
</script>

<template>
    <Head :title="$t('proyectos.editar')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :icono="FolderGit2"
            :titulo="$t('proyectos.editar')"
            :descripcion="props.proyecto.nombre"
        />

        <form novalidate @submit.prevent="enviar">
            <TarjetaDeFormulario>
                <SeccionDeFormulario
                    :titulo="$t('proyectos.formulario.general')"
                    :descripcion="$t('proyectos.formulario.general_ayuda')"
                >
                    <div class="grid gap-bloque md:grid-cols-2">
                        <Field>
                            <Label for="cliente_id">{{
                                $t('proyectos.cliente')
                            }}</Label>
                            <!-- Los clientes crecen con el uso: selector con búsqueda. -->
                            <SelectorConBusqueda
                                id="cliente_id"
                                :model-value="form.cliente_id"
                                :opciones="
                                    props.clientes.map((cliente) => ({
                                        valor: String(cliente.id),
                                        etiqueta: cliente.nombre,
                                    }))
                                "
                                :marcador="$t('proyectos.cliente_marcador')"
                                :icono="Building2"
                                :invalido="!!form.errors.cliente_id"
                                @update:model-value="
                                    (valor) =>
                                        (form.cliente_id = String(valor ?? ''))
                                "
                            />
                            <InputError :message="form.errors.cliente_id" />
                        </Field>
                        <Field>
                            <Label for="nombre">{{ $t('comun.nombre') }}</Label>
                            <InputGroup>
                                <InputGroupInput
                                    id="nombre"
                                    v-model="form.nombre"
                                    required
                                    :placeholder="
                                        $t('proyectos.nombre_marcador')
                                    "
                                    :aria-invalid="!!form.errors.nombre"
                                    @change="form.validate('nombre')"
                                    :data-valido="form.valid('nombre')"
                                />
                                <InputGroupAddon>
                                    <FolderKanban aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>
                            <InputError :message="form.errors.nombre" />
                        </Field>
                    </div>
                </SeccionDeFormulario>
                <SeccionDeFormulario
                    :titulo="$t('proyectos.formulario.identificacion')"
                    :descripcion="
                        $t('proyectos.formulario.identificacion_ayuda')
                    "
                >
                    <div class="grid gap-bloque md:grid-cols-2">
                        <Field>
                            <Label for="slug">{{ $t('proyectos.slug') }}</Label>
                            <InputGroup>
                                <InputGroupInput
                                    id="slug"
                                    v-model="form.slug"
                                    :placeholder="$t('proyectos.slug_marcador')"
                                    :aria-invalid="!!form.errors.slug"
                                    @change="form.validate('slug')"
                                    :data-valido="form.valid('slug')"
                                />
                                <InputGroupAddon>
                                    <Enlace aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>
                            <FieldDescription>
                                {{ $t('proyectos.slug_ayuda') }}
                            </FieldDescription>
                            <InputError :message="form.errors.slug" />
                        </Field>
                        <Field>
                            <Label for="prefijo_tarea">{{
                                $t('proyectos.prefijo')
                            }}</Label>
                            <InputGroup
                                :data-disabled="
                                    props.proyecto.prefijo_bloqueado ||
                                    undefined
                                "
                            >
                                <InputGroupInput
                                    id="prefijo_tarea"
                                    v-model="form.prefijo_tarea"
                                    required
                                    maxlength="10"
                                    :disabled="props.proyecto.prefijo_bloqueado"
                                    :placeholder="
                                        $t('proyectos.prefijo_marcador')
                                    "
                                    :aria-invalid="!!form.errors.prefijo_tarea"
                                    @change="form.validate('prefijo_tarea')"
                                    :data-valido="form.valid('prefijo_tarea')"
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
                        </Field>
                    </div>
                </SeccionDeFormulario>
                <SeccionDeFormulario
                    :titulo="$t('proyectos.formulario.detalle')"
                    :descripcion="$t('proyectos.formulario.detalle_ayuda')"
                >
                    <Field>
                        <Label for="descripcion">{{
                            $t('proyectos.descripcion')
                        }}</Label>
                        <InputGroup>
                            <InputGroupTextarea
                                id="descripcion"
                                v-model="form.descripcion"
                                rows="4"
                                :placeholder="
                                    $t('proyectos.descripcion_marcador')
                                "
                                :aria-invalid="!!form.errors.descripcion"
                                @change="form.validate('descripcion')"
                                :data-valido="form.valid('descripcion')"
                            />
                            <InputGroupAddon>
                                <FileText aria-hidden="true" />
                            </InputGroupAddon>
                        </InputGroup>
                        <InputError :message="form.errors.descripcion" />
                    </Field>
                    <Field>
                        <Label for="estado">{{ $t('comun.estado') }}</Label>
                        <Select v-model="form.estado" required>
                            <SelectTrigger
                                id="estado"
                                class="w-full"
                                :aria-invalid="!!form.errors.estado"
                            >
                                <SelectValue
                                    :placeholder="
                                        $t('proyectos.estado_marcador')
                                    "
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
                    </Field>
                </SeccionDeFormulario>
                <template #pie>
                    <PieDeFormulario
                        :procesando="form.processing"
                        :cancelar="show(props.proyecto.id)"
                    />
                </template>
            </TarjetaDeFormulario>
        </form>
    </div>
</template>
