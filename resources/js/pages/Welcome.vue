<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';

/**
 * Portada pública.
 *
 * Sustituye a la del starter kit, que eran 436 líneas promocionando Laracasts y
 * la documentación de Laravel. Aquella infringía tres reglas del proyecto a la
 * vez: texto incrustado en inglés, colores literales (`bg-[#FDFDFC]`) y una
 * tipografía traída de `rsms.me`, cuando la aplicación se autoaloja las suyas y
 * no debe depender de una petición externa para pintar texto.
 *
 * Esto es lo único que la portada tiene que hacer: decir qué es esto y llevar
 * adentro. No hay registro público.
 */
</script>

<template>
    <Head :title="$t('bienvenida.titulo')" />

    <div
        class="flex min-h-svh flex-col items-center justify-center gap-seccion bg-fondo px-bloque py-pagina"
    >
        <div class="flex flex-col items-center gap-bloque text-center">
            <AppLogoIcon class="size-10 fill-current text-marca" />

            <div class="flex max-w-md flex-col gap-corto">
                <h1 class="text-cima font-semibold text-texto">
                    {{ $t('bienvenida.titulo') }}
                </h1>
                <p class="text-cuerpo text-texto-suave">
                    {{ $t('bienvenida.descripcion') }}
                </p>
            </div>

            <Button as-child size="sm">
                <Link v-if="$page.props.auth.user" :href="dashboard()">
                    {{ $t('bienvenida.ir_al_tablero') }}
                </Link>
                <Link v-else :href="login()">
                    {{ $t('bienvenida.entrar') }}
                </Link>
            </Button>
        </div>
    </div>
</template>
