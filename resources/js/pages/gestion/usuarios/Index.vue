<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import EstadoUsuarioController from '@/actions/App/Http/Controllers/Gestion/EstadoUsuarioController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, edit } from '@/routes/usuarios';

type Usuario = {
    id: number;
    name: string;
    email: string;
    activo: boolean;
    rol_global: string | null;
};

defineProps<{
    usuarios: { data: Usuario[] };
}>();
</script>

<template>
    <Head :title="$t('gestion.usuarios.titulo')" />

    <div class="flex flex-col gap-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                :title="$t('gestion.usuarios.titulo')"
                :description="$t('gestion.usuarios.sin_registro_publico')"
            />
            <Button as-child size="sm">
                <Link :href="create()">{{ $t('gestion.usuarios.crear') }}</Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">{{ $t('comun.nombre') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('comun.correo') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('gestion.usuarios.rol_global') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('comun.estado') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('comun.acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="usuario in usuarios.data"
                        :key="usuario.id"
                        class="border-t border-border"
                    >
                        <td class="px-4 py-2">{{ usuario.name }}</td>
                        <td class="px-4 py-2 text-muted-foreground">{{ usuario.email }}</td>
                        <td class="px-4 py-2">
                            <span v-if="usuario.rol_global">
                                {{ $t(`enums.rol_global.${usuario.rol_global}`) }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <Badge :variant="usuario.activo ? 'secondary' : 'outline'">
                                {{ usuario.activo ? $t('gestion.usuarios.activo') : $t('gestion.usuarios.inactivo') }}
                            </Badge>
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex items-center gap-2">
                                <Button as-child variant="ghost" size="sm">
                                    <Link :href="edit(usuario.id)">{{ $t('comun.editar') }}</Link>
                                </Button>
                                <Form
                                    v-bind="EstadoUsuarioController.update.form(usuario.id)"
                                    v-slot="{ processing }"
                                >
                                    <Button variant="ghost" size="sm" type="submit" :disabled="processing">
                                        {{ usuario.activo ? $t('gestion.usuarios.desactivar') : $t('gestion.usuarios.activar') }}
                                    </Button>
                                </Form>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="usuarios.data.length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-muted-foreground">
                            {{ $t('comun.sin_resultados') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-sm text-muted-foreground">{{ $t('gestion.usuarios.nota_desactivar') }}</p>
    </div>
</template>
