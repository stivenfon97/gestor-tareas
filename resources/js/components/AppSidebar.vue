<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Building2,
    BookOpen,
    FolderGit2,
    LayoutGrid,
    ListChecks,
    Users,
    UsersRound,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
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
import { index as proyectosIndex } from '@/routes/proyectos';
import { index as tareasIndex } from '@/routes/tareas';
import { index as usuariosIndex } from '@/routes/usuarios';
import type { NavItem } from '@/types';

const { puede, esSoloLectura } = usePermisos();

/*
 * Ocultar una entrada del menú no es un permiso: la ruta la protege su policy
 * o su middleware igualmente. Esto solo evita enseñar puertas cerradas.
 */
const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        { title: 'nav.dashboard', href: dashboard(), icon: LayoutGrid },
        /*
         * Tareas va segundo, antes que proyectos: es la pantalla que más se
         * usa. Hasta la fase 07b no estaba en el menú y solo se llegaba
         * entrando a un proyecto y buscando su pestaña.
         *
         * El listado se acota solo: `Tarea::visiblesPara()` no devuelve nada
         * de un proyecto en el que no participas.
         */
        { title: 'nav.tareas', href: tareasIndex(), icon: ListChecks },
        { title: 'nav.proyectos', href: proyectosIndex(), icon: FolderGit2 },
    ];

    // El catálogo de clientes es interno: `ClientePolicy@viewAny` lo niega al
    // rol global `invitado`, así que tampoco se le enseña la puerta.
    if (!esSoloLectura.value) {
        items.push({
            title: 'nav.clientes',
            href: clientesIndex(),
            icon: Building2,
        });
    }

    if (puede('usuarios.gestionar')) {
        items.push(
            { title: 'nav.usuarios', href: usuariosIndex(), icon: Users },
            { title: 'nav.equipos', href: equiposIndex(), icon: UsersRound },
        );
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'nav.repositorio',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'nav.documentacion',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
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
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
