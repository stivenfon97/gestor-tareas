<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Building2,
    FolderGit2,
    History,
    LayoutGrid,
    ListChecks,
    Users,
    UsersRound,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { usePermisos } from '@/composables/usePermisos';
import { dashboard } from '@/routes';
import { index as clientesIndex } from '@/routes/clientes';
import { index as equiposIndex } from '@/routes/equipos';
import { index as historialIndex } from '@/routes/historial';
import { index as proyectosIndex } from '@/routes/proyectos';
import { index as tareasIndex } from '@/routes/tareas';
import { index as usuariosIndex } from '@/routes/usuarios';
import type { NavItem } from '@/types';

const { puede, esSoloLectura } = usePermisos();

/*
 * Ocultar una entrada del menú no es un permiso: la ruta la protege su policy
 * o su middleware igualmente. Esto solo evita enseñar puertas cerradas.
 */
/**
 * El menú va por módulos: lo que se usa a diario arriba y la administración de
 * personas abajo, pegada al usuario, para que no se mezcle con el trabajo.
 */
type GrupoDeMenu = { etiqueta: string; items: NavItem[] };

const gruposPrincipales = computed<GrupoDeMenu[]>(() => {
    const grupos: GrupoDeMenu[] = [
        {
            etiqueta: 'nav.grupos.general',
            items: [
                { title: 'nav.dashboard', href: dashboard(), icon: LayoutGrid },
            ],
        },
        {
            /*
             * Tareas va antes que proyectos: es la pantalla que más se usa.
             * El listado se acota solo: `Tarea::visiblesPara()` no devuelve
             * nada de un proyecto en el que no participas.
             */
            etiqueta: 'nav.grupos.trabajo',
            items: [
                { title: 'nav.tareas', href: tareasIndex(), icon: ListChecks },
                {
                    title: 'nav.proyectos',
                    href: proyectosIndex(),
                    icon: FolderGit2,
                },
            ],
        },
    ];

    // El catálogo de clientes es interno: `ClientePolicy@viewAny` lo niega al
    // rol global `invitado`, así que tampoco se le enseña la puerta.
    if (!esSoloLectura.value) {
        grupos.push({
            etiqueta: 'nav.grupos.comercial',
            items: [
                {
                    title: 'nav.clientes',
                    href: clientesIndex(),
                    icon: Building2,
                },
            ],
        });
    }

    return grupos;
});

/*
 * Ocultar una entrada del menú no es un permiso: la ruta la protege su policy
 * o su middleware igualmente. Esto solo evita enseñar puertas cerradas.
 */
const grupoDeAdministracion = computed<GrupoDeMenu | null>(() => {
    const items: NavItem[] = [];

    if (puede('usuarios.gestionar')) {
        items.push(
            { title: 'nav.usuarios', href: usuariosIndex(), icon: Users },
            { title: 'nav.equipos', href: equiposIndex(), icon: UsersRound },
        );
    }

    // El historial global cruza todos los proyectos: tiene permiso propio.
    if (puede('historial.ver')) {
        items.push({
            title: 'nav.historial',
            href: historialIndex(),
            icon: History,
        });
    }

    return items.length
        ? { etiqueta: 'nav.grupos.administracion', items }
        : null;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <!--
            El conmutador del menú vive aquí desde la fase 08. Antes estaba en
            una franja de 48px que cruzaba la pantalla para alojarlo a él y al
            de tema, y nada más. Plegado a iconos el logotipo se esconde y queda
            solo el conmutador, que es lo que hace falta para volver a abrirlo.
        -->
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem
                    class="flex items-center gap-fino group-data-[collapsible=icon]:justify-center"
                >
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="group-data-[collapsible=icon]:hidden"
                    >
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                    <SidebarTrigger class="shrink-0" />
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain
                v-for="grupo in gruposPrincipales"
                :key="grupo.etiqueta"
                :etiqueta="grupo.etiqueta"
                :items="grupo.items"
            />
        </SidebarContent>

        <SidebarFooter class="gap-corto">
            <NavMain
                v-if="grupoDeAdministracion"
                :etiqueta="grupoDeAdministracion.etiqueta"
                :items="grupoDeAdministracion.items"
                class="px-0"
            />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
