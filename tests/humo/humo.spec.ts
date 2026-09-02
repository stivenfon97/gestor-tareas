import { mkdir } from 'node:fs/promises';
import { join } from 'node:path';
import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';
import { CAPTURAS, ESTADO_DE_SESION } from './rutas';

/**
 * Comprobación de humo. Hace exactamente cuatro cosas y ninguna más:
 *
 * 1. Recorre cinco pantallas y falla si el texto visible contiene algo con
 *    forma de clave de traducción.
 * 2. En cada una, a tres anchos, falla si el documento desborda a lo ancho.
 * 3. Comprueba dos defectos de composición que solo existen en el navegador:
 *    una fila cortada por el borde de la tabla y un control de filtro
 *    estrujado por su columna.
 * 4. Captura cada pantalla y cada ancho, en claro y en oscuro.
 *
 * El punto 3 se añadió en la fase 07d, y va aquí y no en Pest porque ninguna
 * de las dos cosas existe hasta que un navegador reparte el ancho: `Tipo` se
 * renderizaba como `Tc` porque su columna medía cuarenta píxeles, y eso no se
 * ve en el HTML que devuelve el servidor.
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
    // Las tres formas de ver lo mismo que trajo la fase 08. Entran aquí por lo
    // mismo que las otras cinco: las claves sin traducir y el desbordamiento a
    // lo ancho no los ve ninguna prueba de servidor.
    { nombre: 'tablero', ruta: (id) => `/proyectos/${id}/tablero` },
    { nombre: 'lista', ruta: (id) => `/proyectos/${id}/lista` },
    { nombre: 'agenda', ruta: (id) => `/proyectos/${id}/agenda` },
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

/**
 * Filas cortadas por el borde inferior del contenedor de la tabla.
 *
 * El desplazamiento vertical vive dentro del panel para que la cabecera se
 * quede fija, y el alto máximo caía donde caía: casi nunca en la costura entre
 * dos filas, así que la última fila visible aparecía partida por la mitad.
 * Aquí se comprueba que la tabla acaba en una fila entera.
 */
const filasCortadas = async (pagina: Page): Promise<number> =>
    pagina.evaluate(() => {
        const caja = document.querySelector<HTMLElement>(
            'table',
        )?.parentElement;

        if (caja === null || caja === undefined) {
            return 0;
        }

        const fondo = caja.getBoundingClientRect().bottom;

        // Un píxel de margen: los redondeos de disposición del navegador dejan
        // fracciones que no se ven, y fallar por 0.4px sería ruido.
        return [...caja.querySelectorAll('tbody > tr')].filter((fila) => {
            const rectangulo = fila.getBoundingClientRect();

            return (
                rectangulo.top < fondo - 1 && rectangulo.bottom > fondo + 1
            );
        }).length;
    });

/**
 * Controles de filtro más estrechos de lo que su contenido necesita.
 *
 * El `<select>` de «Tipo» se renderizaba como `Tc`. La causa era heredar el
 * ancho de una columna que solo lleva un icono, así que la comprobación no
 * mira esa columna: mira **todos** los controles de filtro, que es lo que
 * impide que el problema reaparezca en la siguiente columna estrecha.
 *
 * Hay que abrirlos uno a uno. En la tabla el control vive en una capa flotante
 * que solo existe mientras está abierta, y en el teléfono dentro de un
 * `<details>` plegado: medir sin abrir devolvería cero controles y **cero
 * controles pasan la comprobación**, que es la forma más silenciosa de dar
 * verde en vacío. Por eso se devuelve también cuántos se midieron.
 */
const FILTRO_MINIMO = 96;

type MedidaDeFiltros = { medidos: number; estrujados: string[] };

const medirFiltros = async (pagina: Page): Promise<MedidaDeFiltros> => {
    const estrujados: string[] = [];
    let medidos = 0;

    const anchoVisible = async (): Promise<Array<[string, number]>> =>
        pagina.evaluate(() =>
            [
                ...document.querySelectorAll<HTMLElement>(
                    '[data-filtro-de-columna]',
                ),
            ]
                .filter((control) => control.offsetParent !== null)
                .map((control): [string, number] => [
                    control.getAttribute('aria-label') ?? control.tagName,
                    control.getBoundingClientRect().width,
                ]),
        );

    const anotar = (medidas: Array<[string, number]>): void => {
        for (const [rotulo, ancho] of medidas) {
            medidos += 1;

            if (ancho < FILTRO_MINIMO) {
                estrujados.push(`${rotulo} (${Math.round(ancho)}px)`);
            }
        }
    };

    // En la tabla: un disparador por columna filtrable, de uno en uno.
    const disparadores = pagina.locator('[data-filtro-cabecera]');
    const cuantos = await disparadores.count();

    for (let indice = 0; indice < cuantos; indice += 1) {
        await disparadores.nth(indice).click();
        await pagina
            .locator('[data-filtro-de-columna]')
            .first()
            .waitFor({ state: 'visible' });
        anotar(await anchoVisible());
        await pagina.keyboard.press('Escape');
    }

    // En el teléfono no hay cabecera: los filtros van apilados en un
    // `<details>` que nace plegado.
    if (cuantos === 0) {
        await pagina.evaluate(() => {
            document
                .querySelectorAll<HTMLDetailsElement>('details')
                .forEach((panel) => {
                    panel.open = true;
                });
        });
        anotar(await anchoVisible());
    }

    return { medidos, estrujados };
};

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

                // Sin esto la comprobación de filtros pasaría en vacío el día
                // que un cambio dejara de renderizar los disparadores.
                let filtrosMedidos = 0;

                for (const pantalla of PANTALLAS) {
                    await pagina.goto(pantalla.ruta(proyecto));
                    // Inertia pinta en el cliente: sin esto se mide el esqueleto.
                    await pagina.waitForLoadState('networkidle');

                    /*
                     * Que la pantalla tenga texto, antes de mirar qué texto es.
                     *
                     * Las dos comprobaciones de abajo pasan **en vacío** sobre
                     * una página en blanco: no hay claves crudas y no hay
                     * desbordamiento. Y una página en blanco es justo lo que
                     * deja un bundle que no carga, que es el fallo más gordo
                     * que puede haber. Sin esto, la comprobación de humo daría
                     * verde sobre una aplicación que no arranca.
                     */
                    const visible = await pagina.evaluate(
                        () => document.body.innerText.trim().length,
                    );

                    if (visible < 50) {
                        fallos.push(
                            `${pantalla.nombre}: la pantalla llegó vacía (${visible} caracteres). ¿Cargó el bundle?`,
                        );
                    }

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

                    const cortadas = await filasCortadas(pagina);

                    if (cortadas > 0) {
                        fallos.push(
                            `${pantalla.nombre} @${medida.nombre}px: ${cortadas} fila(s) cortadas por el borde de la tabla`,
                        );
                    }

                    /*
                     * La captura va **antes** de tocar los filtros: abrirlos
                     * deja capas flotantes desvaneciéndose sobre la tabla, y
                     * la captura es para mirar la pantalla en reposo.
                     */
                    await pagina.screenshot({
                        path: join(
                            CAPTURAS,
                            `${pantalla.nombre}-${medida.nombre}-${tema}.png`,
                        ),
                        fullPage: true,
                    });

                    const filtros = await medirFiltros(pagina);

                    filtrosMedidos += filtros.medidos;

                    if (filtros.estrujados.length > 0) {
                        fallos.push(
                            `${pantalla.nombre} @${medida.nombre}px: controles de filtro por debajo de ${FILTRO_MINIMO}px → ${filtros.estrujados.join(', ')}`,
                        );
                    }

                }

                if (filtrosMedidos === 0) {
                    fallos.push(
                        `@${medida.nombre}px: no se midió ni un control de filtro. ¿Se dejaron de pintar los disparadores?`,
                    );
                }

                await contexto.close();

                // Se acumulan y se reportan juntos: parar en el primero obliga
                // a dar cinco vueltas para ver cinco problemas.
                expect(fallos, fallos.join('\n')).toEqual([]);
            });
        }
    });
}
