import { mkdir } from 'node:fs/promises';
import { dirname } from 'node:path';
import { chromium } from '@playwright/test';
import { ESTADO_DE_SESION } from './rutas';

/**
 * Inicia sesión **una vez** y guarda la cookie para todas las comprobaciones.
 *
 * Fortify limita los intentos de acceso, así que seis pruebas que se
 * autentican por su cuenta agotan el cupo y las últimas fallan por el
 * limitador, no por lo que venían a mirar.
 */
export default async function iniciarSesionUnaVez(): Promise<void> {
    const base = process.env.HUMO_URL ?? 'http://nginx';
    const navegador = await chromium.launch();
    const contexto = await navegador.newContext({ baseURL: base });
    const pagina = await contexto.newPage();

    await pagina.goto('/login');
    // Por `name`, no por etiqueta: la etiqueta es texto y este es justo el
    // proyecto donde el texto cambia de idioma.
    await pagina.locator('input[name="email"]').fill(
        process.env.HUMO_CORREO ?? 'admin@gestor.test',
    );
    await pagina.locator('input[name="password"]').fill(
        process.env.HUMO_CLAVE ?? 'password',
    );
    await pagina.locator('[data-test="login-button"]').click();
    await pagina.waitForURL(/\/dashboard/);

    await mkdir(dirname(ESTADO_DE_SESION), { recursive: true });
    await contexto.storageState({ path: ESTADO_DE_SESION });
    await navegador.close();
}
