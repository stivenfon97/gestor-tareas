import { createInertiaApp } from '@inertiajs/vue3';
import { I18n, i18nVue } from 'laravel-vue-i18n';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import ProyectoLayout from '@/layouts/proyecto/Layout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { instalarModoEstricto } from '@/lib/i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/** Idioma base y de reserva: toda clave existe al menos aquí. */
const IDIOMA_BASE = 'es';

/*
 * Diccionarios dentro del bundle (`eager`), no en un chunk aparte.
 *
 * Con la carga perezosa el diccionario llegaba después del primer render, y
 * esa primera pasada pintaba claves crudas (ver la carga síncrona en
 * `withApp`). Son dos JSON de ~55 KB sin comprimir: el del idioma activo se
 * necesita sí o sí antes de pintar nada, y el otro es el precio de no
 * esperar a la red al cambiar de idioma.
 *
 * El plugin de Vite genera dos por idioma posible: `lang/php_es.json` (las
 * de `lang/es/*.php`, que son las que usa el proyecto) y `lang/es.json`
 * (traducciones de cadena, que este proyecto no tiene). Que el segundo no
 * exista es correcto. Que falte el primero no: la interfaz entera pintaría
 * su clave en crudo, y de eso avisa `onLoad`.
 */
const diccionarios = import.meta.glob<Record<string, string>>(
    '../../lang/*.json',
    { eager: true, import: 'default' },
);

/** Mismo orden de mezcla que el paquete: las de cadena pisan a las de PHP. */
const mensajesDe = (lang: string): Record<string, string> => ({
    ...diccionarios[`../../lang/php_${lang}.json`],
    ...diccionarios[`../../lang/${lang}.json`],
});

/**
 * Secciones de un proyecto: comparten cabecera y pestañas en un layout
 * persistente (`layouts/proyecto/Layout.vue`), así que cambiar de pestaña no
 * vuelve a montar la cabecera. `tareas/Index` es también el listado global;
 * sin `cabeceraDeProyecto` en las props el layout no pinta nada propio.
 */
const PAGINAS_DE_PROYECTO = [
    'proyectos/Show',
    'proyectos/Modulos',
    'proyectos/Miembros',
    'tareas/Index',
    'tareas/Tablero',
    'tareas/Lista',
    'tareas/Agenda',
    'periodos/Index',
    'pagos/Index',
    'reuniones/Listado',
    'reportes/Index',
];

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            case PAGINAS_DE_PROYECTO.includes(name):
                return [AppLayout, ProyectoLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
    // Ningún texto visible se escribe en el componente: todo pasa por $t().
    withApp: (app, { page }) => {
        const idioma = (page.props.locale as string) ?? IDIOMA_BASE;

        app.use(i18nVue, {
            lang: idioma,
            fallbackLang: IDIOMA_BASE,
            // Una clave que falte en `en` cae al texto en español en lugar de
            // pintar su ruta. Que exista en los dos idiomas lo exige
            // `CoberturaDeClavesTest`; esto es la red por debajo.
            fallbackMissingTranslations: true,
            // Sigue haciendo falta: el paquete carga por aquí en su
            // constructor y en `loadLanguageAsync()` al cambiar de idioma.
            // Ya no descarga nada, solo envuelve lo que está en memoria.
            resolve: async (lang: string) => ({ default: mensajesDe(lang) }),
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

        /*
         * Carga síncrona, **antes** del primer render.
         *
         * En el navegador el paquete carga siempre por la vía asíncrona
         * —`resolveLangAsync()` es `async` aunque `resolve` devuelva el dato
         * ya resuelto—, y `createInertiaApp` monta la aplicación justo después
         * de `withApp`, en el mismo tick. El primer render salía entonces con
         * el diccionario vacío: cada `$t` devolvía su clave, se pintaba
         * `⟦auth.acceso.titulo⟧` un instante y el modo estricto de
         * `@/lib/i18n` dejaba en consola un «Clave sin traducir» por clave de
         * la pantalla (11 en `/login`). Al llegar el diccionario se repintaba
         * bien, pero los avisos ya estaban dados: once falsos positivos por
         * pantalla enseñan a no leer la consola, y el aviso de una clave que
         * falta de verdad se pierde entre ellos.
         *
         * Se rellena lo mismo que dejaría la vía asíncrona —el idioma de
         * reserva y el activo, registrados en `I18n.loaded`— para que su
         * llegada posterior sea un no-op y `loadLanguageAsync()` encuentre el
         * idioma ya cargado.
         */
        const i18n = I18n.getSharedInstance();
        const activo = mensajesDe(idioma);

        i18n.applyFallbackLanguage(IDIOMA_BASE, mensajesDe(IDIOMA_BASE));
        i18n.addLoadedLang({ lang: idioma, messages: activo });
        i18n.setLanguage({ lang: idioma, messages: activo });

        instalarModoEstricto(app);
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
