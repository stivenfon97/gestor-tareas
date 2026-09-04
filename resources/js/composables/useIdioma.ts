import { currentLocale, loadLanguageAsync } from 'laravel-vue-i18n';
import { computed } from 'vue';
import type { ComputedRef } from 'vue';

/**
 * Selector de idioma. Mismo patrón que `useAppearance` para el tema:
 * preferencia persistida en una cookie, sin recarga de página.
 *
 * A diferencia del tema, el texto de la interfaz no necesita un viaje al
 * servidor para cambiar: `enums.*.etiqueta()` y compañía ya mandan la
 * **clave**, nunca el texto resuelto (ver `.ai/rules/js.md` y
 * `docs/00-entorno.md#traducciones`), así que `loadLanguageAsync()` basta
 * para repintar toda la pantalla en el idioma nuevo. La cookie es para lo
 * poco que sí se resuelve en el servidor -los mensajes de validación de
 * Laravel y el `lang` del `<html>` en la próxima carga completa-, y la lee
 * `App\Http\Middleware\HandleIdioma`.
 */

export type Idioma = 'es' | 'en';

const IDIOMAS: readonly Idioma[] = ['es', 'en'];

const setCookie = (nombre: string, valor: string, dias = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = dias * 24 * 60 * 60;

    document.cookie = `${nombre}=${valor};path=/;max-age=${maxAge};SameSite=Lax`;
};

export type UseIdiomaReturn = {
    idioma: ComputedRef<Idioma>;
    idiomasDisponibles: readonly Idioma[];
    cambiarIdioma: (valor: Idioma) => Promise<void>;
};

export function useIdioma(): UseIdiomaReturn {
    const idioma = computed<Idioma>(
        () => (currentLocale.value as Idioma) ?? 'es',
    );

    async function cambiarIdioma(valor: Idioma): Promise<void> {
        if (valor === idioma.value) {
            return;
        }

        await loadLanguageAsync(valor);
        setCookie('idioma', valor);
    }

    return { idioma, idiomasDisponibles: IDIOMAS, cambiarIdioma };
}
