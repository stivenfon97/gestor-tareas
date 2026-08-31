import { createInertiaApp } from '@inertiajs/vue3';
import { i18nVue } from 'laravel-vue-i18n';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

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
            resolve: async (lang: string) => {
                const idiomas = import.meta.glob('../../lang/*.json');
                const archivo = idiomas[`../../lang/${lang}.json`];

                return archivo ? await archivo() : { default: {} };
            },
        });
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
