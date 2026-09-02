import type { Ref } from 'vue';
import { watch } from 'vue';
import { trm } from '@/routes/pagos';

/**
 * Precarga la TRM del día del pago y deja `origen_tasa` reflejando de dónde
 * vino el valor.
 *
 * **Ninguna consulta a la API bloquea el formulario.** Un fallo de red, un
 * tiempo agotado o una respuesta rara se tragan en silencio: el campo de tasa
 * sigue siendo editable a mano y `origenTasa` simplemente no cambia a `api`.
 *
 * Se usa tanto en el formulario de pago independiente como, con sus mismos
 * refs pero apuntando a `form.pago.*`, en la pantalla de cierre: es la misma
 * regla de comportamiento en los dos sitios, no una copia.
 */
export function useTrm(
    fechaPago: Ref<string>,
    tasaCambio: Ref<string | number>,
    origenTasa: Ref<string>,
) {
    let actualizandoDesdeApi = false;

    // Si el usuario toca la tasa por su cuenta, el origen pasa a manual. El
    // guardia evita que la propia precarga de la API se marque a sí misma
    // como manual.
    watch(tasaCambio, () => {
        if (actualizandoDesdeApi) {
            actualizandoDesdeApi = false;

            return;
        }

        origenTasa.value = 'manual';
    });

    watch(fechaPago, async (fecha) => {
        if (!fecha) {
            return;
        }

        try {
            const respuesta = await fetch(trm({ query: { fecha } }).url);

            if (!respuesta.ok) {
                return;
            }

            const datos: { tasa_cambio: string | null } =
                await respuesta.json();

            if (datos.tasa_cambio) {
                actualizandoDesdeApi = true;
                tasaCambio.value = datos.tasa_cambio;
                origenTasa.value = 'api';
            }
        } catch {
            // Silencioso a propósito: la API caída no puede impedir registrar
            // el pago a mano.
        }
    });
}
