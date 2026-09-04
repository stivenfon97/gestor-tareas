<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
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
import { formatearFecha, formatearMonto } from '@/lib/formato';
import { show as mostrarPago } from '@/routes/pagos';
import { update } from '@/routes/periodos';
import { create as cierreCreate } from '@/routes/periodos/cierre';
import { create as crearPago } from '@/routes/proyectos/pagos';
import { index as periodosIndex } from '@/routes/proyectos/periodos';
import { show as mostrarTarea } from '@/routes/tareas';

const props = defineProps<{
    proyecto: {
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
    };
    periodo: {
        id: number;
        consecutivo: number;
        fecha_inicio: string;
        fecha_fin: string | null;
        estado: string;
        notas: string | null;
        vencido: boolean;
        duracion_en_dias: number | null;
    };
    tareasPorEstado: Array<{
        estado: string;
        etiqueta: string;
        tareas: Array<{
            id: number;
            codigo: string;
            nombre: string;
            modulo: string | null;
            arrastrada: boolean;
        }>;
    }>;
    arrastradasHaciaOtro: Array<{
        id: number;
        codigo: string;
        nombre: string;
        estado: string;
        periodo_destino: number | null;
    }>;
    pago: {
        id: number;
        fecha_pago: string;
        monto_usd: string;
        monto_cop: string;
        tasa_cambio: string;
        origen_tasa: string;
        referencia: string | null;
    } | null;
    puede: {
        editar: boolean;
        cerrar: boolean;
        verPagos: boolean;
        registrarPago: boolean;
    };
}>();

const editando = ref(false);
const notas = ref(props.periodo.notas ?? '');
const fechaFin = ref(props.periodo.fecha_fin ?? '');
const errores = ref<{ fecha_fin?: string; notas?: string }>({});

const guardar = (): void => {
    router.put(
        update(props.periodo.id).url,
        { notas: notas.value, fecha_fin: fechaFin.value || null },
        {
            preserveScroll: true,
            onSuccess: () => {
                editando.value = false;
            },
            onError: (nuevos) => {
                errores.value = nuevos;
            },
        },
    );
};
</script>

<template>
    <Head
        :title="
            $t('tareas.periodo_numero', {
                consecutivo: String(props.periodo.consecutivo),
            })
        "
    />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="
                $t('tareas.periodo_numero', {
                    consecutivo: String(props.periodo.consecutivo),
                })
            "
            :descripcion="props.proyecto.nombre"
        >
            <template #acciones>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="periodosIndex(props.proyecto.id)">{{
                        $t('comun.volver')
                    }}</Link>
                </Button>
                <Button
                    v-if="props.puede.cerrar"
                    as-child
                    variant="outline"
                    size="sm"
                >
                    <Link :href="cierreCreate(props.periodo.id)">{{
                        $t('periodos.cerrar')
                    }}</Link>
                </Button>
            </template>

            <template #meta>
                <Badge
                    :variant="
                        props.periodo.estado === 'abierto'
                            ? 'outline'
                            : 'secondary'
                    "
                >
                    {{ $t(`enums.estado_periodo.${props.periodo.estado}`) }}
                </Badge>
                <Badge v-if="props.periodo.vencido" variant="outline">
                    {{ $t('periodos.vencido') }}
                </Badge>
                <span class="text-cuerpo text-texto-suave">
                    {{ formatearFecha(props.periodo.fecha_inicio) }} –
                    {{
                        props.periodo.fecha_fin
                            ? formatearFecha(props.periodo.fecha_fin)
                            : $t('periodos.en_curso')
                    }}
                </span>
            </template>
        </CabeceraDePagina>

        <!-- Notas y fecha de fin prevista: solo editables mientras está abierto. -->
        <section
            v-if="props.puede.editar"
            class="flex flex-col gap-corto rounded-md border border-borde-fuerte bg-panel px-celda py-corto"
        >
            <div class="flex items-center justify-between gap-corto">
                <h2 class="text-cuerpo font-medium text-texto">
                    {{ $t('periodos.notas') }}
                </h2>
                <Popover
                    v-if="props.periodo.estado === 'abierto'"
                    v-model:open="editando"
                >
                    <PopoverTrigger as-child>
                        <Button variant="ghost" size="sm">{{
                            $t('comun.editar')
                        }}</Button>
                    </PopoverTrigger>
                    <PopoverContent
                        align="end"
                        class="flex w-80 flex-col gap-corto"
                    >
                        <div class="grid gap-fino">
                            <Label
                                for="periodo-fecha-fin"
                                class="text-micro text-texto-suave"
                            >
                                {{ $t('periodos.fecha_fin_prevista') }}
                            </Label>
                            <Input
                                id="periodo-fecha-fin"
                                v-model="fechaFin"
                                type="date"
                            />
                            <p class="text-micro text-texto-tenue">
                                {{ $t('periodos.fecha_fin_prevista_ayuda') }}
                            </p>
                            <InputError :message="errores.fecha_fin" />
                        </div>
                        <div class="grid gap-fino">
                            <Label
                                for="periodo-notas-editar"
                                class="text-micro text-texto-suave"
                            >
                                {{ $t('periodos.notas') }}
                            </Label>
                            <textarea
                                id="periodo-notas-editar"
                                v-model="notas"
                                rows="3"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                            />
                            <InputError :message="errores.notas" />
                        </div>
                        <div class="flex justify-end">
                            <Button size="sm" @click="guardar">{{
                                $t('comun.guardar')
                            }}</Button>
                        </div>
                    </PopoverContent>
                </Popover>
            </div>
            <p
                v-if="props.periodo.notas"
                class="text-cuerpo whitespace-pre-line text-texto"
            >
                {{ props.periodo.notas }}
            </p>
            <p v-else class="text-cuerpo text-texto-tenue">
                {{ $t('periodos.sin_notas') }}
            </p>
        </section>

        <!-- Pago -->
        <section
            v-if="props.puede.verPagos"
            class="flex flex-col gap-corto rounded-md border border-borde-fuerte bg-panel px-celda py-corto"
        >
            <h2 class="text-cuerpo font-medium text-texto">
                {{ $t('recursos.pago') }}
            </h2>
            <template v-if="props.pago">
                <div class="flex flex-wrap items-center gap-celda text-cuerpo">
                    <span class="text-texto">
                        {{ formatearMonto(props.pago.monto_usd, 'USD') }} ·
                        {{ formatearMonto(props.pago.monto_cop, 'COP') }}
                    </span>
                    <span class="text-texto-suave">{{
                        formatearFecha(props.pago.fecha_pago)
                    }}</span>
                    <Badge variant="outline">
                        {{
                            $t(
                                `enums.origen_tasa_cambio.${props.pago.origen_tasa}`,
                            )
                        }}
                    </Badge>
                    <Button as-child variant="ghost" size="sm">
                        <Link :href="mostrarPago(props.pago.id)">{{
                            $t('comun.ver')
                        }}</Link>
                    </Button>
                </div>
            </template>
            <template v-else>
                <p class="text-cuerpo text-texto-suave">
                    {{ $t('pagos.sin_pago_periodo') }}
                </p>
                <Button
                    v-if="props.puede.registrarPago"
                    as-child
                    variant="outline"
                    size="sm"
                    class="self-start"
                >
                    <Link
                        :href="
                            crearPago(props.proyecto.id, {
                                query: { periodo_id: props.periodo.id },
                            })
                        "
                    >
                        {{ $t('pagos.registrar') }}
                    </Link>
                </Button>
            </template>
        </section>

        <!-- Desglose de tareas por estado -->
        <section class="flex flex-col gap-corto">
            <h2 class="text-cuerpo font-medium text-texto">
                {{ $t('periodos.desglose') }}
            </h2>
            <div class="grid gap-corto sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="grupo in props.tareasPorEstado"
                    :key="grupo.estado"
                    class="flex flex-col gap-fino rounded-md border border-borde-fuerte bg-panel px-celda py-corto"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-fino font-medium text-texto-suave">{{
                            grupo.etiqueta
                        }}</span>
                        <span class="font-mono text-fino text-texto-tenue">{{
                            grupo.tareas.length
                        }}</span>
                    </div>
                    <ul class="flex flex-col gap-fino">
                        <li v-for="tarea in grupo.tareas" :key="tarea.id">
                            <Link
                                :href="mostrarTarea(tarea.id)"
                                class="flex items-center gap-fino text-fino text-texto hover:underline"
                            >
                                <span class="font-mono text-texto-suave">{{
                                    tarea.codigo
                                }}</span>
                                <span class="truncate">{{ tarea.nombre }}</span>
                                <Badge
                                    v-if="tarea.arrastrada"
                                    variant="outline"
                                >
                                    {{ $t('tareas.arrastrada') }}
                                </Badge>
                            </Link>
                        </li>
                        <li
                            v-if="grupo.tareas.length === 0"
                            class="text-fino text-texto-tenue"
                        >
                            {{ $t('tablero.lista.vacio') }}
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Lo que este periodo cedió a otro -->
        <section
            v-if="props.arrastradasHaciaOtro.length > 0"
            class="flex flex-col gap-corto"
        >
            <h2 class="text-cuerpo font-medium text-texto">
                {{ $t('periodos.arrastradas_hacia_otro') }}
            </h2>
            <ul class="flex flex-col gap-fino">
                <li v-for="tarea in props.arrastradasHaciaOtro" :key="tarea.id">
                    <Link
                        :href="mostrarTarea(tarea.id)"
                        class="flex items-center gap-fino text-cuerpo text-texto hover:underline"
                    >
                        <span class="font-mono text-texto-suave">{{
                            tarea.codigo
                        }}</span>
                        <span>{{ tarea.nombre }}</span>
                        <span
                            v-if="tarea.periodo_destino"
                            class="text-fino text-texto-suave"
                        >
                            →
                            {{
                                $t('tareas.periodo_numero', {
                                    consecutivo: String(tarea.periodo_destino),
                                })
                            }}
                        </span>
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
