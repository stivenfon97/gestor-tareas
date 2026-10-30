<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { AlignLeft, FileText, ListPlus, Type } from '@lucide/vue';
import { computed, ref } from 'vue';
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
import type { FilaCapturaRapida, Opcion } from '@/types/tareas';

/**
 * El núcleo de la captura rápida: pegar texto, previsualizar, editar cada
 * fila y confirmar. Extraído de `tareas/CapturaRapida.vue` (fase 06) para que
 * la bitácora (fase 10) lo reutilice tal cual al crear tareas desde una
 * reunión — mismo parser, misma validación de motivos, sin reimplementar
 * nada.
 *
 * No sabe a dónde va después de confirmar: eso lo decide el controlador que
 * atiende `storeUrl` (redirige al listado de tareas, o se queda en la
 * reunión con `back()`). El componente solo dispara el POST.
 */
const props = defineProps<{
    opciones: { estados: Opcion[]; tipos: Opcion[]; prioridades: Opcion[] };
    periodoAbierto: number | null;
    previsualizarUrl: string;
    storeUrl: string;
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

    previsualizacion.post(props.previsualizarUrl, {
        preserveScroll: true,
        onFlash: (flash) => {
            const propuestas = (flash.previsualizacion ??
                []) as FilaCapturaRapida[];

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

const validas = computed(() =>
    filas.value.filter((fila) => fila.nombre.trim() !== ''),
);

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

    confirmacion.post(props.storeUrl, {
        preserveScroll: true,
        onSuccess: () => {
            texto.value = '';
            filas.value = [];
        },
    });
};

/** Error del servidor para un campo de una fila concreta. */
const errorDeFila = (indice: number, campo: string): string | undefined =>
    (confirmacion.errors as Record<string, string>)[`filas.${indice}.${campo}`];
</script>

<template>
    <div class="flex flex-col gap-seccion">
        <p
            v-if="props.periodoAbierto === null"
            class="rounded-lg border border-dashed border-border p-3 text-sm text-muted-foreground"
        >
            {{ $t('tareas.sin_periodo_abierto') }}
        </p>

        <div class="flex flex-col gap-2">
            <Label for="captura-rapida-texto">{{
                $t('tareas.rapida.texto')
            }}</Label>
            <InputGroup>
                <InputGroupTextarea
                    id="captura-rapida-texto"
                    v-model="texto"
                    rows="8"
                    class="font-mono"
                    :placeholder="$t('tareas.rapida.ejemplo')"
                    :aria-invalid="!!previsualizacion.errors.texto"
                />
                <InputGroupAddon align="block-start">
                    <ListPlus aria-hidden="true" />
                </InputGroupAddon>
            </InputGroup>
            <p class="text-xs text-muted-foreground">
                {{ $t('tareas.rapida.formato') }}
            </p>
            <InputError :message="previsualizacion.errors.texto" />

            <div>
                <Button
                    :disabled="
                        previsualizacion.processing || texto.trim() === ''
                    "
                    @click="pedirPrevisualizacion"
                >
                    {{ $t('tareas.rapida.previsualizar') }}
                </Button>
            </div>
        </div>

        <section v-if="filas.length" class="flex flex-col gap-3">
            <div>
                <h3 class="text-base font-medium">
                    {{ $t('tareas.rapida.previsualizacion') }}
                </h3>
                <p class="text-sm text-muted-foreground">
                    {{ $t('tareas.rapida.previsualizacion_ayuda') }}
                </p>
            </div>

            <ul class="flex flex-col gap-3">
                <li
                    v-for="(fila, indice) in filas"
                    :key="fila.linea"
                    class="flex flex-col gap-3 rounded-lg border border-border p-3"
                >
                    <div class="flex items-center justify-between gap-4">
                        <span class="font-mono text-xs text-muted-foreground">
                            {{
                                $t('tareas.rapida.linea', {
                                    numero: String(fila.linea),
                                })
                            }}
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
                        <li
                            v-for="aviso in fila.avisos"
                            :key="aviso"
                            class="text-xs text-muted-foreground"
                        >
                            {{
                                aviso === 'tareas.rapida.avisos.modulo_nuevo'
                                    ? $t('tareas.rapida.modulo_nuevo', {
                                          nombre: fila.modulo ?? '',
                                      })
                                    : $t(aviso)
                            }}
                        </li>
                    </ul>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="grid gap-1 sm:col-span-2">
                            <Label :for="`captura-nombre-${fila.linea}`">{{
                                $t('comun.nombre')
                            }}</Label>
                            <InputGroup>
                                <InputGroupInput
                                    :id="`captura-nombre-${fila.linea}`"
                                    v-model="fila.nombre"
                                    :placeholder="
                                        $t('tareas.marcadores.nombre')
                                    "
                                    :aria-invalid="
                                        !!errorDeFila(indice, 'nombre')
                                    "
                                />
                                <InputGroupAddon>
                                    <Type aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>
                            <InputError
                                :message="errorDeFila(indice, 'nombre')"
                            />
                        </div>

                        <div class="grid gap-1">
                            <Label :for="`captura-estado-${fila.linea}`">{{
                                $t('comun.estado')
                            }}</Label>
                            <Select v-model="fila.estado">
                                <SelectTrigger
                                    :id="`captura-estado-${fila.linea}`"
                                    class="w-full"
                                    :aria-invalid="
                                        !!errorDeFila(indice, 'estado')
                                    "
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opcion in props.opciones.estados"
                                        :key="opcion.valor"
                                        :value="opcion.valor"
                                    >
                                        {{ $t(opcion.etiqueta) }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError
                                :message="errorDeFila(indice, 'estado')"
                            />
                        </div>

                        <div class="grid gap-1">
                            <Label :for="`captura-tipo-${fila.linea}`">{{
                                $t('tareas.tipo')
                            }}</Label>
                            <Select v-model="fila.tipo">
                                <SelectTrigger
                                    :id="`captura-tipo-${fila.linea}`"
                                    class="w-full"
                                    :aria-invalid="
                                        !!errorDeFila(indice, 'tipo')
                                    "
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opcion in props.opciones.tipos"
                                        :key="opcion.valor"
                                        :value="opcion.valor"
                                    >
                                        {{ $t(opcion.etiqueta) }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError
                                :message="errorDeFila(indice, 'tipo')"
                            />
                        </div>

                        <div class="grid gap-1 sm:col-span-2">
                            <Label :for="`captura-descripcion-${fila.linea}`">{{
                                $t('tareas.descripcion')
                            }}</Label>
                            <InputGroup>
                                <InputGroupTextarea
                                    :id="`captura-descripcion-${fila.linea}`"
                                    :model-value="fila.descripcion ?? ''"
                                    rows="2"
                                    :placeholder="
                                        $t('tareas.marcadores.descripcion')
                                    "
                                    :aria-invalid="
                                        !!errorDeFila(indice, 'descripcion')
                                    "
                                    @update:model-value="
                                        (valor: string | number) =>
                                            (fila.descripcion = String(valor))
                                    "
                                />
                                <InputGroupAddon align="block-start">
                                    <FileText aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>
                            <InputError
                                :message="errorDeFila(indice, 'descripcion')"
                            />
                        </div>

                        <!--
                            El motivo se pide aquí, no después: es el momento en
                            que el usuario todavía recuerda por qué lo escribió.
                        -->
                        <div
                            v-if="fila.estado === 'en_espera'"
                            class="grid gap-1 sm:col-span-2"
                        >
                            <Label :for="`captura-espera-${fila.linea}`">{{
                                $t('tareas.estado.motivo_espera')
                            }}</Label>
                            <InputGroup>
                                <InputGroupInput
                                    :id="`captura-espera-${fila.linea}`"
                                    :model-value="fila.motivo_espera ?? ''"
                                    :placeholder="
                                        $t('tareas.marcadores.motivo_espera')
                                    "
                                    :aria-invalid="
                                        !!errorDeFila(indice, 'motivo_espera')
                                    "
                                    @update:model-value="
                                        (valor: string | number) =>
                                            (fila.motivo_espera = String(valor))
                                    "
                                />
                                <InputGroupAddon>
                                    <AlignLeft aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>
                            <InputError
                                :message="errorDeFila(indice, 'motivo_espera')"
                            />
                        </div>

                        <div
                            v-if="fila.estado === 'cancelada'"
                            class="grid gap-1 sm:col-span-2"
                        >
                            <Label :for="`captura-cancelacion-${fila.linea}`">
                                {{ $t('tareas.estado.motivo_cancelacion') }}
                            </Label>
                            <InputGroup>
                                <InputGroupInput
                                    :id="`captura-cancelacion-${fila.linea}`"
                                    :model-value="fila.motivo_cancelacion ?? ''"
                                    :placeholder="
                                        $t(
                                            'tareas.marcadores.motivo_cancelacion',
                                        )
                                    "
                                    :aria-invalid="
                                        !!errorDeFila(
                                            indice,
                                            'motivo_cancelacion',
                                        )
                                    "
                                    @update:model-value="
                                        (valor: string | number) =>
                                            (fila.motivo_cancelacion =
                                                String(valor))
                                    "
                                />
                                <InputGroupAddon>
                                    <AlignLeft aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>
                            <InputError
                                :message="
                                    errorDeFila(indice, 'motivo_cancelacion')
                                "
                            />
                        </div>
                    </div>
                </li>
            </ul>

            <div>
                <Button
                    :disabled="confirmacion.processing || validas.length === 0"
                    @click="confirmar"
                >
                    {{
                        $t('tareas.rapida.confirmar', {
                            total: String(validas.length),
                        })
                    }}
                </Button>
            </div>
        </section>

        <p v-else class="text-sm text-muted-foreground">
            {{ $t('tareas.rapida.sin_filas') }}
        </p>
    </div>
</template>
