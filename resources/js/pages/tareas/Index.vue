<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import Paginacion from '@/components/Paginacion.vue';
import BadgeEstado from '@/components/tareas/BadgeEstado.vue';
import BarraPrioridad from '@/components/tareas/BarraPrioridad.vue';
import IconoTipo from '@/components/tareas/IconoTipo.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { show as mostrarProyecto } from '@/routes/proyectos';
import { create, index } from '@/routes/proyectos/tareas';
import { create as capturaRapida } from '@/routes/proyectos/tareas/rapida';
import { show } from '@/routes/tareas';
import type { RespuestaPaginada } from '@/types/paginacion';
import type { FilaTarea, OpcionesDeTarea } from '@/types/tareas';

const props = defineProps<{
    proyecto: { id: number; nombre: string; slug: string; prefijo_tarea: string };
    tareas: RespuestaPaginada<FilaTarea>;
    filtros: Record<string, string>;
    orden: string | null;
    opciones: OpcionesDeTarea;
    puede: { crear: boolean };
}>();

const filtro = ref({
    texto: props.filtros.texto ?? '',
    estado: props.filtros.estado ?? '',
    tipo: props.filtros.tipo ?? '',
    prioridad: props.filtros.prioridad ?? '',
    modulo_id: props.filtros.modulo_id ?? '',
    periodo_id: props.filtros.periodo_id ?? '',
    arrastradas: props.filtros.arrastradas ?? '',
});

const orden = ref(props.orden ?? 'codigo');

/*
 * Todo viaja como `filter[…]` y `sort` porque el servidor lo resuelve con
 * spatie/laravel-query-builder. La fase 07 sustituye esta tabla por TanStack sin
 * tocar el controlador.
 */
let temporizador: ReturnType<typeof setTimeout> | undefined;

const aplicar = (): void => {
    const activos = Object.fromEntries(
        Object.entries(filtro.value).filter(([, valor]) => valor !== ''),
    );

    router.get(
        index(props.proyecto.id).url,
        { filter: activos, sort: orden.value },
        { preserveState: true, replace: true },
    );
};

watch(
    filtro,
    () => {
        clearTimeout(temporizador);
        temporizador = setTimeout(aplicar, 300);
    },
    { deep: true },
);

watch(orden, aplicar);

const hayFiltros = computed(() => Object.values(filtro.value).some((valor) => valor !== ''));

const limpiar = (): void => {
    filtro.value = {
        texto: '',
        estado: '',
        tipo: '',
        prioridad: '',
        modulo_id: '',
        periodo_id: '',
        arrastradas: '',
    };
};
</script>

<template>
    <Head :title="$t('tareas.titulo')" />

    <div class="flex flex-col gap-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                :title="$t('tareas.titulo')"
                :description="props.proyecto.nombre"
            />
            <div class="flex items-center gap-2">
                <Button as-child variant="ghost" size="sm">
                    <Link :href="mostrarProyecto(props.proyecto.id)">{{ $t('comun.volver') }}</Link>
                </Button>
                <template v-if="props.puede.crear">
                    <Button as-child variant="secondary" size="sm">
                        <Link :href="capturaRapida(props.proyecto.id)">{{ $t('tareas.rapida.titulo') }}</Link>
                    </Button>
                    <Button as-child size="sm">
                        <Link :href="create(props.proyecto.id)">{{ $t('tareas.crear') }}</Link>
                    </Button>
                </template>
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div class="grid gap-2">
                <Label for="texto">{{ $t('tareas.filtros.texto') }}</Label>
                <Input id="texto" v-model="filtro.texto" type="search" class="w-72" />
            </div>

            <div class="grid gap-2">
                <Label for="estado">{{ $t('comun.estado') }}</Label>
                <select
                    id="estado"
                    v-model="filtro.estado"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                >
                    <option value="">{{ $t('tareas.filtros.todos') }}</option>
                    <option v-for="opcion in props.opciones.estados" :key="opcion.valor" :value="opcion.valor">
                        {{ $t(opcion.etiqueta) }}
                    </option>
                </select>
            </div>

            <div class="grid gap-2">
                <Label for="tipo">{{ $t('tareas.tipo') }}</Label>
                <select
                    id="tipo"
                    v-model="filtro.tipo"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                >
                    <option value="">{{ $t('tareas.filtros.todos') }}</option>
                    <option v-for="opcion in props.opciones.tipos" :key="opcion.valor" :value="opcion.valor">
                        {{ $t(opcion.etiqueta) }}
                    </option>
                </select>
            </div>

            <div class="grid gap-2">
                <Label for="prioridad">{{ $t('tareas.prioridad') }}</Label>
                <select
                    id="prioridad"
                    v-model="filtro.prioridad"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                >
                    <option value="">{{ $t('tareas.filtros.todos') }}</option>
                    <option v-for="opcion in props.opciones.prioridades" :key="opcion.valor" :value="opcion.valor">
                        {{ $t(opcion.etiqueta) }}
                    </option>
                </select>
            </div>

            <div class="grid gap-2">
                <Label for="modulo_id">{{ $t('tareas.modulo') }}</Label>
                <select
                    id="modulo_id"
                    v-model="filtro.modulo_id"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                    :title="$t('tareas.filtros.modulo_incluye_hijos')"
                >
                    <option value="">{{ $t('tareas.filtros.todos') }}</option>
                    <option v-for="modulo in props.opciones.modulos" :key="modulo.id" :value="modulo.id">
                        {{ modulo.ruta }}
                    </option>
                </select>
            </div>

            <div class="grid gap-2">
                <Label for="periodo_id">{{ $t('tareas.periodo') }}</Label>
                <select
                    id="periodo_id"
                    v-model="filtro.periodo_id"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                >
                    <option value="">{{ $t('tareas.filtros.todos') }}</option>
                    <option v-for="periodo in props.opciones.periodos" :key="periodo.id" :value="periodo.id">
                        {{ periodo.consecutivo }}
                    </option>
                </select>
            </div>

            <div class="grid gap-2">
                <Label for="arrastradas">{{ $t('tareas.arrastrada') }}</Label>
                <select
                    id="arrastradas"
                    v-model="filtro.arrastradas"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                >
                    <option value="">{{ $t('tareas.filtros.todos') }}</option>
                    <option value="1">{{ $t('tareas.filtros.solo_arrastradas') }}</option>
                </select>
            </div>

            <div class="grid gap-2">
                <Label for="orden">{{ $t('tareas.orden.titulo') }}</Label>
                <select
                    id="orden"
                    v-model="orden"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                >
                    <option value="codigo">{{ $t('tareas.orden.codigo') }}</option>
                    <option value="nombre">{{ $t('tareas.orden.nombre') }}</option>
                    <option value="estado">{{ $t('tareas.orden.estado') }}</option>
                    <option value="-prioridad">{{ $t('tareas.orden.prioridad') }}</option>
                    <option value="-fecha_fin">{{ $t('tareas.orden.fecha_fin') }}</option>
                    <option value="-updated_at">{{ $t('tareas.orden.updated_at') }}</option>
                </select>
            </div>

            <Button v-if="hayFiltros" variant="ghost" size="sm" @click="limpiar">
                {{ $t('tareas.filtros.limpiar') }}
            </Button>
        </div>

        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">{{ $t('tareas.codigo') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('comun.nombre') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('tareas.modulo') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('comun.estado') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('tareas.periodo') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="tarea in props.tareas.data" :key="tarea.id" class="border-t border-border">
                        <td class="px-4 py-2">
                            <div class="flex items-stretch gap-2">
                                <!-- Barra = prioridad, icono = tipo, badge = estado. -->
                                <BarraPrioridad :prioridad="tarea.prioridad" />
                                <IconoTipo :tipo="tarea.tipo" />
                                <Link
                                    :href="show(tarea.id)"
                                    class="font-mono underline-offset-4 hover:underline"
                                >
                                    {{ tarea.codigo }}
                                </Link>
                            </div>
                        </td>
                        <td class="px-4 py-2">
                            <Link :href="show(tarea.id)" class="underline-offset-4 hover:underline">
                                {{ tarea.nombre }}
                            </Link>
                            <Badge v-if="tarea.arrastrada" variant="outline" class="ml-2">
                                {{ $t('tareas.arrastrada') }}
                            </Badge>
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ tarea.modulo ?? $t('tareas.sin_modulo') }}
                        </td>
                        <td class="px-4 py-2">
                            <BadgeEstado :estado="tarea.estado" tamano="pequeno" />
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ tarea.periodo ?? $t('tareas.sin_periodo') }}
                        </td>
                    </tr>
                    <tr v-if="props.tareas.data.length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-muted-foreground">
                            {{ $t('tareas.sin_tareas') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Paginacion :enlaces="props.tareas.links" />
    </div>
</template>
