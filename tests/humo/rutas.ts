import { join } from 'node:path';

/** El directorio de trabajo del contenedor es `tests/humo`. */
const RAIZ = join(process.cwd(), '..', '..');

export const CAPTURAS = join(RAIZ, 'storage', 'humo', 'capturas');
export const ESTADO_DE_SESION = join(RAIZ, 'storage', 'humo', 'sesion.json');
