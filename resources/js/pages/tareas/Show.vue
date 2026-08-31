<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import BadgeEstado from '@/components/tareas/BadgeEstado.vue';
import BarraPrioridad from '@/components/tareas/BarraPrioridad.vue';
import ControlDeEstado from '@/components/tareas/ControlDeEstado.vue';
import IconoTipo from '@/components/tareas/IconoTipo.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { index as tareasDelProyecto } from '@/routes/proyectos/tareas';
import { destroy, edit, show, update } from '@/routes/tareas';
import { store as guardarNota } from '@/routes/tareas/notas';
import type { EstadoTarea, PrioridadTarea, Transicion, TipoTarea } from '@/types/tareas';

const props = defineProps<{
    tarea: {
        id: number;
        codigo: string;
        nombre: string;
        descripcion: string | null;
        estado: EstadoTarea;
        tipo: TipoTarea;
        prioridad: PrioridadTarea;
        motivo_espera: string | null;
        motivo_cancelacion: string | null;
        fecha_inicio: string | null;
        fecha_fin: string | null;
        creada_por: string | null;
        proyecto: { id: number; nombre: string; slug: string };
        modulo: { id: number; ruta: string } | null;
        periodo: number | null;
        periodo_origen: number | null;
        arrastrada: boolean;
        origen: { id: number; codigo: string; nombre: string; estado: EstadoTarea } | null;
    };
    transiciones: Transicion[];
    notas: Array<{
        id: number;
        contenido: string;
        visibilidad: string;
        autor: string | null;
        creada: string | null;
    }>;
    historial: Array<{
        id: number;
        anterior: EstadoTarea | null;
        nuevo: EstadoTarea;
        usuario: string | null;
        cuando: string | null;
        comentario: string | null;
    }>;
    correcciones: Array<{
        id: number;
        codigo: string;
        nombre: string;
        estado: EstadoTarea;
        tipo: TipoTarea;
    }>;
    puede: {
        editar: boolean;
        cambiarEstado: boolean;
        eliminar: boolean;
        comentar: boolean;
        verLoInterno: boolean;
    };
}>();

// Descripción editable en línea: abrir un formulario entero para corregir una
// frase es la clase de fricción que hace que la descripción no se corrija.
const editandoDescripcion = ref(false);

const descripcion = useForm({
    nombre: props.tarea.nombre,
    prioridad: props.tarea.prioridad,
    descripcion: props.tarea.descripcion ?? '',
    modulo_id: props.tarea.modulo?.id ?? '',
});

const guardarDescripcion = (): void => {
    descripcion.put(update(props.tarea.id).url, {
        preserveScroll: true,
        onSuccess: () => (editandoDescripcion.value = false),
    });
};

const nota = useForm({ contenido: '', visibilidad: 'interna' });

const agregarNota = (): void => {
    nota.post(guardarNota(props.tarea.id).url, {
        preserveScroll: true,
        onSuccess: () => nota.reset(),
    });
};

const eliminar = (): void => {
    router.delete(destroy(props.tarea.id).url);
};
</script>

<template>
    <Head :title="props.tarea.codigo" />

    <div class="flex flex-col gap-8">
        <!-- Cabecera: barra de prioridad, icono de tipo, código y badge. -->
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-stretch gap-3">
                <BarraPrioridad :prioridad="props.tarea.prioridad" />
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2">
                        <IconoTipo :tipo="props.tarea.tipo" />
                        <span class="font-mono text-sm text-muted-foreground">{{ props.tarea.codigo }}</span>
                        <BadgeEstado :estado="props.tarea.estado" tamano="pequeno" />
                    </div>
                    <Heading variant="small" :title="props.tarea.nombre" />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Button as-child variant="ghost" size="sm">
                    <Link :href="tareasDelProyecto(props.tarea.proyecto.id)">{{ $t('comun.volver') }}</Link>
                </Button>
                <Button v-if="props.puede.editar" as-child variant="secondary" size="sm">
                    <Link :href="edit(props.tarea.id)">{{ $t('comun.editar') }}</Link>
                </Button>
                <Button v-if="props.puede.eliminar" variant="outline" size="sm" @click="eliminar">
                    {{ $t('tareas.eliminar') }}
                </Button>
            </div>
        </div>

        <!-- Metadatos -->
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-muted-foreground">
            <span>{{ $t('tareas.tipo') }}: {{ $t(`enums.tipo_tarea.${props.tarea.tipo}`) }}</span>
            <span>{{ $t('tareas.prioridad') }}: {{ $t(`enums.prioridad_tarea.${props.tarea.prioridad}`) }}</span>
            <span>{{ $t('tareas.modulo') }}: {{ props.tarea.modulo?.ruta ?? $t('tareas.sin_modulo') }}</span>
            <span>{{ $t('tareas.periodo') }}: {{ props.tarea.periodo ?? $t('tareas.sin_periodo') }}</span>
            <span v-if="props.tarea.fecha_inicio">
                {{ $t('tareas.fecha_inicio') }}: {{ props.tarea.fecha_inicio }}
            </span>
            <span v-if="props.tarea.fecha_fin">
                {{ $t('tareas.fecha_fin') }}: {{ props.tarea.fecha_fin }}
            </span>
            <span v-if="props.tarea.creada_por">
                {{ $t('tareas.creada_por') }}: {{ props.tarea.creada_por }}
            </span>
            <Badge v-if="props.tarea.arrastrada" variant="outline">
                {{ $t('tareas.arrastrada_de', { consecutivo: String(props.tarea.periodo_origen ?? '—') }) }}
            </Badge>
        </div>

        <!-- Estado y transiciones -->
        <section class="flex flex-col gap-3">
            <h3 class="text-base font-medium">{{ $t('tareas.estado.titulo') }}</h3>

            <ControlDeEstado
                :tarea-id="props.tarea.id"
                :estado="props.tarea.estado"
                :transiciones="props.transiciones"
                :puede-cambiar="props.puede.cambiarEstado"
            />

            <p v-if="props.tarea.motivo_espera" class="text-sm text-muted-foreground">
                {{ $t('tareas.estado.motivo_espera') }}: {{ props.tarea.motivo_espera }}
            </p>
            <p v-if="props.tarea.motivo_cancelacion" class="text-sm text-muted-foreground">
                {{ $t('tareas.estado.motivo_cancelacion') }}: {{ props.tarea.motivo_cancelacion }}
            </p>
        </section>

        <!-- Descripción editable en línea -->
        <section class="flex flex-col gap-2">
            <div class="flex items-center justify-between gap-4">
                <h3 class="text-base font-medium">{{ $t('tareas.descripcion') }}</h3>
                <Button
                    v-if="props.puede.editar && ! editandoDescripcion"
                    variant="ghost"
                    size="sm"
                    @click="editandoDescripcion = true"
                >
                    {{ $t('comun.editar') }}
                </Button>
            </div>

            <template v-if="editandoDescripcion">
                <textarea
                    v-model="descripcion.descripcion"
                    rows="6"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                />
                <InputError :message="descripcion.errors.descripcion" />
                <div class="flex items-center gap-2">
                    <Button size="sm" :disabled="descripcion.processing" @click="guardarDescripcion">
                        {{ $t('comun.guardar') }}
                    </Button>
                    <Button variant="ghost" size="sm" @click="editandoDescripcion = false">
                        {{ $t('comun.cancelar') }}
                    </Button>
                </div>
            </template>

            <p v-else class="max-w-3xl whitespace-pre-line text-sm text-muted-foreground">
                {{ props.tarea.descripcion }}
            </p>
        </section>

        <!-- Vínculo con su origen y con las correcciones que generó -->
        <section v-if="props.tarea.origen" class="flex flex-col gap-2">
            <h3 class="text-base font-medium">{{ $t('tareas.origen') }}</h3>
            <Link
                :href="show(props.tarea.origen.id)"
                class="flex items-center gap-2 text-sm underline-offset-4 hover:underline"
            >
                <span class="font-mono">{{ props.tarea.origen.codigo }}</span>
                <span>{{ props.tarea.origen.nombre }}</span>
                <BadgeEstado :estado="props.tarea.origen.estado" tamano="pequeno" />
            </Link>
        </section>

        <section class="flex flex-col gap-2">
            <h3 class="text-base font-medium">
                {{ $t('tareas.correcciones') }}
                <span v-if="props.correcciones.length" class="text-sm font-normal text-muted-foreground">
                    ({{ props.correcciones.length }})
                </span>
            </h3>

            <ul v-if="props.correcciones.length" class="flex flex-col gap-1">
                <li v-for="correccion in props.correcciones" :key="correccion.id">
                    <Link
                        :href="show(correccion.id)"
                        class="flex items-center gap-2 text-sm underline-offset-4 hover:underline"
                    >
                        <IconoTipo :tipo="correccion.tipo" />
                        <span class="font-mono">{{ correccion.codigo }}</span>
                        <span>{{ correccion.nombre }}</span>
                        <BadgeEstado :estado="correccion.estado" tamano="pequeno" />
                    </Link>
                </li>
            </ul>
            <p v-else class="text-sm text-muted-foreground">{{ $t('tareas.sin_correcciones') }}</p>
        </section>

        <!-- Notas -->
        <section class="flex flex-col gap-3">
            <h3 class="text-base font-medium">{{ $t('tareas.notas.titulo') }}</h3>

            <form v-if="props.puede.comentar" class="flex flex-col gap-2" @submit.prevent="agregarNota">
                <Label for="nota">{{ $t('tareas.notas.contenido') }}</Label>
                <textarea
                    id="nota"
                    v-model="nota.contenido"
                    rows="3"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                />
                <InputError :message="nota.errors.contenido" />

                <div class="flex flex-wrap items-center gap-3">
                    <select
                        v-model="nota.visibilidad"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                        :aria-label="$t('tareas.notas.visibilidad')"
                    >
                        <option value="interna">{{ $t('enums.visibilidad.interna') }}</option>
                        <option value="cliente">{{ $t('enums.visibilidad.cliente') }}</option>
                    </select>
                    <Button type="submit" size="sm" :disabled="nota.processing">
                        {{ $t('tareas.notas.agregar') }}
                    </Button>
                    <span class="text-xs text-muted-foreground">{{ $t('tareas.notas.interna_ayuda') }}</span>
                </div>
            </form>

            <ul v-if="props.notas.length" class="flex flex-col gap-3">
                <li
                    v-for="registro in props.notas"
                    :key="registro.id"
                    class="rounded-lg border border-border p-3"
                >
                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <span>{{ registro.autor }}</span>
                        <span>{{ registro.creada }}</span>
                        <Badge variant="outline">
                            {{ $t(`enums.visibilidad.${registro.visibilidad}`) }}
                        </Badge>
                    </div>
                    <p class="mt-1 whitespace-pre-line text-sm">{{ registro.contenido }}</p>
                </li>
            </ul>
            <p v-else class="text-sm text-muted-foreground">{{ $t('tareas.notas.vacio') }}</p>
        </section>

        <!-- Historial: solo lectura, siempre -->
        <section class="flex flex-col gap-3">
            <h3 class="text-base font-medium">{{ $t('tareas.historial.titulo') }}</h3>

            <ol v-if="props.historial.length" class="flex flex-col gap-3 border-l border-border pl-4">
                <li v-for="registro in props.historial" :key="registro.id" class="flex flex-col gap-1">
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <BadgeEstado
                            v-if="registro.anterior"
                            :estado="registro.anterior"
                            tamano="pequeno"
                        />
                        <span v-else class="text-muted-foreground">{{ $t('tareas.historial.inicial') }}</span>
                        <span class="text-muted-foreground">→</span>
                        <BadgeEstado :estado="registro.nuevo" tamano="pequeno" />
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ registro.cuando }}
                        <span v-if="registro.usuario">
                            {{ $t('tareas.historial.por', { usuario: registro.usuario }) }}
                        </span>
                    </p>
                    <p v-if="registro.comentario" class="text-sm">{{ registro.comentario }}</p>
                </li>
            </ol>
            <p v-else class="text-sm text-muted-foreground">{{ $t('tareas.historial.vacio') }}</p>
        </section>
    </div>
</template>
