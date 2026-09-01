<script setup lang="ts">
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

/**
 * Confirmación de una acción destructiva **escribiendo el nombre**.
 *
 * Un «¿seguro?» con dos botones se contesta que sí sin leerlo: el dedo ya iba
 * hacia el botón de la derecha. Escribir el nombre obliga a mirar qué se está
 * borrando, y es la única confirmación que distingue borrar el proyecto que
 * tocaba de borrar el de al lado.
 *
 * Se reserva para lo que arrastra otras cosas detrás —un proyecto arrastra
 * módulos, tareas, periodos, pagos y reuniones—. Para lo reversible, un diálogo
 * normal basta: la confirmación tiene que ser proporcional al daño, y una que
 * estorba en lo trivial enseña a despacharla sin leer también en lo grave.
 *
 * El diálogo lo abre quien lo usa (`v-model:abierto`) y no un disparador
 * propio: la acción suele vivir dentro de un menú, y un menú se cierra —y
 * desmonta su disparador— justo al pulsarlo.
 */
const props = defineProps<{
    titulo: string;
    descripcion: string;
    /** Lo que hay que escribir, tal cual. */
    nombre: string;
    etiquetaCampo: string;
    boton: string;
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const emit = defineEmits<{ confirmar: [] }>();

const escrito = ref('');

// Cada apertura empieza en blanco: si no, basta con abrir, cerrar y volver a
// abrir para encontrarse el campo ya validado.
watch(abierto, (valor) => {
    if (valor) {
        escrito.value = '';
    }
});

const coincide = computed(() => escrito.value.trim() === props.nombre);
</script>

<template>
    <Dialog v-model:open="abierto">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ props.titulo }}</DialogTitle>
                <DialogDescription>{{ props.descripcion }}</DialogDescription>
            </DialogHeader>

            <div class="flex flex-col gap-corto">
                <Label for="confirmacion-por-nombre">
                    {{ props.etiquetaCampo }}
                </Label>
                <Input
                    id="confirmacion-por-nombre"
                    v-model="escrito"
                    autocomplete="off"
                    :placeholder="props.nombre"
                />
            </div>

            <DialogFooter class="gap-corto">
                <Button variant="outline" @click="abierto = false">
                    {{ $t('comun.cancelar') }}
                </Button>
                <Button
                    variant="destructive"
                    :disabled="!coincide"
                    @click="emit('confirmar')"
                >
                    {{ props.boton }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
