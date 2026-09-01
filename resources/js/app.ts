import { createInertiaApp } from '@inertiajs/vue3';
import { I18n, i18nVue } from 'laravel-vue-i18n';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { instalarModoEstricto } from '@/lib/i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
    // Ningún texto visible se escribe en el componente: todo pasa por $t().
    withApp: (app, { page }) => {
        app.use(i18nVue, {
            lang: (page.props.locale as string) ?? 'es',
            fallbackLang: 'es',
            // Una clave que falte en `en` cae al texto en español en lugar de
            // pintar su ruta. Que exista en los dos idiomas lo exige
            // `CoberturaDeClavesTest`; esto es la red por debajo.
            fallbackMissingTranslations: true,
            /*
             * `laravel-vue-i18n` llama a esto **dos veces** por idioma: una con
             * `es` (traducciones de cadena JSON, que este proyecto no usa) y
             * otra con `php_es` (las de `lang/es/*.php`, que sí). Que
             * `../../lang/es.json` no exista es correcto y esperado.
             *
             * Lo que no es correcto es que `lang/php_es.json` falte: el
             * paquete se traga el fallo del import y devuelve un diccionario
             * vacío, con lo que **toda** la interfaz pinta su clave en crudo.
             * De eso avisa `onLoad`, y en desarrollo lo marca `@/lib/i18n`.
             */
            resolve: async (lang: string) => {
                const idiomas = import.meta.glob('../../lang/*.json');
                const archivo = idiomas[`../../lang/${lang}.json`];

                return archivo ? await archivo() : { default: {} };
            },
            onLoad: (lang: string) => {
                const cargado = I18n.loaded.find(
                    (idioma) => idioma.lang === lang,
                );

                if (Object.keys(cargado?.messages ?? {}).length === 0) {
                    console.error(
                        `[i18n] El diccionario de «${lang}» llegó vacío: falta lang/php_${lang}.json. Lo borra «npm run build»; se regenera solo, pero un dev server que ya estaba corriendo se queda sin él hasta que se reinicie.`,
                    );
                }
            },
        });

        instalarModoEstricto(app);
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
