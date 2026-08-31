<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { create, edit } from '@/routes/equipos';

type Equipo = {
    id: number;
    nombre: string;
    descripcion: string | null;
    usuarios_count: number;
};

defineProps<{
    equipos: { data: Equipo[] };
}>();
</script>

<template>
    <Head :title="$t('gestion.equipos.titulo')" />

    <div class="flex flex-col gap-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                :title="$t('gestion.equipos.titulo')"
                :description="$t('gestion.equipos.nota_no_otorga_permisos')"
            />
            <Button as-child size="sm">
                <Link :href="create()">{{ $t('gestion.equipos.crear') }}</Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">{{ $t('comun.nombre') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('comun.descripcion') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('gestion.equipos.cantidad_miembros') }}</th>
                        <th class="px-4 py-2 font-medium">{{ $t('comun.acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="equipo in equipos.data" :key="equipo.id" class="border-t border-border">
                        <td class="px-4 py-2">{{ equipo.nombre }}</td>
                        <td class="px-4 py-2 text-muted-foreground">{{ equipo.descripcion }}</td>
                        <td class="px-4 py-2">{{ equipo.usuarios_count }}</td>
                        <td class="px-4 py-2">
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="edit(equipo.id)">{{ $t('comun.editar') }}</Link>
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="equipos.data.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">
                            {{ $t('comun.sin_resultados') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
