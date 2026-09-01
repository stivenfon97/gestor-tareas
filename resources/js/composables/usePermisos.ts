import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { RolGlobal } from '@/types/auth';

/**
 * Comprobaciones de permisos para la interfaz.
 *
 * **Sirve solo para ocultar controles.** La autorización real vive en las
 * policies del servidor; si esto y una policy discrepan, manda la policy y lo
 * que hay aquí es un bug de presentación, no un agujero.
 */
export function usePermisos() {
    const page = usePage();

    const rolGlobal = computed<RolGlobal | null>(
        () => page.props.auth?.rolGlobal ?? null,
    );
    const permisos = computed<string[]>(() => page.props.auth?.permisos ?? []);

    const esAdministrador = computed(() => rolGlobal.value === 'administrador');

    /** El invitado es de solo lectura en todo el sistema, tenga el rol de proyecto que tenga. */
    const esSoloLectura = computed(() => rolGlobal.value === 'invitado');

    /** Refleja `Gate::before`: el administrador pasa todas las comprobaciones. */
    const puede = (permiso: string): boolean =>
        esAdministrador.value || permisos.value.includes(permiso);

    return { rolGlobal, permisos, esAdministrador, esSoloLectura, puede };
}
