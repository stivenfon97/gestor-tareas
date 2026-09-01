<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import BadgeEstado from '@/components/tareas/BadgeEstado.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { update } from '@/routes/tareas/estado';
import type { EstadoTarea, Transicion } from '@/types/tareas';

/**
 * Cambio de estado de una tarea.
 *
 * **Las transiciones llegan del servidor.** Este componente no sabe cuál es la
 * matriz y no debe saberlo: replicarla aquí crearía una cuarta fuente de verdad
 * —enum, skill, diagrama y Vue— y sería justo la que nadie comprueba.
 */
const props = defineProps<{
    tareaId: number;
    estado: EstadoTarea;
    transiciones: Transicion[];
    puedeCambiar: boolean;
}>();

const abierta = ref<Transicion | null>(null);

const form = useForm({
    estado: '' as string,
    motivo_espera: '',
    motivo_cancelacion: '',
});

const abrir = (transicion: Transicion): void => {
    form.reset();
    form.clearErrors();
    form.estado = transicion.valor;

    // Sin motivo que pedir, se aplica de una vez: obligar a confirmar cada
    // "empezar" convertiría el trabajo diario en una sucesión de diálogos.
    if (transicion.requiere_motivo === null) {
        enviar();

        return;
    }

    abierta.value = transicion;
};

const enviar = (): void => {
    form.put(update(props.tareaId).url, {
        preserveScroll: true,
        onSuccess: () => {
            abierta.value = null;
            form.reset();
        },
    });
};
</script>

<template>
    <div class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <BadgeEstado :estado="props.estado" />

            <template v-if="props.puedeCambiar">
                <Button
                    v-for="transicion in props.transiciones"
                    :key="transicion.valor"
                    variant="outline"
                    size="sm"
                    :disabled="form.processing"
                    @click="abrir(transicion)"
                >
                    {{
                        $t('tareas.estado.mover_a', {
                            estado: $t(transicion.etiqueta),
                        })
                    }}
                </Button>
            </template>
        </div>

        <p
            v-if="props.transiciones.length === 0"
            class="text-sm text-muted-foreground"
        >
            {{ $t('tareas.estado.sin_transiciones') }}
        </p>

        <InputError :message="form.errors.estado" />

        <!-- El motivo se pide en el momento, no después: es cuando se recuerda. -->
        <Dialog
            :open="abierta !== null"
            @update:open="
                (estaAbierta) => (estaAbierta ? null : (abierta = null))
            "
        >
            <DialogContent v-if="abierta">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            $t('tareas.estado.mover_a', {
                                estado: $t(abierta.etiqueta),
                            })
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            abierta.requiere_motivo === 'motivo_espera'
                                ? $t('tareas.estado.motivo_espera_ayuda')
                                : $t('tareas.estado.motivo_cancelacion_ayuda')
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="abierta.requiere_motivo === 'motivo_espera'"
                    class="grid gap-2"
                >
                    <Label for="motivo_espera">{{
                        $t('tareas.estado.motivo_espera')
                    }}</Label>
                    <textarea
                        id="motivo_espera"
                        v-model="form.motivo_espera"
                        rows="3"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                    />
                    <InputError :message="form.errors.motivo_espera" />
                </div>

                <div v-else class="grid gap-2">
                    <Label for="motivo_cancelacion">{{
                        $t('tareas.estado.motivo_cancelacion')
                    }}</Label>
                    <textarea
                        id="motivo_cancelacion"
                        v-model="form.motivo_cancelacion"
                        rows="3"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                    />
                    <InputError :message="form.errors.motivo_cancelacion" />
                </div>

                <DialogFooter>
                    <Button variant="ghost" @click="abierta = null">{{
                        $t('comun.cancelar')
                    }}</Button>
                    <Button :disabled="form.processing" @click="enviar">
                        {{ $t('comun.confirmar') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
