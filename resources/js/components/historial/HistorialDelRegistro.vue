<script setup lang="ts">
/**
 * Botón de la cabecera de una ficha que abre, en un panel lateral, el
 * historial de cambios de ese registro.
 *
 * El historial se pide **al abrir el panel**, no con la página: ninguna ficha
 * paga la consulta si nadie lo mira. Pagina hacia atrás con «cargar más» (un
 * cursor del servidor), y un fallo de red deja reintentar sin cerrar el panel.
 *
 * Que el botón se pinte lo decide la página con la prop `puedeVerHistorial`
 * del controlador; la cerradura real es `HistorialCambioPolicy@verDe`.
 */
import { History } from '@lucide/vue';
import { ref, watch } from 'vue';
import BotonDeAccion from '@/components/BotonDeAccion.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { registro } from '@/routes/historial';
import EntradaDeHistorial from './EntradaDeHistorial.vue';
import type { EntradaDeHistorial as Entrada, TipoDeRegistro } from './tipos';

const props = defineProps<{
    tipo: TipoDeRegistro;
    id: number;
}>();

const abierto = ref(false);
const entradas = ref<Entrada[]>([]);
const siguiente = ref<string | null>(null);
const cargando = ref(false);
const fallo = ref(false);
const cargadoAlgunaVez = ref(false);

const cargar = async (cursor: string | null = null): Promise<void> => {
    cargando.value = true;
    fallo.value = false;

    try {
        const url = registro(
            { tipo: props.tipo, id: props.id },
            cursor ? { query: { cursor } } : undefined,
        ).url;
        const respuesta = await fetch(url, {
            headers: { Accept: 'application/json' },
        });

        if (!respuesta.ok) {
            throw new Error(String(respuesta.status));
        }

        const datos: { entradas: Entrada[]; siguiente: string | null } =
            await respuesta.json();

        entradas.value = cursor
            ? [...entradas.value, ...datos.entradas]
            : datos.entradas;
        siguiente.value = datos.siguiente;
        cargadoAlgunaVez.value = true;
    } catch {
        fallo.value = true;
    } finally {
        cargando.value = false;
    }
};

// Se vuelve a pedir cada vez que se abre: entre una apertura y otra la
// ficha pudo guardarse y el panel enseñaría un historial viejo.
watch(abierto, (estaAbierto) => {
    if (estaAbierto) {
        void cargar();
    }
});
</script>

<template>
    <!-- Una sola raíz; `contents` deja que el botón se coloque en el flex de
         la cabecera como si no hubiera envoltorio. -->
    <div class="contents">
        <BotonDeAccion
            :icono="History"
            :etiqueta="$t('historial.ver')"
            @click="abierto = true"
        />

        <Sheet v-model:open="abierto">
            <SheetContent
                side="right"
                class="w-full gap-0 bg-panel sm:max-w-xl"
            >
                <SheetHeader class="border-b border-borde">
                    <SheetTitle class="flex items-center gap-corto">
                        <History class="size-4 text-marca" aria-hidden="true" />
                        {{ $t('historial.titulo') }}
                    </SheetTitle>
                    <SheetDescription>
                        {{ $t('historial.panel_descripcion') }}
                    </SheetDescription>
                </SheetHeader>

                <div
                    class="flex min-h-0 flex-1 flex-col gap-celda overflow-y-auto p-bloque"
                    :aria-busy="cargando"
                >
                    <template v-if="!cargadoAlgunaVez && cargando">
                        <span class="sr-only">{{
                            $t('historial.cargando')
                        }}</span>
                        <Skeleton
                            v-for="n in 3"
                            :key="n"
                            class="h-28 w-full rounded-lg"
                        />
                    </template>

                    <div
                        v-else-if="cargadoAlgunaVez && entradas.length === 0"
                        class="flex flex-col items-center gap-minimo py-vacio text-center"
                    >
                        <History
                            class="size-6 text-texto-tenue"
                            aria-hidden="true"
                        />
                        <p class="text-medio font-medium text-texto">
                            {{ $t('historial.sin_cambios') }}
                        </p>
                        <p class="max-w-sm text-cuerpo text-texto-suave">
                            {{ $t('historial.sin_cambios_ayuda') }}
                        </p>
                    </div>

                    <EntradaDeHistorial
                        v-for="entrada in entradas"
                        :key="entrada.id"
                        :entrada="entrada"
                    />

                    <div
                        v-if="fallo"
                        role="alert"
                        class="flex flex-col items-start gap-corto rounded-lg border border-tono-peligro-borde bg-tono-peligro-fondo p-bloque text-cuerpo text-tono-peligro-texto"
                    >
                        {{ $t('historial.error') }}
                        <Button
                            variant="outline"
                            size="sm"
                            @click="cargar(entradas.length ? siguiente : null)"
                        >
                            {{ $t('historial.reintentar') }}
                        </Button>
                    </div>

                    <Button
                        v-if="siguiente && !fallo"
                        variant="outline"
                        size="sm"
                        class="self-center"
                        :disabled="cargando"
                        @click="cargar(siguiente)"
                    >
                        <Spinner v-if="cargando" />
                        {{ $t('historial.cargar_mas') }}
                    </Button>
                </div>
            </SheetContent>
        </Sheet>
    </div>
</template>
