<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import {
    Eye,
    Pencil,
    ShieldCheck,
    UserCheck,
    UserRound,
    Users,
    UserX,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import EstadoUsuarioController from '@/actions/App/Http/Controllers/Gestion/EstadoUsuarioController';
import BotonDeAccion from '@/components/BotonDeAccion.vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import { TablaDatos } from '@/components/TablaDatos';
import type { ColumnaDeTabla, OrdenDeColumna } from '@/components/TablaDatos';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { trans } from '@/lib/i18n';
import { tonoDeActivo, tonoDeRolGlobal } from '@/lib/tonos';
import { create, edit, index } from '@/routes/usuarios';
import type { RespuestaPaginada } from '@/types/paginacion';

type Usuario = {
    id: number;
    name: string;
    email: string;
    activo: boolean;
    rol_global: string | null;
};

const props = defineProps<{
    usuarios: RespuestaPaginada<Usuario>;
    filtros: Record<string, string>;
    orden: OrdenDeColumna[];
    columnasVisibles: string[] | null;
}>();

const columnas = computed((): ColumnaDeTabla<Usuario>[] => [
    {
        id: 'nombre',
        etiqueta: trans('comun.nombre'),
        valor: (usuario) => usuario.name,
        ordenPor: 'name',
        fija: true,
        anclada: true,
        principal: true,
        anchoMinimo: '14rem',
    },
    {
        id: 'correo',
        etiqueta: trans('comun.correo'),
        valor: (usuario) => usuario.email,
        ordenPor: 'email',
        prioritaria: true,
        anchoMinimo: '16rem',
    },
    {
        id: 'rol',
        etiqueta: trans('gestion.usuarios.rol_global'),
        valor: (usuario) => usuario.rol_global,
        ajustada: true,
        prioritaria: true,
    },
    {
        id: 'estado',
        etiqueta: trans('comun.estado'),
        valor: (usuario) => (usuario.activo ? '1' : '0'),
        ajustada: true,
        filtro: {
            tipo: 'opciones',
            clave: 'activo',
            opciones: [
                { valor: '1', etiqueta: trans('gestion.usuarios.activo') },
                { valor: '0', etiqueta: trans('gestion.usuarios.inactivo') },
            ],
        },
        prioritaria: true,
    },
    {
        id: 'acciones',
        etiqueta: trans('comun.acciones'),
        alineacion: 'derecha',
        ajustada: true,
        // En la tarjeta de móvil las acciones se ven sin desplegar «Ver más».
        prioritaria: true,
    },
]);

/** El rol se reconoce por el icono antes de leerlo. */
const ICONO_DE_ROL: Record<string, Component> = {
    administrador: ShieldCheck,
    miembro: UserRound,
    invitado: Eye,
};

const abrir = (usuario: Usuario): void => {
    router.visit(edit(usuario.id).url);
};
</script>

<template>
    <Head :title="$t('gestion.usuarios.titulo')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :icono="Users"
            :titulo="$t('gestion.usuarios.titulo')"
            :descripcion="$t('gestion.usuarios.sin_registro_publico')"
        >
            <template #acciones>
                <Button as-child size="sm">
                    <Link :href="create()">{{
                        $t('gestion.usuarios.crear')
                    }}</Link>
                </Button>
            </template>
        </CabeceraDePagina>

        <TablaDatos
            :url="index().url"
            :filas="props.usuarios.data"
            :columnas="columnas"
            :clave="(usuario) => usuario.id"
            :orden="props.orden"
            :filtros="props.filtros"
            :columnas-visibles="props.columnasVisibles"
            :paginacion="props.usuarios"
            :etiqueta="$t('gestion.usuarios.titulo')"
            :busqueda="{
                clave: 'buscar',
                marcador: $t('gestion.usuarios.buscar'),
            }"
            :vacio="{
                titulo: $t('gestion.usuarios.sin_usuarios'),
                cuerpo: $t('gestion.usuarios.sin_usuarios_ayuda'),
            }"
            @abrir="abrir"
        >
            <template #vacio-acciones>
                <Button as-child size="sm">
                    <Link :href="create()">{{
                        $t('gestion.usuarios.crear')
                    }}</Link>
                </Button>
            </template>

            <template #celda-nombre="{ fila }">
                <Link
                    :href="edit(fila.id)"
                    class="underline-offset-4 hover:underline"
                >
                    {{ fila.name }}
                </Link>
            </template>

            <template #celda-correo="{ fila }">
                <span class="break-all text-texto-suave">{{ fila.email }}</span>
            </template>

            <template #celda-rol="{ fila }">
                <Badge
                    v-if="fila.rol_global"
                    :variant="tonoDeRolGlobal(fila.rol_global)"
                >
                    <component
                        :is="ICONO_DE_ROL[fila.rol_global] ?? UserRound"
                        aria-hidden="true"
                    />
                    {{ $t(`enums.rol_global.${fila.rol_global}`) }}
                </Badge>
                <span v-else class="text-texto-tenue">—</span>
            </template>

            <template #celda-estado="{ fila }">
                <Badge :variant="tonoDeActivo(fila.activo)">
                    <span
                        class="size-1.5 rounded-full bg-current"
                        aria-hidden="true"
                    />
                    {{
                        fila.activo
                            ? $t('gestion.usuarios.activo')
                            : $t('gestion.usuarios.inactivo')
                    }}
                </Badge>
            </template>

            <template #celda-acciones="{ fila }">
                <div class="flex items-center justify-end gap-minimo">
                    <BotonDeAccion
                        :href="edit(fila.id)"
                        :icono="Pencil"
                        tono="info"
                        :etiqueta="$t('gestion.usuarios.editar')"
                    />
                    <Form
                        novalidate
                        v-bind="EstadoUsuarioController.update.form(fila.id)"
                        v-slot="{ processing }"
                    >
                        <BotonDeAccion
                            type="submit"
                            :disabled="processing"
                            :icono="fila.activo ? UserX : UserCheck"
                            :tono="fila.activo ? 'aviso' : 'exito'"
                            :etiqueta="
                                fila.activo
                                    ? $t('gestion.usuarios.desactivar')
                                    : $t('gestion.usuarios.activar')
                            "
                        />
                    </Form>
                </div>
            </template>
        </TablaDatos>

        <p class="text-cuerpo text-texto-suave">
            {{ $t('gestion.usuarios.nota_desactivar') }}
        </p>
    </div>
</template>
