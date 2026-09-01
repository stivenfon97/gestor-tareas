<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import draggable from 'vuedraggable';
import Heading from '@/components/Heading.vue';
import ModuloNodo from '@/components/modulos/ModuloNodo.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy, update } from '@/routes/modulos';
import { show } from '@/routes/proyectos';
import { orden, store } from '@/routes/proyectos/modulos';
import type { NivelDeModulos, NodoModulo } from '@/types/modulos';

const props = defineProps<{
    proyecto: { id: number; nombre: string; slug: string };
    arbol: NodoModulo[];
    puede: { gestionar: boolean };
}>();

const clonar = (nodos: NodoModulo[]): NodoModulo[] =>
    nodos.map((nodo) => ({ ...nodo, hijos: clonar(nodo.hijos) }));

/*
 * Dos copias: `arbol` es lo que el usuario está manipulando y `servidor` es la
 * última verdad confirmada. Si la petición se rechaza —un ciclo, por ejemplo—,
 * la primera vuelve a la segunda y la pantalla no se queda mintiendo.
 */
const arbol = ref<NodoModulo[]>(clonar(props.arbol));
const servidor = ref<NodoModulo[]>(clonar(props.arbol));

watch(
    () => props.arbol,
    (nuevo) => {
        arbol.value = clonar(nuevo);
        servidor.value = clonar(nuevo);
    },
);

const colapsados = ref(new Set<number>());

const alternar = (id: number): void => {
    const siguiente = new Set(colapsados.value);

    if (siguiente.has(id)) {
        siguiente.delete(id);
    } else {
        siguiente.add(id);
    }

    colapsados.value = siguiente;
};

/** Orden completo de cada nivel, indexado por el id del padre (`0` = raíz). */
const nivelesDe = (
    nodos: NodoModulo[],
    padre: number | null = null,
): Map<number, number[]> => {
    const niveles = new Map<number, number[]>();
    niveles.set(
        padre ?? 0,
        nodos.map((nodo) => nodo.id),
    );

    for (const nodo of nodos) {
        for (const [clave, valor] of nivelesDe(nodo.hijos, nodo.id)) {
            niveles.set(clave, valor);
        }
    }

    return niveles;
};

/**
 * Persiste el arrastre en **una sola petición**.
 *
 * Manda los niveles afectados enteros —el de origen y el de destino, con el
 * orden completo de sus hijos—, no un movimiento por nodo: diez peticiones
 * seguidas dejan estados intermedios inconsistentes si una falla a la mitad.
 */
const reordenar = (): void => {
    const actuales = nivelesDe(arbol.value);
    const previos = nivelesDe(servidor.value);

    const niveles: NivelDeModulos[] = [];

    for (const [clave, ids] of actuales) {
        const antes = previos.get(clave) ?? [];

        if (
            antes.length !== ids.length ||
            antes.some((id, indice) => id !== ids[indice])
        ) {
            niveles.push({
                parent_id: clave === 0 ? null : clave,
                modulos: ids,
            });
        }
    }

    // Un nivel que se vació entero también cambió y hay que enviarlo.
    for (const [clave, antes] of previos) {
        if (!actuales.has(clave) && antes.length > 0) {
            niveles.push({
                parent_id: clave === 0 ? null : clave,
                modulos: [],
            });
        }
    }

    if (niveles.length === 0) {
        return;
    }

    router.put(
        orden(props.proyecto.id).url,
        { niveles },
        {
            preserveScroll: true,
            // El servidor rechaza el ciclo con un 422 y la transacción no deja
            // nada a medias: aquí solo hay que deshacer lo que se ve.
            onError: () => {
                arbol.value = clonar(servidor.value);
            },
        },
    );
};

const nombreRaiz = ref('');

const crearRaiz = (): void => {
    const nombre = nombreRaiz.value.trim();

    if (nombre === '') {
        return;
    }

    router.post(
        store(props.proyecto.id).url,
        { nombre, parent_id: null },
        { preserveScroll: true },
    );
    nombreRaiz.value = '';
};

const crearHijo = (padre: number, nombre: string): void => {
    router.post(
        store(props.proyecto.id).url,
        { nombre, parent_id: padre },
        { preserveScroll: true },
    );
};

const renombrar = (id: number, nombre: string): void => {
    router.put(update(id).url, { nombre }, { preserveScroll: true });
};

const porEliminar = ref<NodoModulo | null>(null);

const confirmarEliminar = (): void => {
    if (porEliminar.value === null) {
        return;
    }

    router.delete(destroy(porEliminar.value.id).url, { preserveScroll: true });
    porEliminar.value = null;
};
</script>

<template>
    <div>
        <Head :title="$t('modulos.titulo')" />

        <div class="flex flex-col gap-6">
            <div class="flex items-start justify-between gap-4">
                <Heading
                    variant="small"
                    :title="$t('modulos.titulo')"
                    :description="$t('modulos.descripcion')"
                />
                <Button as-child variant="ghost" size="sm">
                    <Link :href="show(props.proyecto.id)">{{
                        $t('comun.volver')
                    }}</Link>
                </Button>
            </div>

            <div
                v-if="props.puede.gestionar"
                class="flex flex-wrap items-end gap-3"
            >
                <div class="grid gap-2">
                    <Label for="modulo_raiz">{{
                        $t('modulos.crear_raiz')
                    }}</Label>
                    <Input
                        id="modulo_raiz"
                        v-model="nombreRaiz"
                        class="w-64"
                        :placeholder="$t('modulos.nombre_nuevo')"
                        @keydown.enter.prevent="crearRaiz"
                    />
                </div>
                <Button size="sm" @click="crearRaiz">{{
                    $t('comun.agregar')
                }}</Button>
            </div>

            <div class="rounded-lg border border-border p-2">
                <draggable
                    v-model="arbol"
                    :group="{ name: 'modulos' }"
                    item-key="id"
                    handle=".tirador"
                    :disabled="!props.puede.gestionar"
                    tag="ul"
                    class="flex flex-col"
                    @change="reordenar"
                >
                    <template #item="{ element }">
                        <!-- La clave es el id, nunca el índice. -->
                        <ModuloNodo
                            :key="element.id"
                            v-model:hijos="element.hijos"
                            :nodo="element"
                            :puede-gestionar="props.puede.gestionar"
                            :colapsados="colapsados"
                            :profundidad="0"
                            @alternar="alternar"
                            @renombrar="renombrar"
                            @crear-hijo="crearHijo"
                            @eliminar="porEliminar = $event"
                            @reordenar="reordenar"
                        />
                    </template>
                </draggable>

                <p
                    v-if="arbol.length === 0"
                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    {{ $t('modulos.sin_modulos') }}
                </p>
            </div>
        </div>

        <!-- El impacto exacto antes de confirmar, no un «¿seguro?» genérico. -->
        <Dialog
            :open="porEliminar !== null"
            @update:open="(abierto) => (abierto ? null : (porEliminar = null))"
        >
            <DialogContent v-if="porEliminar">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            $t('modulos.eliminar.titulo', {
                                nombre: porEliminar.nombre,
                            })
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        <span
                            v-if="
                                porEliminar.submodulos_totales === 0 &&
                                porEliminar.tareas_totales === 0
                            "
                        >
                            {{ $t('modulos.eliminar.sin_impacto') }}
                        </span>
                        <template v-else>
                            <span
                                v-if="porEliminar.submodulos_totales > 0"
                                class="block"
                            >
                                {{
                                    $t('modulos.eliminar.submodulos', {
                                        total: String(
                                            porEliminar.submodulos_totales,
                                        ),
                                    })
                                }}
                            </span>
                            <span
                                v-if="porEliminar.tareas_totales > 0"
                                class="block"
                            >
                                {{
                                    $t('modulos.eliminar.tareas', {
                                        total: String(
                                            porEliminar.tareas_totales,
                                        ),
                                    })
                                }}
                            </span>
                        </template>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="ghost" @click="porEliminar = null">{{
                        $t('comun.cancelar')
                    }}</Button>
                    <Button variant="destructive" @click="confirmarEliminar">
                        {{ $t('modulos.eliminar.confirmar') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
