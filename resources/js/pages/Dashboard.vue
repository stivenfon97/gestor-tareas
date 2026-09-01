<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CabeceraDePagina from '@/components/CabeceraDePagina.vue';
import { Button } from '@/components/ui/button';
import { index as clientesIndex } from '@/routes/clientes';
import { index as proyectosIndex } from '@/routes/proyectos';
import { index as tareasIndex } from '@/routes/tareas';

/**
 * Tablero.
 *
 * Los tres recuadros con rayas diagonales del starter kit parecían un error de
 * carga: no decían que faltara nada, simulaban contenido. Hasta que la fase 12
 * traiga los datos de verdad, cada bloque dice **qué va a haber ahí** y ofrece
 * el camino que hoy sí existe. Nada de cifras de mentira: un dato inventado se
 * olvida y acaba enseñándose a un cliente.
 *
 * **Sin miga de pan.** Decía «Tablero» en la cabecera de la aplicación y el
 * `<h1>` de justo debajo decía «Tablero»: el mismo nombre dos veces en cuarenta
 * píxeles de altura. La miga sirve para volver al nivel de arriba, y aquí no
 * hay arriba.
 */

const bloques = [
    {
        clave: 'tareas',
        enlace: tareasIndex(),
    },
    {
        clave: 'periodos',
        enlace: proyectosIndex(),
    },
    {
        clave: 'bitacora',
        enlace: clientesIndex(),
    },
];
</script>

<template>
    <Head :title="$t('nav.dashboard')" />

    <div class="flex flex-col gap-seccion">
        <CabeceraDePagina
            :titulo="$t('dashboard.titulo')"
            :descripcion="$t('dashboard.descripcion')"
        />

        <div class="grid gap-bloque md:grid-cols-3">
            <section
                v-for="bloque in bloques"
                :key="bloque.clave"
                class="flex flex-col gap-corto rounded-md border border-dashed border-borde-fuerte bg-panel px-celda py-bloque"
            >
                <h2 class="text-medio font-medium text-texto">
                    {{ $t(`dashboard.bloques.${bloque.clave}.titulo`) }}
                </h2>
                <p class="flex-1 text-cuerpo text-texto-suave">
                    {{ $t(`dashboard.bloques.${bloque.clave}.cuerpo`) }}
                </p>
                <p class="text-fino text-texto-tenue">
                    {{ $t('dashboard.pendiente') }}
                </p>
                <Button as-child variant="outline" size="sm" class="self-start">
                    <Link :href="bloque.enlace">
                        {{ $t(`dashboard.bloques.${bloque.clave}.accion`) }}
                    </Link>
                </Button>
            </section>
        </div>
    </div>
</template>
