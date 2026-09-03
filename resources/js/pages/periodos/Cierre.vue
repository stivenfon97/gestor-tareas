<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, toRef } from 'vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { useTrm } from '@/composables/useTrm';
import { show as mostrarPeriodo } from '@/routes/periodos';
import { store } from '@/routes/periodos/cierre';

/**
 * Pantalla propia del cierre. No una acción del tablero: cierra en una sola
 * transacción, con traslado en lote, apertura del siguiente y pago opcional.
 */
const props = defineProps<{
    proyecto: {
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
    };
    periodo: { id: number; consecutivo: number; fecha_inicio: string };
    resumen: { total: number; por_estado: Record<string, number> };
    pendientes: Array<{
        id: number;
        codigo: string;
        nombre: string;
        estado: string;
        modulo: string | null;
    }>;
    fechaInicioSiguientePropuesta: string;
    puedeRegistrarPago: boolean;
    origenesTasa: Array<{ valor: string; etiqueta: string }>;
}>();

const form = useForm({
    tareas_trasladar: props.pendientes.map((tarea) => tarea.id),
    fecha_inicio_siguiente: props.fechaInicioSiguientePropuesta,
    pago: {
        fecha_pago: '',
        monto_usd: '',
        tasa_cambio: '',
        origen_tasa: 'manual',
        referencia: '',
        notas: '',
    },
});

const conPago = ref(false);

useTrm(
    toRef(form.pago, 'fecha_pago'),
    toRef(form.pago, 'tasa_cambio'),
    toRef(form.pago, 'origen_tasa'),
);

// No se envía `pago` si el registro está desmarcado: cerrar sin pago es
// válido, y el pago se puede añadir después.
form.transform((datos) => ({
    ...datos,
    pago: conPago.value ? datos.pago : null,
}));

const alternarTraslado = (id: number): void => {
    const indice = form.tareas_trasladar.indexOf(id);

    if (indice === -1) {
        form.tareas_trasladar.push(id);
    } else {
        form.tareas_trasladar.splice(indice, 1);
    }
};

const seTrasladan = computed(() => form.tareas_trasladar.length);
const seQuedan = computed(
    () => props.pendientes.length - form.tareas_trasladar.length,
);

const confirmando = ref(false);

const confirmar = (): void => {
    form.post(store(props.periodo.id).url, {
        onSuccess: () => {
            confirmando.value = false;
        },
        onError: () => {
            confirmando.value = false;
        },
    });
};
</script>

<template>
    <Head :title="$t('periodos.cerrar')" />

    <div class="flex max-w-3xl flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="
                $t('periodos.cerrar_titulo', {
                    consecutivo: String(props.periodo.consecutivo),
                })
            "
            :descripcion="props.proyecto.nombre"
        >
            <template #acciones>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="mostrarPeriodo(props.periodo.id)">{{
                        $t('comun.volver')
                    }}</Link>
                </Button>
            </template>
        </CabeceraDePagina>

        <!-- 1. Resumen de lo que se cierra -->
        <section class="flex flex-col gap-corto">
            <h2 class="text-cuerpo font-medium text-texto">
                {{ $t('periodos.cierre.resumen') }}
            </h2>
            <div class="flex flex-wrap gap-corto">
                <div
                    v-for="(total, estado) in props.resumen.por_estado"
                    :key="estado"
                    class="rounded-md border border-borde-fuerte bg-panel px-celda py-fino text-fino"
                >
                    <span class="text-texto-suave">{{
                        $t(`enums.estado_tarea.${estado}`)
                    }}</span>
                    <span class="ml-fino font-mono font-medium text-texto">{{
                        total
                    }}</span>
                </div>
            </div>
            <p class="text-fino text-texto-suave">
                {{
                    $t('periodos.cierre.resumen_ayuda', {
                        total: String(props.resumen.total),
                        pendientes: String(props.pendientes.length),
                    })
                }}
            </p>
        </section>

        <!-- 2. Traslado de pendientes -->
        <section class="flex flex-col gap-corto">
            <h2 class="text-cuerpo font-medium text-texto">
                {{ $t('periodos.cierre.traslado') }}
            </h2>
            <p class="text-fino text-texto-suave">
                {{ $t('periodos.cierre.traslado_ayuda') }}
            </p>

            <div
                v-if="props.pendientes.length > 0"
                class="flex flex-col overflow-hidden rounded-md border border-borde-fuerte bg-panel"
            >
                <label
                    v-for="tarea in props.pendientes"
                    :key="tarea.id"
                    class="flex items-center gap-corto border-b border-borde px-celda py-fino text-cuerpo last:border-b-0 hover:bg-fila-hover"
                >
                    <Checkbox
                        :model-value="form.tareas_trasladar.includes(tarea.id)"
                        @update:model-value="alternarTraslado(tarea.id)"
                    />
                    <span class="font-mono text-fino text-texto-suave">{{
                        tarea.codigo
                    }}</span>
                    <span class="min-w-0 flex-1 truncate text-texto">{{
                        tarea.nombre
                    }}</span>
                    <span
                        v-if="tarea.modulo"
                        class="hidden shrink-0 text-fino text-texto-suave sm:inline"
                    >
                        {{ tarea.modulo }}
                    </span>
                    <Badge variant="outline">
                        {{ $t(`enums.estado_tarea.${tarea.estado}`) }}
                    </Badge>
                </label>
            </div>
            <p v-else class="text-fino text-texto-tenue">
                {{ $t('periodos.cierre.sin_pendientes') }}
            </p>
        </section>

        <!-- 3. Apertura del siguiente -->
        <section class="flex flex-col gap-corto">
            <h2 class="text-cuerpo font-medium text-texto">
                {{ $t('periodos.cierre.siguiente') }}
            </h2>
            <div class="grid max-w-xs gap-2">
                <Label for="fecha-inicio-siguiente">{{
                    $t('recursos.columnas.fecha_inicio')
                }}</Label>
                <Input
                    id="fecha-inicio-siguiente"
                    v-model="form.fecha_inicio_siguiente"
                    type="date"
                />
                <InputError :message="form.errors.fecha_inicio_siguiente" />
            </div>
        </section>

        <!-- 4. Pago, opcional -->
        <section class="flex flex-col gap-corto">
            <label class="flex items-center gap-corto text-cuerpo text-texto">
                <Checkbox v-if="props.puedeRegistrarPago" v-model="conPago" />
                <span class="font-medium">{{
                    $t('periodos.cierre.pago')
                }}</span>
            </label>
            <p class="text-fino text-texto-suave">
                {{ $t('periodos.cierre.pago_ayuda') }}
            </p>

            <div
                v-if="conPago && props.puedeRegistrarPago"
                class="grid max-w-xl gap-4 rounded-md border border-borde-fuerte bg-panel px-celda py-corto sm:grid-cols-2"
            >
                <div class="grid gap-2">
                    <Label for="cierre-fecha-pago">{{
                        $t('pagos.fecha_pago')
                    }}</Label>
                    <Input
                        id="cierre-fecha-pago"
                        v-model="form.pago.fecha_pago"
                        type="date"
                    />
                    <InputError :message="form.errors['pago.fecha_pago']" />
                </div>
                <div class="grid gap-2">
                    <Label for="cierre-monto-usd">{{
                        $t('pagos.monto_usd')
                    }}</Label>
                    <Input
                        id="cierre-monto-usd"
                        v-model="form.pago.monto_usd"
                        type="number"
                        step="0.01"
                        min="0"
                    />
                    <InputError :message="form.errors['pago.monto_usd']" />
                </div>
                <div class="grid gap-2">
                    <Label for="cierre-tasa-cambio">{{
                        $t('pagos.tasa_cambio')
                    }}</Label>
                    <Input
                        id="cierre-tasa-cambio"
                        v-model="form.pago.tasa_cambio"
                        type="number"
                        step="0.0001"
                        min="0"
                    />
                    <InputError :message="form.errors['pago.tasa_cambio']" />
                </div>
                <div class="grid gap-2">
                    <Label for="cierre-origen-tasa">{{
                        $t('pagos.origen_tasa')
                    }}</Label>
                    <select
                        id="cierre-origen-tasa"
                        v-model="form.pago.origen_tasa"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                    >
                        <option
                            v-for="opcion in props.origenesTasa"
                            :key="opcion.valor"
                            :value="opcion.valor"
                        >
                            {{ opcion.etiqueta }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="cierre-referencia">{{
                        $t('pagos.referencia')
                    }}</Label>
                    <Input
                        id="cierre-referencia"
                        v-model="form.pago.referencia"
                    />
                    <InputError :message="form.errors['pago.referencia']" />
                </div>
            </div>
        </section>

        <!-- 5. Confirmación -->
        <div
            class="flex items-center gap-corto border-t border-borde pt-bloque"
        >
            <Button @click="confirmando = true">
                {{ $t('periodos.cerrar') }}
            </Button>
            <Button as-child variant="ghost">
                <Link :href="mostrarPeriodo(props.periodo.id)">{{
                    $t('comun.cancelar')
                }}</Link>
            </Button>
        </div>

        <Dialog v-model:open="confirmando">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{
                            $t('periodos.cierre.confirmar_titulo', {
                                consecutivo: String(props.periodo.consecutivo),
                            })
                        }}
                    </DialogTitle>
                    <DialogDescription class="flex flex-col gap-fino text-left">
                        <span>
                            {{
                                $t('periodos.cierre.confirmar_traslado', {
                                    trasladan: String(seTrasladan),
                                    quedan: String(seQuedan),
                                })
                            }}
                        </span>
                        <span>
                            {{
                                $t('periodos.cierre.confirmar_siguiente', {
                                    fecha: form.fecha_inicio_siguiente,
                                })
                            }}
                        </span>
                        <span v-if="conPago">
                            {{
                                $t('periodos.cierre.confirmar_pago', {
                                    monto: form.pago.monto_usd || '0',
                                })
                            }}
                        </span>
                        <span v-else>
                            {{ $t('periodos.cierre.confirmar_sin_pago') }}
                        </span>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="ghost" @click="confirmando = false">{{
                        $t('comun.cancelar')
                    }}</Button>
                    <Button :disabled="form.processing" @click="confirmar">
                        {{ $t('periodos.cierre.confirmar') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
