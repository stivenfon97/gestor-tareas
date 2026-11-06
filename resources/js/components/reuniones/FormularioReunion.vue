<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    Building2,
    Calendar,
    FileText,
    Plus,
    Trash2,
    Type,
    User,
} from '@lucide/vue';
import { computed } from 'vue';
import PieDeFormulario from '@/components/formularios/PieDeFormulario.vue';
import SeccionDeFormulario from '@/components/formularios/SeccionDeFormulario.vue';
import TarjetaDeFormulario from '@/components/formularios/TarjetaDeFormulario.vue';
import InputError from '@/components/InputError.vue';
import EditorEnriquecido from '@/components/reuniones/EditorEnriquecido.vue';
import SelectorConBusqueda from '@/components/SelectorConBusqueda.vue';
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
import { trans } from '@/lib/i18n';
import type { DocumentoTipTap } from '@/types/reuniones';
import type { Opcion } from '@/types/tareas';

type Asistente = {
    usuario_id: number | null;
    nombre: string;
    organizacion: string | null;
};

/** Documento TipTap vacío: un párrafo sin texto, igual que `editor.getJSON()`
 * de un editor recién montado. Nunca `null`: el backend exige un array. */
const documentoVacio = (): DocumentoTipTap => ({
    type: 'doc',
    content: [{ type: 'paragraph' }],
});

const props = defineProps<{
    proyecto: { id: number; nombre: string; prefijo_tarea: string };
    opciones: {
        tipos: Opcion[];
        visibilidades: Opcion[];
        miembros: Array<{ id: number; nombre: string }>;
    };
    reunion?: {
        id: number;
        fecha: string;
        tipo: string;
        titulo: string;
        contenido_json: DocumentoTipTap;
        contenido_html: string;
        acuerdos: string | null;
        visibilidad: string;
        asistentes: Asistente[];
    } | null;
    accionUrl: string;
}>();

/** `datetime-local` quiere `YYYY-MM-DDTHH:mm`; el servidor manda `Y-m-d H:i:s`. */
const comoFechaLocal = (fecha: string): string =>
    fecha.replace(' ', 'T').slice(0, 16);

/**
 * El momento actual, en hora **local** del navegador. `toISOString()` da UTC
 * y desplazaría la hora por defecto de una reunión nueva varias horas.
 */
const ahoraLocal = (): string => {
    const ahora = new Date();
    const doble = (n: number): string => String(n).padStart(2, '0');

    return `${ahora.getFullYear()}-${doble(ahora.getMonth() + 1)}-${doble(ahora.getDate())}T${doble(ahora.getHours())}:${doble(ahora.getMinutes())}`;
};

const fechaInicial = props.reunion
    ? comoFechaLocal(props.reunion.fecha)
    : ahoraLocal();

const form = useForm({
    fecha: fechaInicial,
    tipo: props.reunion?.tipo ?? props.opciones.tipos[0]?.valor ?? 'reunion',
    titulo: props.reunion?.titulo ?? '',
    contenido_json: props.reunion?.contenido_json ?? documentoVacio(),
    contenido_html: props.reunion?.contenido_html ?? '',
    acuerdos: props.reunion?.acuerdos ?? '',
    visibilidad: props.reunion?.visibilidad ?? 'interna',
    asistentes:
        props.reunion && props.reunion.asistentes.length > 0
            ? props.reunion.asistentes.map((asistente) => ({ ...asistente }))
            : [
                  {
                      usuario_id: null,
                      nombre: '',
                      organizacion: null,
                  } as Asistente,
              ],
})
    .withPrecognition(props.reunion ? 'put' : 'post', props.accionUrl)
    .setValidationTimeout(500);

const anadirAsistente = (): void => {
    form.asistentes.push({ usuario_id: null, nombre: '', organizacion: null });
};

const quitarAsistente = (indice: number): void => {
    form.asistentes.splice(indice, 1);
};

const alElegirMiembro = (asistente: Asistente, id: string): void => {
    const usuarioId = id === '' ? null : Number(id);

    asistente.usuario_id = usuarioId;

    // Comodidad, no obligación: si el nombre estaba vacío, se rellena con el
    // del miembro elegido; si ya lo habían escrito, no se pisa.
    if (usuarioId !== null && asistente.nombre.trim() === '') {
        asistente.nombre =
            props.opciones.miembros.find((miembro) => miembro.id === usuarioId)
                ?.nombre ?? '';
    }
};

const opcionesDeMiembro = computed(() =>
    props.opciones.miembros.map((miembro) => ({
        valor: miembro.id,
        etiqueta: miembro.nombre,
    })),
);

/** Error de una fila del repetidor: las claves `asistentes.N.campo` no son
 * literales del tipo de `form.errors`, igual que en `CapturaRapidaFormulario`. */
const errorDeAsistente = (indice: number, campo: string): string | undefined =>
    (form.errors as Record<string, string>)[`asistentes.${indice}.${campo}`];

const enviar = (): void => {
    const opciones = { preserveScroll: true };

    if (props.reunion) {
        form.put(props.accionUrl, opciones);
    } else {
        form.post(props.accionUrl, opciones);
    }
};
</script>

<template>
    <form novalidate @submit.prevent="enviar">
        <TarjetaDeFormulario>
            <SeccionDeFormulario
                :titulo="$t('reuniones.formulario.encuentro')"
                :descripcion="$t('reuniones.formulario.encuentro_ayuda')"
            >
                <div class="grid gap-corto sm:grid-cols-2">
                    <div class="grid gap-fino">
                        <Label for="reunion-fecha">{{
                            trans('reuniones.campos.fecha')
                        }}</Label>
                        <InputGroup>
                            <InputGroupInput
                                id="reunion-fecha"
                                v-model="form.fecha"
                                type="datetime-local"
                                required
                                :aria-invalid="!!form.errors.fecha"
                                @change="form.validate('fecha')"
                                :data-valido="form.valid('fecha')"
                            />
                            <InputGroupAddon>
                                <Calendar aria-hidden="true" />
                            </InputGroupAddon>
                        </InputGroup>
                        <InputError :message="form.errors.fecha" />
                    </div>

                    <div class="grid gap-fino">
                        <Label for="reunion-tipo">{{
                            trans('reuniones.campos.tipo')
                        }}</Label>
                        <Select v-model="form.tipo">
                            <SelectTrigger
                                id="reunion-tipo"
                                class="w-full"
                                :aria-invalid="!!form.errors.tipo"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="opcion in props.opciones.tipos"
                                    :key="opcion.valor"
                                    :value="opcion.valor"
                                >
                                    {{ trans(opcion.etiqueta) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.tipo" />
                    </div>
                </div>
                <div class="grid gap-fino">
                    <Label for="reunion-titulo">{{
                        trans('reuniones.campos.titulo')
                    }}</Label>
                    <InputGroup>
                        <InputGroupInput
                            id="reunion-titulo"
                            v-model="form.titulo"
                            required
                            maxlength="255"
                            :placeholder="trans('reuniones.marcadores.titulo')"
                            :aria-invalid="!!form.errors.titulo"
                            @change="form.validate('titulo')"
                            :data-valido="form.valid('titulo')"
                        />
                        <InputGroupAddon>
                            <Type aria-hidden="true" />
                        </InputGroupAddon>
                    </InputGroup>
                    <InputError :message="form.errors.titulo" />
                </div>
                <div class="grid gap-fino">
                    <Label for="reunion-visibilidad">{{
                        trans('reuniones.campos.visibilidad')
                    }}</Label>
                    <Select v-model="form.visibilidad">
                        <SelectTrigger
                            id="reunion-visibilidad"
                            :aria-invalid="!!form.errors.visibilidad"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opcion in props.opciones.visibilidades"
                                :key="opcion.valor"
                                :value="opcion.valor"
                            >
                                {{ trans(opcion.etiqueta) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.visibilidad" />
                </div>
            </SeccionDeFormulario>
            <SeccionDeFormulario
                :titulo="$t('reuniones.formulario.contenido')"
                :descripcion="$t('reuniones.formulario.contenido_ayuda')"
            >
                <div class="grid gap-fino">
                    <!--
                El editor no es un control nativo y `for` no lo alcanza: la
                etiqueta se le asocia por `aria-labelledby` (ver
                `EditorEnriquecido`).
            -->
                    <Label id="reunion-contenido-etiqueta">{{
                        trans('reuniones.campos.contenido')
                    }}</Label>
                    <EditorEnriquecido
                        v-model:json="form.contenido_json"
                        v-model:html="form.contenido_html"
                        etiquetado-por="reunion-contenido-etiqueta"
                        :invalido="
                            !!form.errors.contenido_json ||
                            !!form.errors.contenido_html
                        "
                    />
                    <InputError :message="form.errors.contenido_json" />
                    <InputError :message="form.errors.contenido_html" />
                </div>
                <div class="grid gap-fino">
                    <Label for="reunion-acuerdos">{{
                        trans('reuniones.campos.acuerdos')
                    }}</Label>
                    <InputGroup>
                        <InputGroupTextarea
                            id="reunion-acuerdos"
                            v-model="form.acuerdos"
                            rows="3"
                            :placeholder="
                                trans('reuniones.marcadores.acuerdos')
                            "
                            :aria-invalid="!!form.errors.acuerdos"
                            @change="form.validate('acuerdos')"
                            :data-valido="form.valid('acuerdos')"
                        />
                        <InputGroupAddon>
                            <FileText aria-hidden="true" />
                        </InputGroupAddon>
                    </InputGroup>
                    <InputError :message="form.errors.acuerdos" />
                </div>
            </SeccionDeFormulario>
            <SeccionDeFormulario
                :titulo="$t('reuniones.formulario.asistentes')"
                :descripcion="$t('reuniones.formulario.asistentes_ayuda')"
            >
                <section class="flex flex-col gap-corto">
                    <div class="flex items-center justify-between">
                        <Label>{{
                            trans('reuniones.asistentes.titulo')
                        }}</Label>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="anadirAsistente"
                        >
                            <Plus class="size-4" aria-hidden="true" />
                            {{ trans('reuniones.asistentes.anadir') }}
                        </Button>
                    </div>
                    <InputError :message="form.errors.asistentes" />
                    <p
                        v-if="form.asistentes.length === 0"
                        class="text-fino text-texto-tenue"
                    >
                        {{ trans('reuniones.asistentes.ninguno_ayuda') }}
                    </p>

                    <div
                        v-for="(asistente, indice) in form.asistentes"
                        :key="indice"
                        class="grid items-start gap-fino rounded-md border border-borde bg-panel p-fino sm:grid-cols-[2fr_2fr_1fr_auto]"
                    >
                        <div class="grid gap-1">
                            <Label :for="`asistente-nombre-${indice}`">{{
                                trans('reuniones.asistentes.nombre')
                            }}</Label>
                            <InputGroup>
                                <InputGroupInput
                                    :id="`asistente-nombre-${indice}`"
                                    v-model="asistente.nombre"
                                    required
                                    :placeholder="
                                        trans(
                                            'reuniones.marcadores.asistente_nombre',
                                        )
                                    "
                                    :aria-invalid="
                                        !!errorDeAsistente(indice, 'nombre')
                                    "
                                />
                                <InputGroupAddon>
                                    <User aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>
                            <InputError
                                :message="errorDeAsistente(indice, 'nombre')"
                            />
                        </div>

                        <div class="grid gap-1">
                            <Label :for="`asistente-organizacion-${indice}`">{{
                                trans('reuniones.asistentes.organizacion')
                            }}</Label>
                            <InputGroup>
                                <InputGroupInput
                                    :id="`asistente-organizacion-${indice}`"
                                    :model-value="asistente.organizacion ?? ''"
                                    :placeholder="
                                        trans(
                                            'reuniones.marcadores.organizacion',
                                        )
                                    "
                                    :aria-invalid="
                                        !!errorDeAsistente(
                                            indice,
                                            'organizacion',
                                        )
                                    "
                                    @update:model-value="
                                        (valor: string | number) =>
                                            (asistente.organizacion =
                                                String(valor) || null)
                                    "
                                />
                                <InputGroupAddon>
                                    <Building2 aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>
                            <InputError
                                :message="
                                    errorDeAsistente(indice, 'organizacion')
                                "
                            />
                        </div>

                        <div class="grid gap-1">
                            <Label :for="`asistente-usuario-${indice}`">{{
                                trans('reuniones.asistentes.usuario')
                            }}</Label>
                            <!-- Los miembros crecen con el uso: selector con búsqueda. -->
                            <SelectorConBusqueda
                                :id="`asistente-usuario-${indice}`"
                                :model-value="asistente.usuario_id"
                                :opciones="opcionesDeMiembro"
                                :marcador="
                                    trans(
                                        'reuniones.asistentes.usuario_ninguno',
                                    )
                                "
                                :nula="
                                    trans(
                                        'reuniones.asistentes.usuario_ninguno',
                                    )
                                "
                                :icono="User"
                                :invalido="
                                    !!errorDeAsistente(indice, 'usuario_id')
                                "
                                @update:model-value="
                                    (valor) =>
                                        alElegirMiembro(
                                            asistente,
                                            valor === null ? '' : String(valor),
                                        )
                                "
                            />
                            <InputError
                                :message="
                                    errorDeAsistente(indice, 'usuario_id')
                                "
                            />
                        </div>

                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            class="mt-6 justify-self-start"
                            :aria-label="trans('reuniones.asistentes.quitar')"
                            :title="trans('reuniones.asistentes.quitar')"
                            @click="quitarAsistente(indice)"
                        >
                            <Trash2 class="size-4" aria-hidden="true" />
                        </Button>
                    </div>
                </section>
            </SeccionDeFormulario>
            <template #pie>
                <PieDeFormulario :procesando="form.processing" />
            </template>
        </TarjetaDeFormulario>
    </form>
</template>
