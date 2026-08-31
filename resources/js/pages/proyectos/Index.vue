<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import Paginacion from '@/components/Paginacion.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index, show } from '@/routes/proyectos';
import type { RespuestaPaginada } from '@/types/paginacion';

type Proyecto = {
    id: number;
    nombre: string;
    prefijo_tarea: string;
    estado: string;
    cliente: { id: number; nombre: string } | null;
    tareas_abiertas_count: number;
};

const props = defineProps<{
    proyectos: RespuestaPaginada<Proyecto>;
    filtros: { nombre: string | null; estado: string | null };
    estados: Array<{ valor: string; etiqueta: string }>;
    puede: { crear: boolean };
}>();

const nombre = ref(props.filtros.nombre ?? '');
const estado = ref(props.filtros.estado ?? '');

let temporizador: ReturnType<typeof setTimeout> | undefined;

watch([nombre, estado], () => {
    clearTimeout(temporizador);
    temporizador = setTimeout(() => {
        router.get(
            index().url,
            {
                filter: {
                    ...(nombre.value ? { nombre: nombre.value } : {}),
                    ...(estado.value ? { estado: estado.value } : {}),
                },
            },
            { preserveState: true, replace: true },
        );
    }, 300);
});
</script>

<template>
    <Head :title="$t('proyectos.titulo')" />

    <div class="flex flex-col gap-6">
        <div class="flex items-start justify-between gap-4">
            <Heading variant="small" :title="$t('proyectos.titulo')" />
            <Button v-if="props.puede.crear" as-child size="sm">
                <Link :href="create()">{{ $t('proyectos.crear') }}</Link>
            </Button>
        </div>

        <div class="flex flex-wrap items-end gap-4">
            <div class="grid gap-2">
                <Label for="buscar">{{ $t('proyectos.buscar') }}</Label>
                <Input id="buscar" v-model="nombre" type="search" class="w-64" />
            </div>
            <div class="grid gap-2">
                <Label for="estado">{{ $t('comun.estado') }}</Label>
                <select
                    id="estado"
                    v-model="estado"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                >
                    <option value="">{{ $t('proyectos.todos_los_estados') }}</option>
                    <option v-for="opcion in props.estados" :key="opcion.valor" :value="opcion.valor">
                        {{ $t(opcion.etiqueta) }}
                    </option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">{{ $t('comun.nombre') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('proyectos.cliente') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('proyectos.prefijo') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('proyectos.tareas_abiertas') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('comun.estado') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="proyecto in props.proyectos.data" :key="proyecto.id" class="border-t border-border">
                        <td class="px-4 py-2">
                            <Link :href="show(proyecto.id)" class="underline-offset-4 hover:underline">
                                {{ proyecto.nombre }}
                            </Link>
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">{{ proyecto.cliente?.nombre }}</td>
                        <td class="px-4 py-2">
                            <Badge variant="outline">{{ proyecto.prefijo_tarea }}</Badge>
                        </td>
                        <td class="px-4 py-2">{{ proyecto.tareas_abiertas_count }}</td>
                        <td class="px-4 py-2">{{ $t(`enums.estado_proyecto.${proyecto.estado}`) }}</td>
                    </tr>
                    <tr v-if="props.proyectos.data.length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-muted-foreground">
                            {{ $t('proyectos.sin_proyectos') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Paginacion :enlaces="props.proyectos.links" />
    </div>
</template>
