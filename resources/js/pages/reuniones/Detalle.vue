<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PanelDeAdjuntos from '@/components/archivos/PanelDeAdjuntos.vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import CapturaRapidaFormulario from '@/components/tareas/CapturaRapidaFormulario.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatearFechaHora } from '@/lib/formato';
import { trans } from '@/lib/i18n';
import { index as indiceDeReuniones } from '@/routes/proyectos/reuniones';
import { destroy, edit } from '@/routes/reuniones';
import {
    destroy as borrarAdjunto,
    store as subirAdjunto,
} from '@/routes/reuniones/adjuntos';
import { desvincular, vincular } from '@/routes/reuniones/tareas';
import {
    crear as crearTareasRapido,
    previsualizar as previsualizarTareasRapido,
} from '@/routes/reuniones/tareas/rapida';
import { show as mostrarTarea } from '@/routes/tareas';
import type { Adjunto } from '@/types/archivos';
import type { EstadoTarea, Opcion } from '@/types/tareas';

const props = defineProps<{
    proyecto: {
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
    };
    reunion: {
        id: number;
        fecha: string;
        tipo: string;
        titulo: string;
        contenido_html: string;
        acuerdos: string | null;
        visibilidad: string;
        creado_por: string | null;
        asistentes: Array<{
            id: number;
            nombre: string;
            organizacion: string | null;
            usuario: string | null;
        }>;
    };
    adjuntos: { imagenes: Adjunto[]; documentos: Adjunto[] };
    tareasVinculadas: Array<{
        id: number;
        codigo: string;
        nombre: string;
        estado: EstadoTarea;
    }>;
    tareasDisponibles: Array<{ id: number; codigo: string; nombre: string }>;
    capturaRapida: {
        opciones: { estados: Opcion[]; tipos: Opcion[]; prioridades: Opcion[] };
        periodoAbierto: number | null;
    };
    puede: {
        editar: boolean;
        eliminar: boolean;
        gestionarAdjuntosYTareas: boolean;
    };
}>();

const eliminando = ref(false);

const eliminar = (): void => {
    router.delete(destroy(props.reunion.id).url);
};

const seleccionadas = ref<number[]>([]);

const alternarSeleccion = (id: number): void => {
    const indice = seleccionadas.value.indexOf(id);

    if (indice === -1) {
        seleccionadas.value.push(id);
    } else {
        seleccionadas.value.splice(indice, 1);
    }
};

const vincularForm = useForm({ tarea_ids: [] as number[] });

const confirmarVinculo = (): void => {
    vincularForm.tarea_ids = seleccionadas.value;

    vincularForm.post(vincular(props.reunion.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            seleccionadas.value = [];
        },
    });
};

const desvincularTarea = (tareaId: number): void => {
    router.delete(desvincular([props.reunion.id, tareaId]).url, {
        preserveScroll: true,
    });
};

const adjuntosBorrarUrl = (adjunto: Adjunto): string =>
    borrarAdjunto([props.reunion.id, adjunto.id]).url;
</script>

<template>
    <Head :title="props.reunion.titulo" />

    <div class="flex max-w-3xl flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="props.reunion.titulo"
            :descripcion="props.proyecto.nombre"
        >
            <template #meta>
                <Badge variant="outline">
                    {{ trans(`enums.tipo_reunion.${props.reunion.tipo}`) }}
                </Badge>
                <Badge
                    v-if="props.reunion.visibilidad === 'cliente'"
                    variant="outline"
                >
                    {{ trans('enums.visibilidad.cliente') }}
                </Badge>
            </template>
            <template #acciones>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="indiceDeReuniones(props.proyecto.id)">{{
                        trans('comun.volver')
                    }}</Link>
                </Button>
                <Button
                    v-if="props.puede.editar"
                    as-child
                    variant="outline"
                    size="sm"
                >
                    <Link :href="edit(props.reunion.id)">{{
                        trans('comun.editar')
                    }}</Link>
                </Button>
                <Button
                    v-if="props.puede.eliminar"
                    variant="outline"
                    size="sm"
                    @click="eliminando = true"
                >
                    {{ trans('comun.eliminar') }}
                </Button>
            </template>
        </CabeceraDePagina>

        <p class="text-fino text-texto-suave">
            {{ formatearFechaHora(props.reunion.fecha) }}
            <span v-if="props.reunion.creado_por">
                · {{ props.reunion.creado_por }}
            </span>
        </p>

        <!-- Contenido enriquecido: ya saneado en el servidor con lista
             blanca (SanearHtmlDeReunion) antes de guardarse. -->
        <div
            class="contenido-enriquecido"
            v-html="props.reunion.contenido_html"
        />

        <section v-if="props.reunion.acuerdos" class="flex flex-col gap-fino">
            <h2 class="text-cuerpo font-medium text-texto">
                {{ trans('reuniones.campos.acuerdos') }}
            </h2>
            <p class="text-fino whitespace-pre-line text-texto-suave">
                {{ props.reunion.acuerdos }}
            </p>
        </section>

        <section class="flex flex-col gap-fino">
            <h2 class="text-cuerpo font-medium text-texto">
                {{ trans('reuniones.asistentes.titulo') }}
            </h2>
            <ul class="flex flex-wrap gap-fino">
                <li
                    v-for="asistente in props.reunion.asistentes"
                    :key="asistente.id"
                    class="rounded-md border border-borde bg-panel px-corto py-fino text-fino"
                >
                    <span class="text-texto">{{ asistente.nombre }}</span>
                    <span
                        v-if="asistente.organizacion"
                        class="text-texto-tenue"
                    >
                        · {{ asistente.organizacion }}
                    </span>
                </li>
            </ul>
        </section>

        <section class="flex flex-col gap-fino">
            <h2 class="text-cuerpo font-medium text-texto">
                {{ trans('archivos.coleccion.imagenes') }}
            </h2>
            <PanelDeAdjuntos
                coleccion="imagenes"
                :adjuntos="props.adjuntos.imagenes"
                :subir-url="subirAdjunto(props.reunion.id).url"
                :borrar-url="adjuntosBorrarUrl"
                :puede-subir="props.puede.gestionarAdjuntosYTareas"
                :puede-borrar="props.puede.gestionarAdjuntosYTareas"
            />
        </section>

        <section class="flex flex-col gap-fino">
            <h2 class="text-cuerpo font-medium text-texto">
                {{ trans('archivos.coleccion.documentos') }}
            </h2>
            <PanelDeAdjuntos
                coleccion="documentos"
                :adjuntos="props.adjuntos.documentos"
                :subir-url="subirAdjunto(props.reunion.id).url"
                :borrar-url="adjuntosBorrarUrl"
                :puede-subir="props.puede.gestionarAdjuntosYTareas"
                :puede-borrar="props.puede.gestionarAdjuntosYTareas"
            />
        </section>

        <!-- Tareas vinculadas: sales de la reunión con los acuerdos ya
             convertidos en trabajo, sin cambiar de pantalla. -->
        <section class="flex flex-col gap-corto">
            <h2 class="text-cuerpo font-medium text-texto">
                {{ trans('reuniones.tareas.titulo') }}
            </h2>

            <ul
                v-if="props.tareasVinculadas.length"
                class="flex flex-col gap-fino"
            >
                <li
                    v-for="tarea in props.tareasVinculadas"
                    :key="tarea.id"
                    class="flex items-center gap-corto rounded-md border border-borde bg-panel px-corto py-fino"
                >
                    <Link
                        :href="mostrarTarea(tarea.id)"
                        class="flex min-w-0 flex-1 items-center gap-fino text-texto hover:underline"
                    >
                        <span class="font-mono text-fino text-texto-suave">{{
                            tarea.codigo
                        }}</span>
                        <span class="truncate">{{ tarea.nombre }}</span>
                    </Link>
                    <Badge variant="outline">
                        {{ trans(`enums.estado_tarea.${tarea.estado}`) }}
                    </Badge>
                    <button
                        v-if="props.puede.gestionarAdjuntosYTareas"
                        type="button"
                        class="text-fino text-texto-tenue hover:text-estado-cancelada-texto"
                        :title="trans('reuniones.tareas.desvincular_confirmar')"
                        @click="desvincularTarea(tarea.id)"
                    >
                        {{ trans('comun.quitar') }}
                    </button>
                </li>
            </ul>
            <p v-else class="text-fino text-texto-tenue">
                {{ trans('reuniones.tareas.vacio') }}
            </p>

            <template v-if="props.puede.gestionarAdjuntosYTareas">
                <div
                    v-if="props.tareasDisponibles.length"
                    class="flex flex-col gap-fino"
                >
                    <h3 class="text-fino font-medium text-texto">
                        {{ trans('reuniones.tareas.vincular') }}
                    </h3>
                    <div
                        class="flex max-h-48 flex-col overflow-y-auto rounded-md border border-borde-fuerte bg-panel"
                    >
                        <label
                            v-for="tarea in props.tareasDisponibles"
                            :key="tarea.id"
                            class="flex items-center gap-fino border-b border-borde px-fino py-fino last:border-b-0 hover:bg-fila-hover"
                        >
                            <Checkbox
                                :model-value="seleccionadas.includes(tarea.id)"
                                @update:model-value="
                                    alternarSeleccion(tarea.id)
                                "
                            />
                            <span
                                class="font-mono text-fino text-texto-suave"
                                >{{ tarea.codigo }}</span
                            >
                            <span
                                class="min-w-0 flex-1 truncate text-fino text-texto"
                                >{{ tarea.nombre }}</span
                            >
                        </label>
                    </div>
                    <div>
                        <Button
                            size="sm"
                            :disabled="
                                seleccionadas.length === 0 ||
                                vincularForm.processing
                            "
                            @click="confirmarVinculo"
                        >
                            {{ trans('reuniones.tareas.vincular_confirmar') }}
                        </Button>
                    </div>
                </div>
                <p v-else class="text-fino text-texto-tenue">
                    {{ trans('reuniones.tareas.disponibles_vacio') }}
                </p>

                <details
                    class="rounded-md border border-dashed border-borde-fuerte"
                >
                    <summary
                        class="cursor-pointer px-corto py-fino text-fino font-medium text-texto"
                    >
                        {{ trans('reuniones.tareas.crear_titulo') }}
                    </summary>
                    <div class="flex flex-col gap-corto px-corto pb-corto">
                        <p class="text-fino text-texto-suave">
                            {{ trans('reuniones.tareas.crear_ayuda') }}
                        </p>
                        <CapturaRapidaFormulario
                            :opciones="props.capturaRapida.opciones"
                            :periodo-abierto="
                                props.capturaRapida.periodoAbierto
                            "
                            :previsualizar-url="
                                previsualizarTareasRapido(props.reunion.id).url
                            "
                            :store-url="crearTareasRapido(props.reunion.id).url"
                        />
                    </div>
                </details>
            </template>
        </section>

        <Dialog v-model:open="eliminando">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ props.reunion.titulo }}</DialogTitle>
                </DialogHeader>
                <p class="text-fino text-texto-suave">
                    {{ trans('reuniones.eliminar_confirmar') }}
                </p>
                <DialogFooter>
                    <Button variant="ghost" @click="eliminando = false">
                        {{ trans('comun.cancelar') }}
                    </Button>
                    <Button variant="destructive" @click="eliminar">
                        {{ trans('comun.eliminar') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
