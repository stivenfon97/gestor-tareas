<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
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
import PieDeFormulario from '@/components/formularios/PieDeFormulario.vue';
import SeccionDeFormulario from '@/components/formularios/SeccionDeFormulario.vue';
import TarjetaDeFormulario from '@/components/formularios/TarjetaDeFormulario.vue';
import InputError from '@/components/InputError.vue';
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
}).withPrecognition(ProyectoController.store());

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

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina :icono="FolderGit2" :titulo="$t('proyectos.crear')" />

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
                            <Select v-model="form.cliente_id" required>
                                <SelectTrigger
                                    id="cliente_id"
                                    class="w-full"
                                    :aria-invalid="!!form.errors.cliente_id"
                                >
                                    <SelectValue
                                        :placeholder="
                                            $t('proyectos.cliente_marcador')
                                        "
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
                                    @input="marcarSlugManual"
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
                            <InputGroup>
                                <InputGroupInput
                                    id="prefijo_tarea"
                                    v-model="form.prefijo_tarea"
                                    required
                                    maxlength="10"
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
                            <FieldDescription>
                                {{
                                    $t('proyectos.prefijo_ayuda', {
                                        ejemplo: ejemploDeCodigo,
                                    })
                                }}
                            </FieldDescription>
                            <!-- Se avisa antes de crear, no después: luego ya no hay vuelta atrás. -->
                            <p
                                class="text-xs font-medium text-muted-foreground"
                            >
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
                            <InputGroupAddon align="block-start">
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
                        :cancelar="index()"
                    />
                </template>
            </TarjetaDeFormulario>
        </form>
    </div>
</template>
