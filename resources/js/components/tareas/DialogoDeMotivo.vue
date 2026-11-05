<script setup lang="ts">
import { AlignLeft } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupTextarea,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import type { Transicion } from '@/types/tareas';

/**
 * El motivo que exigen `en_espera` y `cancelada`.
 *
 * Qué campo hace falta **lo dice el servidor** en `requiere_motivo`, con el
 * nombre del campo y no un booleano: `motivo_espera` y `motivo_cancelacion` son
 * columnas distintas y se consultan en momentos distintos. Mezclarlas haría
 * ilegible el historial.
 *
 * Cancelar el diálogo **no es no hacer nada**: la tarjeta ya se movió en la
 * pantalla, así que cerrar sin confirmar tiene que devolverla a su columna. De
 * eso se encarga quien lo abre, por el evento `cancelar`.
 */
const props = defineProps<{
    abierto: boolean;
    transicion: Transicion | null;
    codigo: string;
}>();

const emit = defineEmits<{
    confirmar: [motivo: string];
    cancelar: [];
}>();

const motivo = ref('');

// Cada apertura empieza en blanco: reaprovechar el texto del movimiento
// anterior escribiría en una tarea el motivo de otra.
watch(
    () => props.abierto,
    (abierto) => {
        if (abierto) {
            motivo.value = '';
        }
    },
);

const esEspera = computed(
    () => props.transicion?.requiere_motivo === 'motivo_espera',
);

const titulo = computed(() =>
    esEspera.value ? 'tablero.motivo.espera' : 'tablero.motivo.cancelacion',
);

const marcador = computed(() =>
    esEspera.value
        ? 'tareas.marcadores.motivo_espera'
        : 'tareas.marcadores.motivo_cancelacion',
);

const ayuda = computed(() =>
    esEspera.value
        ? 'tablero.motivo.ayuda_espera'
        : 'tablero.motivo.ayuda_cancelacion',
);
</script>

<template>
    <Dialog
        :open="props.abierto"
        @update:open="(valor: boolean) => !valor && emit('cancelar')"
    >
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ $t('tablero.motivo.titulo') }}</DialogTitle>
                <DialogDescription>{{ $t(ayuda) }}</DialogDescription>
            </DialogHeader>

            <div class="flex flex-col gap-corto">
                <Label for="motivo-de-la-transicion">
                    {{ $t(titulo) }} — {{ props.codigo }}
                </Label>
                <InputGroup>
                    <InputGroupTextarea
                        id="motivo-de-la-transicion"
                        v-model="motivo"
                        rows="3"
                        :placeholder="$t(marcador)"
                        autofocus
                    />
                    <InputGroupAddon>
                        <AlignLeft aria-hidden="true" />
                    </InputGroupAddon>
                </InputGroup>
            </div>

            <DialogFooter class="gap-corto">
                <Button variant="outline" @click="emit('cancelar')">
                    {{ $t('comun.cancelar') }}
                </Button>
                <Button
                    :disabled="motivo.trim() === ''"
                    @click="emit('confirmar', motivo.trim())"
                >
                    {{ $t('comun.confirmar') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
