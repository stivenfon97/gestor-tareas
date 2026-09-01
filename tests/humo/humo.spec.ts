import { mkdir } from 'node:fs/promises';
import { join } from 'node:path';
import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';
import { CAPTURAS, ESTADO_DE_SESION } from './rutas';

/**
 * Comprobación de humo. Hace exactamente tres cosas y ninguna más:
 *
 * 1. Recorre cinco pantallas y falla si el texto visible contiene algo con
 *    forma de clave de traducción.
 * 2. En cada una, a tres anchos, falla si el documento desborda a lo ancho.
 * 3. Captura cada pantalla y cada ancho, en claro y en oscuro.
 *
 * No es una suite end-to-end y no debe crecer hasta serlo. Lo que valida el
 * comportamiento son las pruebas de Pest.
 */

/** Los tres anchos: teléfono, tableta y escritorio. */
const ANCHOS = [
    { nombre: '360', ancho: 360, alto: 780 },
    { nombre: '768', ancho: 768, alto: 1024 },
    { nombre: '1440', ancho: 1440, alto: 900 },
];

type Pantalla = { nombre: string; ruta: (proyecto: number) => string };

const PANTALLAS: Pantalla[] = [
    { nombre: 'dashboard', ruta: () => '/dashboard' },
    { nombre: 'clientes', ruta: () => '/clientes' },
    { nombre: 'proyectos', ruta: () => '/proyectos' },
    { nombre: 'proyecto-detalle', ruta: (id) => `/proyectos/${id}` },
    { nombre: 'tareas', ruta: () => '/tareas' },
];

/*
 * Una cadena con forma de clave: sin espacios y con dos o más segmentos
 * separados por puntos. Es el mismo criterio que `esClaveDeTraduccion` en
 * `resources/js/lib/i18n.ts`.
 */
const FORMA_DE_CLAVE = /^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/i;

/*
 * Un dominio tiene la misma forma que una clave. Se descartan por el último
 * segmento en lugar de por una lista de dominios concretos: la lista se
 * quedaría corta en cuanto alguien añadiera un enlace.
 */
const DOMINIOS = new Set([
    'com',
    'org',
    'net',
    'io',
    'co',
    'dev',
    'test',
    'local',
    'localhost',
    'php',
    'json',
    'js',
    'ts',
    'vue',
    'css',
    'xlsx',
    'sql',
    'md',
]);

/**
 * Texto visible con forma de clave.
 *
 * El delimitador del modo estricto (`⟦clave⟧`) se retira antes de comparar:
 * en desarrollo la clave sin traducir llega marcada, y marcada tiene que
 * seguir fallando.
 */
const clavesEnPantalla = async (pagina: Page): Promise<string[]> => {
    const texto = await pagina.evaluate(() => document.body.innerText);

    const sospechosas = texto
        .replace(/[⟦⟧]/g, ' ')
        .split(/\s+/)
        .map((palabra) => palabra.replace(/^[«»"'(]+|[«»"'),;:!?]+$/g, ''))
        .filter((palabra) => palabra.length > 0)
        // Un correo o una URL no son una clave aunque lleven puntos.
        .filter((palabra) => !/[@/:\\]/.test(palabra))
        .filter((palabra) => FORMA_DE_CLAVE.test(palabra))
        .filter((palabra) => {
            const ultimo = palabra.split('.').pop() ?? '';

            return !DOMINIOS.has(ultimo.toLowerCase());
        });

    return [...new Set(sospechosas)];
};

/**
 * Desbordamiento horizontal del documento.
 *
 * Se mide en la raíz, no en un contenedor: una tabla que se desplaza dentro de
 * su propio panel es correcta; lo que no puede pasar es que la página entera
 * se vaya a la derecha.
 */
const desbordamiento = async (pagina: Page): Promise<number> =>
    pagina.evaluate(() => {
        const raiz = document.documentElement;

        return raiz.scrollWidth - raiz.clientWidth;
    });

/** El primer proyecto del listado. Sin datos sembrados no hay nada que mirar. */
const primerProyecto = async (pagina: Page): Promise<number> => {
    await pagina.goto('/proyectos');
    await pagina.waitForLoadState('networkidle');

    // Con id numérico: `/proyectos/crear` también empieza por `/proyectos/`.
    const id = await pagina.evaluate(() =>
        [...document.querySelectorAll('a[href]')]
            .map((enlace) => enlace.getAttribute('href') ?? '')
            .map((href) => href.match(/\/proyectos\/(\d+)$/)?.[1])
            .find((encontrado) => encontrado !== undefined),
    );

    expect(id, 'no hay ningún proyecto sembrado').toBeDefined();

    return Number(id);
};

for (const tema of ['light', 'dark'] as const) {
    test.describe(`tema ${tema}`, () => {
        test.use({ colorScheme: tema });

        for (const medida of ANCHOS) {
            test(`${medida.nombre}px`, async ({ browser }) => {
                await mkdir(CAPTURAS, { recursive: true });

                const contexto = await browser.newContext({
                    viewport: { width: medida.ancho, height: medida.alto },
                    colorScheme: tema,
                    // La sesión la abrió `globalSetup`, una sola vez.
                    storageState: ESTADO_DE_SESION,
                });
                const pagina = await contexto.newPage();

                const proyecto = await primerProyecto(pagina);
                const fallos: string[] = [];

                for (const pantalla of PANTALLAS) {
                    await pagina.goto(pantalla.ruta(proyecto));
                    // Inertia pinta en el cliente: sin esto se mide el esqueleto.
                    await pagina.waitForLoadState('networkidle');

                    const claves = await clavesEnPantalla(pagina);

                    if (claves.length > 0) {
                        fallos.push(
                            `${pantalla.nombre}: claves sin traducir → ${claves.join(', ')}`,
                        );
                    }

                    const sobra = await desbordamiento(pagina);

                    if (sobra > 0) {
                        fallos.push(
                            `${pantalla.nombre} @${medida.nombre}px: el documento desborda ${sobra}px a lo ancho`,
                        );
                    }

                    await pagina.screenshot({
                        path: join(
                            CAPTURAS,
                            `${pantalla.nombre}-${medida.nombre}-${tema}.png`,
                        ),
                        fullPage: true,
                    });
                }

                await contexto.close();

                // Se acumulan y se reportan juntos: parar en el primero obliga
                // a dar cinco vueltas para ver cinco problemas.
                expect(fallos, fallos.join('\n')).toEqual([]);
            });
        }
    });
}
