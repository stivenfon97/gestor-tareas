import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { expect, test } from '@playwright/test';

/**
 * Valida sintácticamente todos los diagramas Mermaid del repositorio.
 *
 * La fase 02 los validaba "a ojo" porque el contenedor `node` es Alpine y
 * Chrome no arranca ahí. Este contenedor (`humo`, imagen oficial de
 * Playwright) sí tiene un navegador de verdad, así que reutilizarlo para esto
 * es gratis: ya corre en `make humo`.
 *
 * Recorre `docs/**\/*.md`, extrae cada bloque ` ```mermaid ` y le pide al
 * propio parser de Mermaid que lo analice dentro de una página real. No
 * comprueba que el diagrama se vea bien, solo que el motor lo entiende — es
 * la misma frontera que el resto de `tests/humo`: lo visual no es esto.
 */

const RAIZ_DOCS = join(process.cwd(), '..', '..', 'docs');

type Diagrama = { archivo: string; indice: number; codigo: string };

const archivosMarkdown = (directorio: string): string[] =>
    readdirSync(directorio).flatMap((entrada) => {
        const ruta = join(directorio, entrada);

        if (statSync(ruta).isDirectory()) {
            return archivosMarkdown(ruta);
        }

        return ruta.endsWith('.md') ? [ruta] : [];
    });

const extraerDiagramas = (rutaArchivo: string): Diagrama[] => {
    const contenido = readFileSync(rutaArchivo, 'utf-8');
    const patron = /```mermaid\n([\s\S]*?)```/g;
    const relativo = rutaArchivo.slice(RAIZ_DOCS.length + 1);

    return [...contenido.matchAll(patron)].map((coincidencia, indice) => ({
        archivo: relativo,
        indice: indice + 1,
        codigo: coincidencia[1],
    }));
};

const diagramas = archivosMarkdown(RAIZ_DOCS).flatMap(extraerDiagramas);

// El bundle de navegador, no el paquete ESM: `mermaid.parse()` necesita
// `document`, y aquí se ejecuta dentro de una página de Playwright. El
// directorio de trabajo del contenedor `humo` es `tests/humo`, igual que en
// `rutas.ts`.
const RUTA_MERMAID = join(
    process.cwd(),
    'node_modules',
    'mermaid',
    'dist',
    'mermaid.min.js',
);

test.describe('diagramas Mermaid', () => {
    // Sin diagramas que validar, la comprobación pasaría en vacío y nadie lo
    // notaría: es el mismo defecto que la comprobación de filtros de humo.
    test('hay al menos un diagrama que validar', () => {
        expect(diagramas.length).toBeGreaterThan(0);
    });

    for (const diagrama of diagramas) {
        test(`${diagrama.archivo} #${diagrama.indice} es sintácticamente válido`, async ({
            page,
        }) => {
            await page.addScriptTag({ path: RUTA_MERMAID });
            await page.evaluate(() => {
                window.mermaid.initialize({ startOnLoad: false });
            });

            const error = await page.evaluate(async (codigo) => {
                try {
                    await window.mermaid.parse(codigo);

                    return null;
                } catch (fallo) {
                    return fallo instanceof Error ? fallo.message : String(fallo);
                }
            }, diagrama.codigo);

            expect(error, `${diagrama.archivo} #${diagrama.indice}: ${error}`).toBeNull();
        });
    }
});

declare global {
    interface Window {
        mermaid: {
            initialize: (config: { startOnLoad: boolean }) => void;
            parse: (texto: string) => Promise<unknown>;
        };
    }
}
