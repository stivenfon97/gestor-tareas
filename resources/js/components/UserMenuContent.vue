<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Monitor, Moon, Settings, Sun } from '@lucide/vue';
import {
    DropdownMenuCheckboxItem,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { useAppearance } from '@/composables/useAppearance';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
};

/**
 * El tema vive aquí desde la fase 08.
 *
 * Estaba en la barra superior de la aplicación, y al quedarse esa barra sin
 * más contenido que él y el conmutador del menú, la franja entera dejó de
 * ganarse el sitio. Aquí llega igual de rápido, se alcanza con el menú lateral
 * plegado y no cuesta ni un píxel de alto.
 *
 * Siguen siendo **tres posiciones y no dos**: con solo claro y oscuro, elegir
 * «claro» a mediodía deja la aplicación en claro por la noche aunque el sistema
 * entero haya cambiado. «Sistema» es una elección, no su ausencia.
 */
const { appearance, updateAppearance } = useAppearance();

const temas = [
    { valor: 'light', Icono: Sun, clave: 'comun.tema.claro' },
    { valor: 'dark', Icono: Moon, clave: 'comun.tema.oscuro' },
    { valor: 'system', Icono: Monitor, clave: 'comun.tema.sistema' },
] as const;

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                {{ $t('ajustes.titulo') }}
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />

    <DropdownMenuLabel class="text-fino font-normal text-texto-suave">
        {{ $t('comun.tema.titulo') }}
    </DropdownMenuLabel>
    <!-- `preventDefault` en `select`: probar un tema y ver cómo queda exige que
         el menú siga abierto mientras se prueban los tres. -->
    <DropdownMenuCheckboxItem
        v-for="{ valor, Icono, clave } in temas"
        :key="valor"
        :model-value="appearance === valor"
        class="gap-fino text-cuerpo"
        @select="(evento: Event) => evento.preventDefault()"
        @update:model-value="() => updateAppearance(valor)"
    >
        <component :is="Icono" class="size-4" aria-hidden="true" />
        {{ $t(clave) }}
    </DropdownMenuCheckboxItem>

    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            {{ $t('auth.salir') }}
        </Link>
    </DropdownMenuItem>
</template>
