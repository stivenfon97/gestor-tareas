<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Download, File, FileText, Image, Trash2, Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { trans } from '@/lib/i18n';
import type { Adjunto, Coleccion } from '@/types/archivos';

/**
 * Panel de adjuntos, genérico: no sabe si cuelga de un proyecto, una tarea o
 * una reunión. Cada página lo instancia con su propia `subirUrl` y su propia
 * función `borrarUrl`, igual que `TablaDatos` recibe columnas desde fuera en
 * vez de conocer el dominio.
 *
 * La subida usa el `progress` nativo de `useForm` de Inertia (barra real,
 * sin XHR a mano) y se queda en la página actual —el controlador responde
 * `back()`—, así que no compite con el resto de la navegación mientras dura.
 */
const props = defineProps<{
    coleccion: Coleccion;
    adjuntos: Adjunto[];
    subirUrl: string;
    borrarUrl: (adjunto: Adjunto) => string;
    puedeSubir: boolean;
    puedeBorrar: boolean;
}>();

const archivo = ref<HTMLInputElement>();

const subida = useForm<{ coleccion: Coleccion; archivo: File | null }>({
    coleccion: props.coleccion,
    archivo: null,
});

const alSeleccionar = (evento: Event): void => {
    const elegido = (evento.target as HTMLInputElement).files?.[0] ?? null;

    subida.archivo = elegido;

    if (elegido === null) {
        return;
    }

    subida.post(props.subirUrl, {
        preserveScroll: true,
        onSuccess: () => {
            subida.reset('archivo');

            if (archivo.value) {
                archivo.value.value = '';
            }
        },
    });
};

const porBorrar = ref<Adjunto | null>(null);
const borrado = useForm({});

const confirmarBorrado = (): void => {
    if (porBorrar.value === null) {
        return;
    }

    borrado.delete(props.borrarUrl(porBorrar.value), {
        preserveScroll: true,
        onFinish: () => {
            porBorrar.value = null;
        },
    });
};

/** `1.2 MB`, `840 KB`. Bytes crudos no dicen nada de un vistazo. */
const comoTamano = (bytes: number): string => {
    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    return `${Math.ceil(bytes / 1024)} KB`;
};

const esImagen = computed(() => props.coleccion === 'imagenes');
</script>

<template>
    <div class="flex flex-col gap-corto">
        <div v-if="props.puedeSubir" class="flex flex-col gap-fino">
            <label
                class="flex w-fit cursor-pointer items-center gap-fino rounded-md border border-dashed border-borde-fuerte px-celda py-fino text-fino text-texto-suave hover:bg-fila-hover"
            >
                <Upload class="size-4" aria-hidden="true" />
                {{ trans('archivos.subir') }}
                <input
                    ref="archivo"
                    type="file"
                    class="sr-only"
                    :disabled="subida.processing"
                    @change="alSeleccionar"
                />
            </label>
            <p class="text-micro text-texto-tenue">
                {{ trans(`archivos.limite.${props.coleccion}`) }}
            </p>
            <p v-if="subida.errors.archivo" class="text-fino text-estado-cancelada-texto">
                {{ subida.errors.archivo }}
            </p>
            <div
                v-if="subida.progress"
                class="h-1.5 w-full max-w-64 overflow-hidden rounded-full bg-panel-cabecera"
            >
                <div
                    class="h-full bg-marca transition-[width]"
                    :style="{ width: `${subida.progress.percentage ?? 0}%` }"
                />
            </div>
            <p v-if="subida.progress" class="text-micro text-texto-tenue">
                {{
                    trans('archivos.subiendo', {
                        porcentaje: String(subida.progress.percentage ?? 0),
                    })
                }}
            </p>
        </div>

        <ul v-if="props.adjuntos.length" class="flex flex-wrap gap-corto">
            <li
                v-for="adjunto in props.adjuntos"
                :key="adjunto.id"
                class="flex items-center gap-fino rounded-md border border-borde bg-panel px-fino py-fino"
            >
                <a
                    :href="adjunto.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-fino"
                >
                    <img
                        v-if="esImagen && adjunto.miniatura_url"
                        :src="adjunto.miniatura_url"
                        :alt="adjunto.nombre"
                        class="size-10 rounded-sm object-cover"
                    />
                    <Image
                        v-else-if="esImagen"
                        class="size-5 text-texto-suave"
                        aria-hidden="true"
                    />
                    <FileText
                        v-else-if="props.coleccion === 'documentos'"
                        class="size-5 text-texto-suave"
                        aria-hidden="true"
                    />
                    <File v-else class="size-5 text-texto-suave" aria-hidden="true" />

                    <span class="flex flex-col">
                        <span class="max-w-40 truncate text-fino text-texto">{{
                            adjunto.nombre
                        }}</span>
                        <span class="text-micro text-texto-tenue">{{
                            comoTamano(adjunto.tamano)
                        }}</span>
                    </span>
                </a>

                <a
                    :href="adjunto.url"
                    :title="trans('comun.descargar')"
                    :aria-label="trans('comun.descargar')"
                    class="text-texto-suave hover:text-texto"
                >
                    <Download class="size-4" aria-hidden="true" />
                </a>

                <button
                    v-if="props.puedeBorrar"
                    type="button"
                    :title="trans('archivos.eliminar_confirmar')"
                    :aria-label="trans('comun.eliminar')"
                    class="text-texto-suave hover:text-estado-cancelada-texto"
                    @click="porBorrar = adjunto"
                >
                    <Trash2 class="size-4" aria-hidden="true" />
                </button>
            </li>
        </ul>
        <p v-else class="text-fino text-texto-tenue">
            {{ trans('archivos.vacio') }}
        </p>

        <Dialog :open="porBorrar !== null" @update:open="porBorrar = null">
            <DialogContent v-if="porBorrar">
                <DialogHeader>
                    <DialogTitle>{{ porBorrar.nombre }}</DialogTitle>
                </DialogHeader>
                <p class="text-fino text-texto-suave">
                    {{ trans('archivos.eliminar_confirmar') }}
                </p>
                <DialogFooter>
                    <Button variant="ghost" @click="porBorrar = null">
                        {{ trans('comun.cancelar') }}
                    </Button>
                    <Button
                        variant="destructive"
                        :disabled="borrado.processing"
                        @click="confirmarBorrado"
                    >
                        {{ trans('comun.eliminar') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
