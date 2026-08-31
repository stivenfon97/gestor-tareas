<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/proyectos/tareas';
import { previsualizar, store } from '@/routes/proyectos/tareas/rapida';
import type { FilaCapturaRapida, Opcion } from '@/types/tareas';

const props = defineProps<{
    proyecto: { id: number; nombre: string; prefijo_tarea: string };
    opciones: { estados: Opcion[]; tipos: Opcion[]; prioridades: Opcion[] };
    periodoAbierto: number | null;
}>();

const texto = ref('');
const filas = ref<FilaCapturaRapida[]>([]);

const previsualizacion = useForm({ texto: '' });

/**
 * Previsualizar **no escribe nada**: el servidor devuelve lo que se crearía y
 * aquí se edita antes de confirmar. Crear directamente desde el texto pegado
 * convertiría cada errata del parser en una tarea que borrar a mano.
 */
const pedirPrevisualizacion = (): void => {
    previsualizacion.texto = texto.value;

    previsualizacion.post(previsualizar(props.proyecto.id).url, {
        preserveScroll: true,
        onFlash: (flash) => {
            const propuestas = (flash.previsualizacion ?? []) as FilaCapturaRapida[];

            filas.value = propuestas.map((fila) => ({
                ...fila,
                // El parser no adivina el tipo: se elige en la previsualización.
                tipo: 'desarrollo',
                prioridad: null,
                motivo_espera: null,
                motivo_cancelacion: null,
            }));
        },
    });
};

type FilaConfirmada = {
    nombre: string;
    descripcion: string | null;
    estado: string;
    tipo: string;
    prioridad: string | null;
    modulo_id: number | null;
    modulo_nuevo: string | null;
    motivo_espera: string | null;
    motivo_cancelacion: string | null;
};

const confirmacion = useForm<{ filas: FilaConfirmada[] }>({ filas: [] });

const validas = computed(() => filas.value.filter((fila) => fila.nombre.trim() !== ''));

const quitar = (linea: number): void => {
    filas.value = filas.value.filter((fila) => fila.linea !== linea);
};

const confirmar = (): void => {
    confirmacion.filas = validas.value.map((fila) => ({
        nombre: fila.nombre,
        descripcion: fila.descripcion,
        estado: fila.estado,
        tipo: fila.tipo,
        prioridad: fila.prioridad,
        modulo_id: fila.modulo_id,
        // Solo se manda el nombre del módulo si de verdad hay que crearlo.
        modulo_nuevo: fila.modulo_por_crear ? fila.modulo : null,
        motivo_espera: fila.motivo_espera,
        motivo_cancelacion: fila.motivo_cancelacion,
    }));

    confirmacion.post(store(props.proyecto.id).url);
};

/** Error del servidor para un campo de una fila concreta. */
const errorDeFila = (indice: number, campo: string): string | undefined =>
    (confirmacion.errors as Record<string, string>)[`filas.${indice}.${campo}`];
</script>

<template>
    <Head :title="$t('tareas.rapida.titulo')" />

    <div class="flex flex-col gap-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                :title="$t('tareas.rapida.titulo')"
                :description="$t('tareas.rapida.descripcion')"
            />
            <Button as-child variant="ghost" size="sm">
                <Link :href="index(props.proyecto.id)">{{ $t('comun.volver') }}</Link>
            </Button>
        </div>

        <p
            v-if="props.periodoAbierto === null"
            class="rounded-lg border border-dashed border-border p-3 text-sm text-muted-foreground"
        >
            {{ $t('tareas.sin_periodo_abierto') }}
        </p>

        <div class="flex max-w-3xl flex-col gap-2">
            <Label for="texto">{{ $t('tareas.rapida.texto') }}</Label>
            <textarea
                id="texto"
                v-model="texto"
                rows="8"
                :placeholder="$t('tareas.rapida.ejemplo')"
                class="rounded-md border border-input bg-background px-3 py-2 font-mono text-sm text-foreground"
            />
            <p class="text-xs text-muted-foreground">{{ $t('tareas.rapida.formato') }}</p>
            <InputError :message="previsualizacion.errors.texto" />

            <div>
                <Button :disabled="previsualizacion.processing || texto.trim() === ''" @click="pedirPrevisualizacion">
                    {{ $t('tareas.rapida.previsualizar') }}
                </Button>
            </div>
        </div>

        <section v-if="filas.length" class="flex flex-col gap-3">
            <div>
                <h3 class="text-base font-medium">{{ $t('tareas.rapida.previsualizacion') }}</h3>
                <p class="text-sm text-muted-foreground">{{ $t('tareas.rapida.previsualizacion_ayuda') }}</p>
            </div>

            <ul class="flex flex-col gap-3">
                <li
                    v-for="(fila, indice) in filas"
                    :key="fila.linea"
                    class="flex flex-col gap-3 rounded-lg border border-border p-3"
                >
                    <div class="flex items-center justify-between gap-4">
                        <span class="font-mono text-xs text-muted-foreground">
                            {{ $t('tareas.rapida.linea', { numero: String(fila.linea) }) }}
                        </span>
                        <Button
                            variant="ghost"
                            size="sm"
                            :aria-label="$t('tareas.rapida.quitar_fila')"
                            @click="quitar(fila.linea)"
                        >
                            {{ $t('comun.quitar') }}
                        </Button>
                    </div>

                    <ul v-if="fila.avisos.length" class="flex flex-col gap-1">
                        <li v-for="aviso in fila.avisos" :key="aviso" class="text-xs text-muted-foreground">
                            {{ aviso === 'tareas.rapida.avisos.modulo_nuevo'
                                ? $t('tareas.rapida.modulo_nuevo', { nombre: fila.modulo ?? '' })
                                : $t(aviso) }}
                        </li>
                    </ul>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="grid gap-1 sm:col-span-2">
                            <Label :for="`nombre-${fila.linea}`">{{ $t('comun.nombre') }}</Label>
                            <Input :id="`nombre-${fila.linea}`" v-model="fila.nombre" />
                            <InputError :message="errorDeFila(indice, 'nombre')" />
                        </div>

                        <div class="grid gap-1">
                            <Label :for="`estado-${fila.linea}`">{{ $t('comun.estado') }}</Label>
                            <select
                                :id="`estado-${fila.linea}`"
                                v-model="fila.estado"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                            >
                                <option v-for="opcion in props.opciones.estados" :key="opcion.valor" :value="opcion.valor">
                                    {{ $t(opcion.etiqueta) }}
                                </option>
                            </select>
                        </div>

                        <div class="grid gap-1">
                            <Label :for="`tipo-${fila.linea}`">{{ $t('tareas.tipo') }}</Label>
                            <select
                                :id="`tipo-${fila.linea}`"
                                v-model="fila.tipo"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                            >
                                <option v-for="opcion in props.opciones.tipos" :key="opcion.valor" :value="opcion.valor">
                                    {{ $t(opcion.etiqueta) }}
                                </option>
                            </select>
                        </div>

                        <div class="grid gap-1 sm:col-span-2">
                            <Label :for="`descripcion-${fila.linea}`">{{ $t('tareas.descripcion') }}</Label>
                            <textarea
                                :id="`descripcion-${fila.linea}`"
                                v-model="fila.descripcion"
                                rows="2"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                            />
                        </div>

                        <!--
                            El motivo se pide aquí, no después: es el momento en
                            que el usuario todavía recuerda por qué lo escribió.
                        -->
                        <div v-if="fila.estado === 'en_espera'" class="grid gap-1 sm:col-span-2">
                            <Label :for="`espera-${fila.linea}`">{{ $t('tareas.estado.motivo_espera') }}</Label>
                            <Input :id="`espera-${fila.linea}`" :model-value="fila.motivo_espera ?? ''" @update:model-value="(valor) => (fila.motivo_espera = String(valor))" />
                            <InputError :message="errorDeFila(indice, 'motivo_espera')" />
                        </div>

                        <div v-if="fila.estado === 'cancelada'" class="grid gap-1 sm:col-span-2">
                            <Label :for="`cancelacion-${fila.linea}`">
                                {{ $t('tareas.estado.motivo_cancelacion') }}
                            </Label>
                            <Input
                                :id="`cancelacion-${fila.linea}`"
                                :model-value="fila.motivo_cancelacion ?? ''"
                                @update:model-value="(valor) => (fila.motivo_cancelacion = String(valor))"
                            />
                            <InputError :message="errorDeFila(indice, 'motivo_cancelacion')" />
                        </div>
                    </div>
                </li>
            </ul>

            <div>
                <Button
                    :disabled="confirmacion.processing || validas.length === 0"
                    @click="confirmar"
                >
                    {{ $t('tareas.rapida.confirmar', { total: String(validas.length) }) }}
                </Button>
            </div>
        </section>

        <p v-else class="text-sm text-muted-foreground">{{ $t('tareas.rapida.sin_filas') }}</p>
    </div>
</template>
