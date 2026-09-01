import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import i18n from 'laravel-vue-i18n/vite';
import {
    generateFiles,
    prepareExtendedParsedLangFiles,
} from 'laravel-vue-i18n/loader';
import { defineConfig } from 'vite';
import type { Plugin } from 'vite';

const RUTA_LANG = 'lang/';
const RUTA_LANG_FRAMEWORK =
    'vendor/laravel/framework/src/Illuminate/Translation/lang/';

/**
 * Vuelve a escribir `lang/php_{es,en}.json` cuando el build termina.
 *
 * El plugin `i18n()` los genera en `buildStart` y **los borra en `buildEnd`**.
 * Con eso, `npm run build` deja sin diccionario a cualquier dev server que ya
 * estuviera corriendo: el `import()` de `lang/php_es.json` devuelve 404, el
 * paquete se traga el fallo y **toda la interfaz pinta su clave en crudo**.
 *
 * Es exactamente lo que pasó en las fases 04 y 07: la verificación de cada
 * fase es `docker compose exec node npm run build`, y se ejecuta con
 * `docker compose up` levantado. El build salía verde y dejaba la aplicación
 * sin un solo texto.
 *
 * `closeBundle` corre después de `buildEnd`, así que los deja donde estaban.
 */
const regenerarTraducciones = (): Plugin => ({
    name: 'regenerar-traducciones-php',
    // Solo en el build: en desarrollo los mantiene el propio plugin.
    apply: 'build',
    closeBundle() {
        generateFiles(
            RUTA_LANG,
            prepareExtendedParsedLangFiles([RUTA_LANG_FRAMEWORK, RUTA_LANG]),
        );
    },
});

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            // Sin `fonts:`. Las tipografías se autoalojan con @fontsource desde
            // resources/css/app.css: la aplicación corre en local y no debe
            // depender de Bunny ni de Google para pintar texto.
        }),
        // Compila lang/{es,en}/*.php a lang/php_{es,en}.json para el cliente.
        i18n(),
        // …y los repone, porque i18n() los borra al cerrar el build.
        regenerarTraducciones(),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
    server: {
        // Vite corre dentro del contenedor "node": debe escuchar en todas las
        // interfaces para ser alcanzable desde el host en localhost:5173.
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
        },
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
});
