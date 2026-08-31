import { onMounted, ref } from 'vue';
import type { Densidad } from '@/components/TablaDatos/tipos';

/**
 * Densidad de fila de los listados, recordada por navegador.
 *
 * **No va en la URL** a propósito, al revés que el orden, los filtros y las
 * columnas: es una preferencia de quien mira, no parte de lo que se comparte.
 * Enviarle a alguien un enlace y forzarle además el tamaño de fila sería
 * decidir por él con qué comodidad lee.
 *
 * Por defecto **cómoda**. La compacta es una herramienta para barrer un periodo
 * entero de un vistazo, no el estado de reposo de una aplicación que se mira
 * ocho horas seguidas.
 */
const CLAVE = 'densidad-tabla';

const densidad = ref<Densidad>('comoda');

const leerGuardada = (): Densidad | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    const guardada = window.localStorage.getItem(CLAVE);

    return guardada === 'comoda' || guardada === 'compacta' ? guardada : null;
};

export function useDensidad() {
    onMounted(() => {
        densidad.value = leerGuardada() ?? 'comoda';
    });

    const fijarDensidad = (valor: Densidad): void => {
        densidad.value = valor;

        if (typeof window !== 'undefined') {
            window.localStorage.setItem(CLAVE, valor);
        }
    };

    return { densidad, fijarDensidad };
}
