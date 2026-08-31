<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import EstadoClienteController from '@/actions/App/Http/Controllers/EstadoClienteController';
import Heading from '@/components/Heading.vue';
import Paginacion from '@/components/Paginacion.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index, show } from '@/routes/clientes';
import type { RespuestaPaginada } from '@/types/paginacion';

type Cliente = {
    id: number;
    nombre: string;
    contacto_nombre: string | null;
    contacto_email: string | null;
    activo: boolean;
    proyectos_count: number;
    proyectos_activos_count: number;
};

const props = defineProps<{
    clientes: RespuestaPaginada<Cliente>;
    filtros: { nombre: string | null; activo: string | null };
    puede: { crear: boolean };
}>();

const nombre = ref(props.filtros.nombre ?? '');
const activo = ref(props.filtros.activo ?? '');

/*
 * El filtro viaja como `filter[…]` porque el servidor lo resuelve con
 * spatie/laravel-query-builder. La fase 07 cambia esta tabla por el componente
 * de TanStack sin tocar el controlador.
 */
let temporizador: ReturnType<typeof setTimeout> | undefined;

watch([nombre, activo], () => {
    clearTimeout(temporizador);
    temporizador = setTimeout(() => {
        router.get(
            index().url,
            {
                filter: {
                    ...(nombre.value ? { nombre: nombre.value } : {}),
                    ...(activo.value !== '' ? { activo: activo.value } : {}),
                },
            },
            { preserveState: true, replace: true },
        );
    }, 300);
});
</script>

<template>
    <Head :title="$t('clientes.titulo')" />

    <div class="flex flex-col gap-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                :title="$t('clientes.titulo')"
                :description="$t('clientes.nota_desactivar')"
            />
            <Button v-if="props.puede.crear" as-child size="sm">
                <Link :href="create()">{{ $t('clientes.crear') }}</Link>
            </Button>
        </div>

        <div class="flex flex-wrap items-end gap-4">
            <div class="grid gap-2">
                <Label for="buscar">{{ $t('clientes.buscar') }}</Label>
                <Input id="buscar" v-model="nombre" type="search" class="w-64" />
            </div>
            <div class="grid gap-2">
                <Label for="activo">{{ $t('comun.estado') }}</Label>
                <select
                    id="activo"
                    v-model="activo"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                >
                    <option value="">{{ $t('clientes.todos') }}</option>
                    <option value="1">{{ $t('clientes.activo') }}</option>
                    <option value="0">{{ $t('clientes.inactivo') }}</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">{{ $t('comun.nombre') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('clientes.contacto_nombre') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('clientes.proyectos') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('comun.estado') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('comun.acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="cliente in props.clientes.data" :key="cliente.id" class="border-t border-border">
                        <td class="px-4 py-2">
                            <Link :href="show(cliente.id)" class="underline-offset-4 hover:underline">
                                {{ cliente.nombre }}
                            </Link>
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ cliente.contacto_nombre }}
                        </td>
                        <td class="px-4 py-2">
                            {{ cliente.proyectos_activos_count }} / {{ cliente.proyectos_count }}
                        </td>
                        <td class="px-4 py-2">
                            <Badge :variant="cliente.activo ? 'secondary' : 'outline'">
                                {{ cliente.activo ? $t('clientes.activo') : $t('clientes.inactivo') }}
                            </Badge>
                        </td>
                        <td class="px-4 py-2">
                            <Form
                                v-if="props.puede.crear"
                                v-bind="EstadoClienteController.update.form(cliente.id)"
                                v-slot="{ processing }"
                            >
                                <Button variant="ghost" size="sm" type="submit" :disabled="processing">
                                    {{ cliente.activo ? $t('clientes.desactivar') : $t('clientes.activar') }}
                                </Button>
                            </Form>
                        </td>
                    </tr>
                    <tr v-if="props.clientes.data.length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-muted-foreground">
                            {{ $t('comun.sin_resultados') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Paginacion :enlaces="props.clientes.links" />
    </div>
</template>
