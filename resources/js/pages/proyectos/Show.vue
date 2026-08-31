<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import SeccionPendiente from '@/components/SeccionPendiente.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { destroy, edit } from '@/routes/proyectos';
import { index as miembrosIndex } from '@/routes/proyectos/miembros';
import { index as modulosIndex } from '@/routes/proyectos/modulos';
import { index as pagosIndex } from '@/routes/proyectos/pagos';
import { index as periodosIndex } from '@/routes/proyectos/periodos';
import { index as reunionesIndex } from '@/routes/proyectos/reuniones';
import { index as tareasIndex } from '@/routes/proyectos/tareas';

const props = defineProps<{
    proyecto: {
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
        descripcion: string | null;
        estado: string;
        cliente: { id: number; nombre: string };
        modulos_count: number;
        tareas_count: number;
        tareas_abiertas_count: number;
        miembros_count: number;
    };
    resumen: {
        tareas_por_estado: Record<string, number>;
        periodo_abierto: {
            id: number;
            consecutivo: number;
            fecha_inicio: string;
            fecha_fin: string | null;
            dias_transcurridos: number;
            vencido: boolean;
        } | null;
        ultima_reunion: { id: number; titulo: string; fecha: string } | null;
    };
    miRol: string | null;
    puede: {
        editar: boolean;
        eliminar: boolean;
        gestionarMiembros: boolean;
        gestionarModulos: boolean;
        verPagos: boolean;
        verReuniones: boolean;
        generarReportes: boolean;
    };
}>();

const estados = computed(() => Object.entries(props.resumen.tareas_por_estado));

const eliminar = (): void => {
    router.delete(destroy(props.proyecto.id).url);
};
</script>

<template>
    <Head :title="props.proyecto.nombre" />

    <div class="flex flex-col gap-8">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                :title="props.proyecto.nombre"
                :description="props.proyecto.cliente.nombre"
            />
            <div class="flex items-center gap-2">
                <Button v-if="props.puede.editar" as-child size="sm" variant="secondary">
                    <Link :href="edit(props.proyecto.id)">{{ $t('comun.editar') }}</Link>
                </Button>
                <Button
                    v-if="props.puede.eliminar"
                    size="sm"
                    variant="outline"
                    @click="eliminar"
                >
                    {{ $t('proyectos.eliminar') }}
                </Button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 text-sm">
            <Badge variant="outline">{{ props.proyecto.prefijo_tarea }}</Badge>
            <Badge variant="secondary">{{ $t(`enums.estado_proyecto.${props.proyecto.estado}`) }}</Badge>
            <span v-if="props.miRol" class="text-muted-foreground">
                {{ $t('proyectos.mi_rol') }}: {{ $t(`enums.rol_proyecto.${props.miRol}`) }}
            </span>
        </div>

        <p v-if="props.proyecto.descripcion" class="max-w-3xl text-sm text-muted-foreground">
            {{ props.proyecto.descripcion }}
        </p>

        <!-- Resumen -->
        <section class="flex flex-col gap-4">
            <h3 class="text-base font-medium">{{ $t('proyectos.resumen.titulo') }}</h3>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-border p-4">
                    <p class="text-sm text-muted-foreground">{{ $t('proyectos.resumen.tareas_por_estado') }}</p>
                    <ul v-if="estados.length" class="mt-2 flex flex-col gap-1 text-sm">
                        <li v-for="[estado, total] in estados" :key="estado" class="flex justify-between gap-4">
                            <span>{{ $t(`enums.estado_tarea.${estado}`) }}</span>
                            <span class="font-medium">{{ total }}</span>
                        </li>
                    </ul>
                    <p v-else class="mt-2 text-sm text-muted-foreground">
                        {{ $t('proyectos.resumen.sin_tareas') }}
                    </p>
                </div>

                <div class="rounded-lg border border-border p-4">
                    <p class="text-sm text-muted-foreground">{{ $t('proyectos.resumen.periodo_abierto') }}</p>
                    <template v-if="props.resumen.periodo_abierto">
                        <p class="mt-2 font-medium">
                            {{
                                $t('proyectos.resumen.periodo_numero', {
                                    consecutivo: String(props.resumen.periodo_abierto.consecutivo),
                                })
                            }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{
                                $t('proyectos.resumen.dias_transcurridos', {
                                    dias: String(props.resumen.periodo_abierto.dias_transcurridos),
                                })
                            }}
                        </p>
                        <!-- Pasarse de la fecha de fin es válido: el cierre es manual. -->
                        <p v-if="props.resumen.periodo_abierto.vencido" class="mt-1 text-sm">
                            <Badge variant="outline">{{ $t('proyectos.resumen.vencido') }}</Badge>
                            <span class="mt-1 block text-xs text-muted-foreground">
                                {{ $t('proyectos.resumen.vencido_ayuda') }}
                            </span>
                        </p>
                    </template>
                    <p v-else class="mt-2 text-sm text-muted-foreground">
                        {{ $t('proyectos.resumen.sin_periodo_abierto') }}
                    </p>
                </div>

                <div class="rounded-lg border border-border p-4">
                    <p class="text-sm text-muted-foreground">{{ $t('proyectos.resumen.ultima_reunion') }}</p>
                    <template v-if="props.resumen.ultima_reunion">
                        <p class="mt-2 font-medium">{{ props.resumen.ultima_reunion.titulo }}</p>
                        <p class="text-sm text-muted-foreground">{{ props.resumen.ultima_reunion.fecha }}</p>
                    </template>
                    <p v-else class="mt-2 text-sm text-muted-foreground">
                        {{ $t('proyectos.resumen.sin_reuniones') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Módulos: contenido real desde la fase 05 -->
        <section class="flex flex-col gap-3">
            <div class="flex items-center justify-between gap-4">
                <h3 class="text-base font-medium">{{ $t('proyectos.secciones.modulos') }}</h3>
                <Button as-child variant="outline" size="sm">
                    <Link :href="modulosIndex(props.proyecto.id)">{{ $t('comun.ver') }}</Link>
                </Button>
            </div>
            <p class="text-sm text-muted-foreground">
                {{ $t('proyectos.resumen.modulos') }}: {{ props.proyecto.modulos_count }}
            </p>
        </section>

        <!-- Equipo: contenido real desde la fase 04 -->
        <section class="flex flex-col gap-3">
            <div class="flex items-center justify-between gap-4">
                <h3 class="text-base font-medium">{{ $t('proyectos.secciones.equipo') }}</h3>
                <Button v-if="props.puede.gestionarMiembros" as-child variant="outline" size="sm">
                    <Link :href="miembrosIndex(props.proyecto.id)">{{ $t('comun.ver') }}</Link>
                </Button>
            </div>
            <p class="text-sm text-muted-foreground">
                {{ $t('proyectos.resumen.miembros') }}: {{ props.proyecto.miembros_count }}
            </p>
        </section>

        <!--
            De aquí abajo, secciones sin contenido todavía. Cada una dice qué
            fase la llena. **Nunca datos de mentira**: un placeholder con cifras
            inventadas se olvida y acaba en producción.
        -->
        <!-- Tareas: contenido real desde la fase 06 -->
        <section class="flex flex-col gap-3">
            <div class="flex items-center justify-between gap-4">
                <h3 class="text-base font-medium">{{ $t('proyectos.secciones.tareas') }}</h3>
                <Button as-child variant="outline" size="sm">
                    <Link :href="tareasIndex(props.proyecto.id)">{{ $t('comun.ver') }}</Link>
                </Button>
            </div>
            <p class="text-sm text-muted-foreground">
                {{ $t('proyectos.tareas_abiertas') }}: {{ props.proyecto.tareas_abiertas_count }}
                / {{ props.proyecto.tareas_count }}
            </p>
        </section>

        <SeccionPendiente :titulo="$t('proyectos.secciones.periodos')" :fase="9">
            <Button as-child variant="outline" size="sm">
                <Link :href="periodosIndex(props.proyecto.id)">{{ $t('comun.ver') }}</Link>
            </Button>
        </SeccionPendiente>

        <!-- Ocultarlo no es el permiso: PagoPolicy niega la ruta igualmente. -->
        <SeccionPendiente v-if="props.puede.verPagos" :titulo="$t('proyectos.secciones.pagos')" :fase="9">
            <Button as-child variant="outline" size="sm">
                <Link :href="pagosIndex(props.proyecto.id)">{{ $t('comun.ver') }}</Link>
            </Button>
        </SeccionPendiente>

        <SeccionPendiente
            v-if="props.puede.verReuniones"
            :titulo="$t('proyectos.secciones.reuniones')"
            :fase="10"
        >
            <Button as-child variant="outline" size="sm">
                <Link :href="reunionesIndex(props.proyecto.id)">{{ $t('comun.ver') }}</Link>
            </Button>
        </SeccionPendiente>

        <SeccionPendiente :titulo="$t('proyectos.secciones.archivos')" :fase="10" />

        <SeccionPendiente
            v-if="props.puede.generarReportes"
            :titulo="$t('proyectos.secciones.reportes')"
            :fase="11"
        />
    </div>
</template>
