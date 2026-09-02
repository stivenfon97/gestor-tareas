<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import BarraPrioridad from '@/components/tareas/BarraPrioridad.vue';
import IconoTipo from '@/components/tareas/IconoTipo.vue';
import PanelDeFiltrosDeTareas from '@/components/tareas/PanelDeFiltrosDeTareas.vue';
import SelectorDeVista from '@/components/tareas/SelectorDeVista.vue';
import VistasGuardadas from '@/components/tareas/VistasGuardadas.vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { show as mostrarProyecto } from '@/routes/proyectos';
import { show as mostrarTarea } from '@/routes/tareas';
import type { OpcionesDeTarea } from '@/types/tareas';
import type {
    ConfiguracionDeVista,
    TarjetaDeAgenda,
    VistaGuardada,
} from '@/types/vistas';

/**
 * Calendario mensual por `fecha_fin`.
 *
 * **Las tareas sin fecha van a una franja aparte y visible.** Un calendario que
 * esconde lo que no encaja en su rejilla no es una vista del trabajo, es una
 * vista de la rejilla: enseñaría veinte tareas cuando el proyecto tiene ochenta,
 * y las sesenta que faltan serían justo las que nadie ha fechado.
 *
 * Sin arrastre entre días. Mover una fecha de fin tiene consecuencias de
 * periodo que la fase 09 todavía no ha definido, y abrir la tarea desde su
 * casilla ya resuelve para qué se mira un calendario.
 */
const props = defineProps<{
    proyecto: {
        id: number;
        nombre: string;
        slug: string;
        prefijo_tarea: string;
    };
    mes: string;
    dias: Record<string, TarjetaDeAgenda[]>;
    sinFecha: { total: number; tareas: TarjetaDeAgenda[] };
    filtros: Record<string, string>;
    vistas: VistaGuardada[];
    opciones: OpcionesDeTarea;
}>();

const CLAVES_DE_DIA = [
    'lunes',
    'martes',
    'miercoles',
    'jueves',
    'viernes',
    'sabado',
    'domingo',
] as const;

const inicioDelMes = computed(() => new Date(`${props.mes}-01T00:00:00`));

const hoy = new Date().toISOString().slice(0, 10);

/**
 * Las casillas de la rejilla, incluidas las del mes anterior y el siguiente
 * que completan la primera y la última semana.
 *
 * La semana empieza en lunes, no en domingo: es el calendario laboral con el
 * que se trabaja aquí, y una semana que empieza en domingo parte el fin de
 * semana en dos.
 */
const casillas = computed(() => {
    const primero = inicioDelMes.value;
    // `getDay()` da 0 para domingo; se corrige para que lunes sea 0.
    const desplazamiento = (primero.getDay() + 6) % 7;

    const inicio = new Date(primero);
    inicio.setDate(primero.getDate() - desplazamiento);

    return Array.from({ length: 42 }, (_, indice) => {
        const fecha = new Date(inicio);
        fecha.setDate(inicio.getDate() + indice);

        const iso = [
            fecha.getFullYear(),
            String(fecha.getMonth() + 1).padStart(2, '0'),
            String(fecha.getDate()).padStart(2, '0'),
        ].join('-');

        return {
            iso,
            dia: fecha.getDate(),
            delMes: iso.slice(0, 7) === props.mes,
            esHoy: iso === hoy,
            tareas: props.dias[iso] ?? [],
        };
    });
});

const desplazarMes = (meses: number): void => {
    const fecha = new Date(inicioDelMes.value);
    fecha.setMonth(fecha.getMonth() + meses);

    const destino = `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}`;

    router.get(
        window.location.pathname,
        { ...configuracionActual.value, mes: destino },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const configuracionActual = computed((): ConfiguracionDeVista => {
    const activos = Object.entries(props.filtros).filter(
        ([, valor]) => valor !== '' && valor !== null,
    );

    return activos.length > 0
        ? { filter: Object.fromEntries(activos) as Record<string, string> }
        : {};
});
</script>

<template>
    <Head :title="$t('tablero.agenda.titulo')" />

    <div class="flex min-w-0 flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="$t('tablero.agenda.titulo')"
            :descripcion="props.proyecto.nombre"
        >
            <template #acciones>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="mostrarProyecto(props.proyecto.id)">{{
                        $t('comun.volver')
                    }}</Link>
                </Button>
            </template>

            <template #meta>
                <SelectorDeVista
                    :proyecto="props.proyecto"
                    actual="calendario"
                    :filtros="props.filtros"
                />
                <VistasGuardadas
                    :vistas="props.vistas"
                    tipo="calendario"
                    :proyecto-id="props.proyecto.id"
                    :configuracion-actual="configuracionActual"
                />
            </template>
        </CabeceraDePagina>

        <div class="flex flex-wrap items-center gap-corto">
            <Button
                variant="outline"
                size="icon-sm"
                :title="$t('tablero.agenda.mes_anterior')"
                :aria-label="$t('tablero.agenda.mes_anterior')"
                @click="desplazarMes(-1)"
            >
                <ChevronLeft class="size-4" aria-hidden="true" />
            </Button>
            <span class="font-mono text-cuerpo text-texto">{{
                props.mes
            }}</span>
            <Button
                variant="outline"
                size="icon-sm"
                :title="$t('tablero.agenda.mes_siguiente')"
                :aria-label="$t('tablero.agenda.mes_siguiente')"
                @click="desplazarMes(1)"
            >
                <ChevronRight class="size-4" aria-hidden="true" />
            </Button>

            <PanelDeFiltrosDeTareas
                :filtros="props.filtros"
                :opciones="props.opciones"
                :otros="{ mes: props.mes }"
            />
        </div>

        <div
            class="min-w-0 overflow-x-auto rounded-md border border-borde-fuerte bg-panel"
        >
            <table class="w-full min-w-3xl border-collapse text-cuerpo">
                <caption class="sr-only">
                    {{
                        $t('tablero.agenda.descripcion')
                    }}
                </caption>
                <thead>
                    <tr class="bg-panel-cabecera">
                        <th
                            v-for="clave in CLAVES_DE_DIA"
                            :key="clave"
                            scope="col"
                            class="px-celda py-corto text-left text-micro font-medium text-texto-suave"
                        >
                            {{ $t(`tablero.agenda.dias.${clave}`) }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="semana in 6" :key="semana">
                        <td
                            v-for="casilla in casillas.slice(
                                (semana - 1) * 7,
                                semana * 7,
                            )"
                            :key="casilla.iso"
                            :class="
                                cn(
                                    'h-24 w-[14.28%] border-t border-l border-borde align-top first:border-l-0',
                                    !casilla.delMes && 'bg-panel-cabecera',
                                )
                            "
                        >
                            <div class="flex h-full flex-col gap-minimo p-fino">
                                <span
                                    :class="
                                        cn(
                                            'self-start rounded-sm px-fino font-mono text-fino',
                                            casilla.esHoy
                                                ? 'bg-marca text-marca-contraste'
                                                : casilla.delMes
                                                  ? 'text-texto-suave'
                                                  : 'text-texto-tenue',
                                        )
                                    "
                                >
                                    {{ casilla.dia }}
                                </span>

                                <Link
                                    v-for="tarea in casilla.tareas"
                                    :key="tarea.id"
                                    :href="mostrarTarea(tarea.id)"
                                    class="flex items-stretch gap-fino rounded-sm border border-borde bg-panel px-fino py-minimo hover:bg-fila-hover"
                                    :title="`${tarea.codigo} · ${tarea.nombre}`"
                                >
                                    <BarraPrioridad
                                        :prioridad="tarea.prioridad"
                                    />
                                    <IconoTipo :tipo="tarea.tipo" />
                                    <span class="truncate text-fino text-texto">
                                        {{ tarea.nombre }}
                                    </span>
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!--
            La franja de sin fecha. No es un detalle: si no estuviera, el
            calendario enseñaría veinte tareas de ochenta y las sesenta que
            faltan serían justo las que nadie ha fechado.
        -->
        <section
            v-if="props.sinFecha.total > 0"
            class="flex flex-col gap-corto rounded-md border border-dashed border-borde-fuerte bg-panel px-celda py-corto"
        >
            <div class="flex flex-wrap items-baseline gap-corto">
                <h2 class="text-cuerpo font-medium text-texto">
                    {{ $t('tablero.agenda.sin_fecha') }}
                </h2>
                <span class="font-mono text-fino text-texto-suave">
                    {{ props.sinFecha.total }}
                </span>
                <p class="text-fino text-texto-suave">
                    {{ $t('tablero.agenda.sin_fecha_ayuda') }}
                </p>
            </div>

            <ul class="flex flex-wrap gap-corto">
                <li v-for="tarea in props.sinFecha.tareas" :key="tarea.id">
                    <Link
                        :href="mostrarTarea(tarea.id)"
                        class="flex items-stretch gap-fino rounded-sm border border-borde bg-panel-cabecera px-corto py-fino hover:bg-fila-hover"
                    >
                        <BarraPrioridad :prioridad="tarea.prioridad" />
                        <IconoTipo :tipo="tarea.tipo" />
                        <span class="font-mono text-fino text-texto-suave">
                            {{ tarea.codigo }}
                        </span>
                        <span class="max-w-60 truncate text-fino text-texto">
                            {{ tarea.nombre }}
                        </span>
                    </Link>
                </li>
            </ul>

            <p
                v-if="props.sinFecha.tareas.length < props.sinFecha.total"
                class="text-fino text-texto-tenue"
            >
                {{
                    $t('tablero.agenda.sin_fecha_recortadas', {
                        mostradas: String(props.sinFecha.tareas.length),
                        total: String(props.sinFecha.total),
                    })
                }}
            </p>
        </section>
    </div>
</template>
