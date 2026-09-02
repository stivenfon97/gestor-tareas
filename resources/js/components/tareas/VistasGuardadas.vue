<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Bookmark, Check, Star } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { trans } from '@/lib/i18n';
import { destroy, duplicar, store, update } from '@/routes/vistas';
import type {
    ConfiguracionDeVista,
    TipoDeVista,
    VistaGuardada,
} from '@/types/vistas';

/**
 * Guardar, aplicar y administrar vistas.
 *
 * Como el estado de una vista vive en la URL desde la fase 07, guardar una
 * vista es **guardar la cadena de consulta con un nombre**, y aplicarla es
 * navegar. No hay ningún mecanismo paralelo que pueda quedar desincronizado.
 */
const props = defineProps<{
    vistas: VistaGuardada[];
    tipo: TipoDeVista;
    proyectoId: number | null;
    /** Los parámetros de la URL de ahora, que es lo que se guardaría. */
    configuracionActual: ConfiguracionDeVista;
}>();

const abierto = ref(false);
const nombre = ref('');
const comoPredeterminada = ref(false);

/**
 * Qué vista está aplicada ahora mismo, si es que alguna lo está.
 *
 * Se compara la configuración serializada, no el id: no hay ningún id en la
 * URL, y **eso es a propósito** —una vista guardada no es un modo, es un atajo
 * a una URL—. Si el usuario toca un filtro después de aplicarla, deja de
 * coincidir, que es exactamente lo que hay que decirle.
 */
const huella = (configuracion: ConfiguracionDeVista): string =>
    JSON.stringify(configuracion, Object.keys(configuracion).sort());

const actual = computed(() =>
    props.vistas.find(
        (vista) =>
            huella(vista.configuracion) === huella(props.configuracionActual),
    ),
);

const difiere = computed(
    () => actual.value === undefined && props.vistas.length > 0,
);

/*
 * El aviso de «se aplicó en parte» se da **una vez por vista**, no en cada
 * render: un aviso que reaparece cada vez que se repinta la pantalla se
 * aprende a ignorar antes de leerlo.
 */
const avisadas = new Set<number>();

watch(
    () => props.vistas,
    (vistas) => {
        for (const vista of vistas) {
            if (vista.parcial && !avisadas.has(vista.id)) {
                avisadas.add(vista.id);
                toast.warning(
                    trans('vistas.parcial', { nombre: vista.nombre }),
                );
            }
        }
    },
    { immediate: true, deep: true },
);

/** Aplicar es navegar: la barra de direcciones dice qué se está viendo. */
const aplicar = (vista: VistaGuardada): void => {
    router.get(window.location.pathname, vista.configuracion, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const guardar = (): void => {
    if (nombre.value.trim() === '') {
        return;
    }

    router.post(
        store().url,
        {
            nombre: nombre.value.trim(),
            tipo: props.tipo,
            proyecto_id: props.proyectoId,
            configuracion: props.configuracionActual,
            es_predeterminada: comoPredeterminada.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                abierto.value = false;
                nombre.value = '';
                comoPredeterminada.value = false;
            },
        },
    );
};

const renombrar = (vista: VistaGuardada): void => {
    const nuevo = window.prompt(trans('vistas.renombrar'), vista.nombre);

    if (nuevo === null || nuevo.trim() === '') {
        return;
    }

    router.put(
        update(vista.id).url,
        { nombre: nuevo.trim(), configuracion: vista.configuracion },
        { preserveScroll: true },
    );
};

/** Guardar sobre la vista actual: es lo que resuelve «cambios sin guardar». */
const sobrescribir = (vista: VistaGuardada): void => {
    router.put(
        update(vista.id).url,
        { nombre: vista.nombre, configuracion: props.configuracionActual },
        { preserveScroll: true },
    );
};

const marcarPredeterminada = (vista: VistaGuardada): void => {
    router.put(
        update(vista.id).url,
        {
            nombre: vista.nombre,
            configuracion: vista.configuracion,
            es_predeterminada: !vista.es_predeterminada,
        },
        { preserveScroll: true },
    );
};
</script>

<template>
    <div class="flex items-center gap-corto">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="outline" size="sm" class="gap-fino">
                    <Bookmark class="size-4" aria-hidden="true" />
                    <span class="max-w-40 truncate">
                        {{ actual?.nombre ?? $t('vistas.titulo') }}
                    </span>
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="start" class="w-72">
                <DropdownMenuLabel
                    class="text-fino font-normal text-texto-suave"
                >
                    {{ $t('vistas.titulo') }}
                </DropdownMenuLabel>
                <DropdownMenuSeparator />

                <p
                    v-if="props.vistas.length === 0"
                    class="px-celda py-corto text-fino text-texto-suave"
                >
                    {{ $t('vistas.ninguna') }}
                </p>

                <DropdownMenuItem
                    v-for="vista in props.vistas"
                    :key="vista.id"
                    class="flex items-start gap-fino text-cuerpo"
                    @select="aplicar(vista)"
                >
                    <Check
                        v-if="actual?.id === vista.id"
                        class="mt-fino size-3.5 shrink-0 text-marca"
                        aria-hidden="true"
                    />
                    <span class="flex min-w-0 flex-1 flex-col">
                        <span class="flex items-center gap-fino truncate">
                            {{ vista.nombre }}
                            <Star
                                v-if="vista.es_predeterminada"
                                class="size-3 shrink-0 text-marca"
                                :aria-label="$t('vistas.predeterminada')"
                            />
                        </span>
                        <span class="text-fino text-texto-suave">
                            {{ $t(`vistas.ambito.${vista.ambito}`) }}
                        </span>
                    </span>
                </DropdownMenuItem>

                <template v-if="props.vistas.length > 0 && actual">
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        class="text-cuerpo"
                        @select="marcarPredeterminada(actual)"
                    >
                        {{ $t('vistas.predeterminada') }}
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        class="text-cuerpo"
                        @select="renombrar(actual)"
                    >
                        {{ $t('vistas.renombrar') }}
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        class="text-cuerpo"
                        @select="
                            router.post(
                                duplicar(actual.id).url,
                                {},
                                { preserveScroll: true },
                            )
                        "
                    >
                        {{ $t('vistas.duplicar') }}
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        variant="destructive"
                        class="text-cuerpo"
                        @select="
                            router.delete(destroy(actual.id).url, {
                                preserveScroll: true,
                            })
                        "
                    >
                        {{ $t('vistas.borrar') }}
                    </DropdownMenuItem>
                </template>
            </DropdownMenuContent>
        </DropdownMenu>

        <!--
            «La configuración actual difiere de la guardada» dicho donde se ve:
            sin esta marca, alguien toca un filtro, se va, vuelve por la vista
            guardada y no entiende por qué no está lo que dejó.
        -->
        <span
            v-if="difiere"
            class="text-fino text-texto-suave"
            :title="$t('vistas.sin_guardar_ayuda')"
        >
            {{ $t('vistas.sin_guardar') }}
        </span>

        <Popover v-model:open="abierto">
            <PopoverTrigger as-child>
                <Button variant="ghost" size="sm">
                    {{ $t('vistas.guardar') }}
                </Button>
            </PopoverTrigger>

            <PopoverContent align="end" class="flex w-72 flex-col gap-corto">
                <p class="text-fino text-texto-suave">
                    {{ $t('vistas.guardar_ayuda') }}
                </p>

                <Label for="vista-nombre" class="text-micro text-texto-suave">
                    {{ $t('comun.nombre') }}
                </Label>
                <Input
                    id="vista-nombre"
                    v-model="nombre"
                    autocomplete="off"
                    @keydown.enter.prevent="guardar"
                />

                <Label
                    class="flex items-start gap-corto text-fino text-texto-suave"
                >
                    <Checkbox v-model="comoPredeterminada" class="mt-minimo" />
                    <span>
                        {{ $t('vistas.predeterminada') }}
                        <span class="block text-texto-tenue">
                            {{ $t('vistas.predeterminada_ayuda') }}
                        </span>
                    </span>
                </Label>

                <div class="flex justify-end gap-corto">
                    <Button
                        v-if="actual"
                        variant="outline"
                        size="sm"
                        @click="sobrescribir(actual)"
                    >
                        {{ $t('vistas.sobrescribir') }}
                    </Button>
                    <Button
                        size="sm"
                        :disabled="nombre.trim() === ''"
                        @click="guardar"
                    >
                        {{ $t('comun.guardar') }}
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    </div>
</template>
