<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import EstadoVacio from '@/components/EstadoVacio.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { show as mostrarPeriodo } from '@/routes/periodos';
import { show as mostrarProyecto } from '@/routes/proyectos';
import { store } from '@/routes/proyectos/periodos';

const props = defineProps<{
    proyecto: {
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
    };
    periodos: Array<{
        id: number;
        consecutivo: number;
        fecha_inicio: string;
        fecha_fin: string | null;
        estado: string;
        vencido: boolean;
        tareas_count: number;
        pago: {
            monto_usd: string;
            monto_cop: string;
            fecha_pago: string;
        } | null;
    }>;
    puede: { gestionar: boolean; verPagos: boolean };
    periodoAbierto: { id: number; consecutivo: number } | null;
}>();

const abierto = ref(false);
const fechaInicio = ref(new Date().toISOString().slice(0, 10));
const notas = ref('');
const errores = ref<{ fecha_inicio?: string }>({});

const abrirPeriodo = (): void => {
    router.post(
        store(props.proyecto.id).url,
        { fecha_inicio: fechaInicio.value, notas: notas.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                abierto.value = false;
                notas.value = '';
            },
            onError: (nuevos) => {
                errores.value = nuevos;
            },
        },
    );
};
</script>

<template>
    <Head :title="$t('recursos.periodos')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="$t('recursos.periodos')"
            :descripcion="props.proyecto.nombre"
        >
            <template #acciones>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="mostrarProyecto(props.proyecto.id)">{{
                        $t('comun.volver')
                    }}</Link>
                </Button>

                <Popover v-if="props.puede.gestionar" v-model:open="abierto">
                    <PopoverTrigger as-child>
                        <Button
                            size="sm"
                            :disabled="props.periodoAbierto !== null"
                            :title="
                                props.periodoAbierto
                                    ? $t('periodos.ya_hay_abierto_ayuda', {
                                          consecutivo: String(
                                              props.periodoAbierto.consecutivo,
                                          ),
                                      })
                                    : undefined
                            "
                        >
                            {{ $t('periodos.abrir') }}
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent
                        align="end"
                        class="flex w-80 flex-col gap-corto"
                    >
                        <p class="text-fino text-texto-suave">
                            {{ $t('periodos.abrir_ayuda') }}
                        </p>

                        <div class="grid gap-fino">
                            <Label
                                for="periodo-fecha-inicio"
                                class="text-micro text-texto-suave"
                            >
                                {{ $t('recursos.columnas.fecha_inicio') }}
                            </Label>
                            <Input
                                id="periodo-fecha-inicio"
                                v-model="fechaInicio"
                                type="date"
                            />
                            <InputError :message="errores.fecha_inicio" />
                        </div>

                        <div class="grid gap-fino">
                            <Label
                                for="periodo-notas"
                                class="text-micro text-texto-suave"
                            >
                                {{ $t('periodos.notas') }}
                            </Label>
                            <textarea
                                id="periodo-notas"
                                v-model="notas"
                                rows="2"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                            />
                        </div>

                        <div class="flex justify-end">
                            <Button size="sm" @click="abrirPeriodo">
                                {{ $t('periodos.abrir') }}
                            </Button>
                        </div>
                    </PopoverContent>
                </Popover>
            </template>
        </CabeceraDePagina>

        <EstadoVacio
            v-if="props.periodos.length === 0"
            :titulo="$t('periodos.sin_periodos')"
            :cuerpo="$t('periodos.sin_periodos_ayuda')"
        />

        <div
            v-else
            class="overflow-x-auto rounded-md border border-borde-fuerte bg-panel"
        >
            <table class="w-full min-w-2xl text-left text-cuerpo">
                <thead class="bg-panel-cabecera text-texto-suave">
                    <tr>
                        <th class="px-celda py-corto font-medium">
                            {{ $t('recursos.columnas.consecutivo') }}
                        </th>
                        <th class="px-celda py-corto font-medium">
                            {{ $t('recursos.columnas.fecha_inicio') }}
                        </th>
                        <th class="px-celda py-corto font-medium">
                            {{ $t('periodos.fecha_fin') }}
                        </th>
                        <th class="px-celda py-corto font-medium">
                            {{ $t('recursos.columnas.estado') }}
                        </th>
                        <th class="px-celda py-corto text-right font-medium">
                            {{ $t('proyectos.secciones.tareas') }}
                        </th>
                        <th
                            v-if="props.puede.verPagos"
                            class="px-celda py-corto font-medium"
                        >
                            {{ $t('recursos.pago') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="periodo in props.periodos"
                        :key="periodo.id"
                        class="border-t border-borde hover:bg-fila-hover"
                    >
                        <td class="px-celda py-corto">
                            <Link
                                :href="mostrarPeriodo(periodo.id)"
                                class="font-mono text-texto underline-offset-4 hover:underline"
                            >
                                {{ periodo.consecutivo }}
                            </Link>
                        </td>
                        <td
                            class="px-celda py-corto font-mono text-texto-suave"
                        >
                            {{ periodo.fecha_inicio }}
                        </td>
                        <td
                            class="px-celda py-corto font-mono text-texto-suave"
                        >
                            {{ periodo.fecha_fin ?? '—' }}
                        </td>
                        <td class="px-celda py-corto">
                            <div class="flex items-center gap-fino">
                                <Badge
                                    :variant="
                                        periodo.estado === 'abierto'
                                            ? 'outline'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        $t(
                                            `enums.estado_periodo.${periodo.estado}`,
                                        )
                                    }}
                                </Badge>
                                <Badge v-if="periodo.vencido" variant="outline">
                                    {{ $t('periodos.vencido') }}
                                </Badge>
                            </div>
                        </td>
                        <td
                            class="px-celda py-corto text-right font-mono text-texto-suave"
                        >
                            {{ periodo.tareas_count }}
                        </td>
                        <td
                            v-if="props.puede.verPagos"
                            class="px-celda py-corto text-texto-suave"
                        >
                            <span v-if="periodo.pago">
                                {{ $t('pagos.usd') }}
                                {{ periodo.pago.monto_usd }}
                            </span>
                            <span v-else>—</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
