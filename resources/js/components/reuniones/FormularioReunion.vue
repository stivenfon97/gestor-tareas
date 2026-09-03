<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import EditorEnriquecido from '@/components/reuniones/EditorEnriquecido.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
});

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
    <form class="flex max-w-3xl flex-col gap-seccion" @submit.prevent="enviar">
        <div class="grid gap-corto sm:grid-cols-2">
            <div class="grid gap-fino">
                <Label for="reunion-fecha">{{
                    trans('reuniones.campos.fecha')
                }}</Label>
                <Input
                    id="reunion-fecha"
                    v-model="form.fecha"
                    type="datetime-local"
                    required
                />
                <InputError :message="form.errors.fecha" />
            </div>

            <div class="grid gap-fino">
                <Label for="reunion-tipo">{{
                    trans('reuniones.campos.tipo')
                }}</Label>
                <select
                    id="reunion-tipo"
                    v-model="form.tipo"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                >
                    <option
                        v-for="opcion in props.opciones.tipos"
                        :key="opcion.valor"
                        :value="opcion.valor"
                    >
                        {{ trans(opcion.etiqueta) }}
                    </option>
                </select>
                <InputError :message="form.errors.tipo" />
            </div>
        </div>

        <div class="grid gap-fino">
            <Label for="reunion-titulo">{{
                trans('reuniones.campos.titulo')
            }}</Label>
            <Input
                id="reunion-titulo"
                v-model="form.titulo"
                required
                maxlength="255"
            />
            <InputError :message="form.errors.titulo" />
        </div>

        <div class="grid gap-fino">
            <Label for="reunion-visibilidad">{{
                trans('reuniones.campos.visibilidad')
            }}</Label>
            <select
                id="reunion-visibilidad"
                v-model="form.visibilidad"
                class="h-9 w-fit rounded-md border border-input bg-background px-3 text-sm text-foreground"
            >
                <option
                    v-for="opcion in props.opciones.visibilidades"
                    :key="opcion.valor"
                    :value="opcion.valor"
                >
                    {{ trans(opcion.etiqueta) }}
                </option>
            </select>
            <InputError :message="form.errors.visibilidad" />
        </div>

        <div class="grid gap-fino">
            <Label>{{ trans('reuniones.campos.contenido') }}</Label>
            <EditorEnriquecido
                v-model:json="form.contenido_json"
                v-model:html="form.contenido_html"
            />
            <InputError :message="form.errors.contenido_json" />
            <InputError :message="form.errors.contenido_html" />
        </div>

        <div class="grid gap-fino">
            <Label for="reunion-acuerdos">{{
                trans('reuniones.campos.acuerdos')
            }}</Label>
            <textarea
                id="reunion-acuerdos"
                v-model="form.acuerdos"
                rows="3"
                class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
            />
            <InputError :message="form.errors.acuerdos" />
        </div>

        <section class="flex flex-col gap-corto">
            <div class="flex items-center justify-between">
                <Label>{{ trans('reuniones.asistentes.titulo') }}</Label>
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
                    <Input
                        :id="`asistente-nombre-${indice}`"
                        v-model="asistente.nombre"
                        required
                    />
                    <InputError :message="errorDeAsistente(indice, 'nombre')" />
                </div>

                <div class="grid gap-1">
                    <Label :for="`asistente-organizacion-${indice}`">{{
                        trans('reuniones.asistentes.organizacion')
                    }}</Label>
                    <Input
                        :id="`asistente-organizacion-${indice}`"
                        :model-value="asistente.organizacion ?? ''"
                        @update:model-value="
                            (valor) =>
                                (asistente.organizacion = String(valor) || null)
                        "
                    />
                </div>

                <div class="grid gap-1">
                    <Label :for="`asistente-usuario-${indice}`">{{
                        trans('reuniones.asistentes.usuario')
                    }}</Label>
                    <select
                        :id="`asistente-usuario-${indice}`"
                        :value="asistente.usuario_id ?? ''"
                        class="h-9 rounded-md border border-input bg-background px-2 text-sm text-foreground"
                        @change="
                            alElegirMiembro(
                                asistente,
                                ($event.target as HTMLSelectElement).value,
                            )
                        "
                    >
                        <option value="">
                            {{ trans('reuniones.asistentes.usuario_ninguno') }}
                        </option>
                        <option
                            v-for="miembro in props.opciones.miembros"
                            :key="miembro.id"
                            :value="miembro.id"
                        >
                            {{ miembro.nombre }}
                        </option>
                    </select>
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

        <div
            class="flex items-center gap-corto border-t border-borde pt-bloque"
        >
            <Button type="submit" :disabled="form.processing">
                {{ trans('comun.guardar') }}
            </Button>
        </div>
    </form>
</template>
