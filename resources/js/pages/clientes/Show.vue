<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import EstadoClienteController from '@/actions/App/Http/Controllers/EstadoClienteController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { edit, index } from '@/routes/clientes';
import { show as mostrarProyecto } from '@/routes/proyectos';

const props = defineProps<{
    cliente: {
        id: number;
        nombre: string;
        contacto_nombre: string | null;
        contacto_email: string | null;
        contacto_telefono: string | null;
        notas: string | null;
        activo: boolean;
    };
    proyectos: Array<{
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
        estado: string;
    }>;
    resumen: {
        proyectos_activos: number;
        tareas_abiertas: number;
        ultimo_periodo_cerrado: {
            proyecto: string;
            consecutivo: number;
            fecha_fin: string | null;
        } | null;
    };
    puede: { editar: boolean };
}>();
</script>

<template>
    <Head :title="props.cliente.nombre" />

    <div class="flex flex-col gap-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                :title="props.cliente.nombre"
                :description="props.cliente.contacto_nombre ?? undefined"
            />
            <div class="flex items-center gap-2">
                <Button as-child variant="ghost" size="sm">
                    <Link :href="index()">{{ $t('comun.volver') }}</Link>
                </Button>
                <Button
                    v-if="props.puede.editar"
                    as-child
                    size="sm"
                    variant="secondary"
                >
                    <Link :href="edit(props.cliente.id)">{{
                        $t('comun.editar')
                    }}</Link>
                </Button>
                <Form
                    v-if="props.puede.editar"
                    v-bind="
                        EstadoClienteController.update.form(props.cliente.id)
                    "
                    v-slot="{ processing }"
                >
                    <Button
                        variant="outline"
                        size="sm"
                        type="submit"
                        :disabled="processing"
                    >
                        {{
                            props.cliente.activo
                                ? $t('clientes.desactivar')
                                : $t('clientes.activar')
                        }}
                    </Button>
                </Form>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 text-sm">
            <Badge :variant="props.cliente.activo ? 'secondary' : 'outline'">
                {{
                    props.cliente.activo
                        ? $t('clientes.activo')
                        : $t('clientes.inactivo')
                }}
            </Badge>
            <span
                v-if="props.cliente.contacto_email"
                class="text-muted-foreground"
            >
                {{ props.cliente.contacto_email }}
            </span>
            <span
                v-if="props.cliente.contacto_telefono"
                class="text-muted-foreground"
            >
                {{ props.cliente.contacto_telefono }}
            </span>
        </div>

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-border p-4">
                <p class="text-sm text-muted-foreground">
                    {{ $t('clientes.resumen.proyectos_activos') }}
                </p>
                <p class="text-2xl font-semibold">
                    {{ props.resumen.proyectos_activos }}
                </p>
            </div>
            <div class="rounded-lg border border-border p-4">
                <p class="text-sm text-muted-foreground">
                    {{ $t('clientes.resumen.tareas_abiertas') }}
                </p>
                <p class="text-2xl font-semibold">
                    {{ props.resumen.tareas_abiertas }}
                </p>
            </div>
            <div class="rounded-lg border border-border p-4">
                <p class="text-sm text-muted-foreground">
                    {{ $t('clientes.resumen.ultimo_periodo_cerrado') }}
                </p>
                <p
                    v-if="props.resumen.ultimo_periodo_cerrado"
                    class="text-sm font-medium"
                >
                    {{
                        $t('clientes.resumen.periodo', {
                            consecutivo: String(
                                props.resumen.ultimo_periodo_cerrado
                                    .consecutivo,
                            ),
                            proyecto:
                                props.resumen.ultimo_periodo_cerrado.proyecto,
                        })
                    }}
                    <span class="block text-muted-foreground">
                        {{ props.resumen.ultimo_periodo_cerrado.fecha_fin }}
                    </span>
                </p>
                <p v-else class="text-sm text-muted-foreground">
                    {{ $t('clientes.resumen.sin_periodos_cerrados') }}
                </p>
            </div>
        </section>

        <section class="flex flex-col gap-3">
            <h3 class="text-base font-medium">
                {{ $t('clientes.proyectos') }}
            </h3>

            <div class="overflow-x-auto rounded-lg border border-border">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted text-muted-foreground">
                        <tr>
                            <th class="px-4 py-2 font-medium">
                                {{ $t('comun.nombre') }}
                            </th>
                            <th class="px-4 py-2 font-medium">
                                {{ $t('proyectos.prefijo') }}
                            </th>
                            <th class="px-4 py-2 font-medium">
                                {{ $t('comun.estado') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="proyecto in props.proyectos"
                            :key="proyecto.id"
                            class="border-t border-border"
                        >
                            <td class="px-4 py-2">
                                <Link
                                    :href="mostrarProyecto(proyecto.id)"
                                    class="underline-offset-4 hover:underline"
                                >
                                    {{ proyecto.nombre }}
                                </Link>
                            </td>
                            <td class="px-4 py-2">
                                {{ proyecto.prefijo_tarea }}
                            </td>
                            <td class="px-4 py-2">
                                {{
                                    $t(
                                        `enums.estado_proyecto.${proyecto.estado}`,
                                    )
                                }}
                            </td>
                        </tr>
                        <tr v-if="props.proyectos.length === 0">
                            <td
                                colspan="3"
                                class="px-4 py-6 text-center text-muted-foreground"
                            >
                                {{ $t('clientes.sin_proyectos') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-if="props.cliente.notas" class="flex flex-col gap-2">
            <h3 class="text-base font-medium">{{ $t('clientes.notas') }}</h3>
            <p
                class="max-w-3xl text-sm whitespace-pre-line text-muted-foreground"
            >
                {{ props.cliente.notas }}
            </p>
        </section>
    </div>
</template>
