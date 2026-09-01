import { defineConfig } from '@playwright/test';
import { ESTADO_DE_SESION } from './rutas';

/**
 * Comprobación de humo. **No es una suite end-to-end.**
 *
 * El README dice «sin pruebas de navegador». La excepción es estrecha y
 * deliberada: las fases 04 y 07 se cerraron en verde con la aplicación
 * mostrando `nav.dashboard` en pantalla, porque `npm run build` compila igual
 * de bien con las claves sin resolver y una tabla que se corta pasa todas las
 * pruebas de servidor.
 *
 * Esto comprueba dos cosas que ninguna prueba de servidor puede ver, y nada
 * más: que no queda texto con forma de clave y que el documento no desborda a
 * lo ancho. No debe crecer hasta ser una suite de flujos.
 */
export default defineConfig({
    testDir: '.',
    // Una sola autenticación para las seis comprobaciones: Fortify limita
    // los intentos y seis accesos seguidos agotan el cupo.
    globalSetup: './sesion.ts',
    // Un solo trabajador: las capturas van a rutas fijas y el navegador
    // comparte la sesión iniciada.
    workers: 1,
    fullyParallel: false,
    reporter: [['list']],
    timeout: 60_000,
    outputDir: '../../storage/humo/resultados',
    use: {
        baseURL: process.env.HUMO_URL ?? 'http://nginx',
        storageState: ESTADO_DE_SESION,
        // La aplicación corre en local sobre HTTP; no hay certificado.
        ignoreHTTPSErrors: true,
    },
});
