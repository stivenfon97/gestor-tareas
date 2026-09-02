import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { trans } from '@/lib/i18n';
import {
    columna as moverDeColumna,
    orden as reordenarRuta,
} from '@/routes/proyectos/tareas';
import { update as cambiarEstado } from '@/routes/tareas/estado';
import type { Transicion } from '@/types/tareas';
import type {
    Agrupacion,
    ColumnaDeTablero,
    TarjetaDeTarea,
} from '@/types/vistas';

/**
 * La lógica de mover tarjetas en el tablero.
 *
 * # Las dos formas de soltar, que no son la misma
 *
 * **Agrupado por estado**, soltar una tarjeta en otra columna es un **cambio de
 * estado**: pasa por `tareas.estado.update`, que valida contra la matriz de
 * once transiciones, exige motivo en dos destinos, rellena las fechas
 * automáticas y deja fila en el historial.
 *
 * **Agrupado por módulo o por prioridad**, soltar es un cambio de campo: un
 * `update` y nada más.
 *
 * **Agrupado por tipo no se mueve nada entre columnas.** El tipo de una tarea
 * se fija al crear y no cambia (D1.3), así que sus columnas se leen y se
 * reordenan pero no son destinos.
 *
 * Los tres caminos están escritos aparte a propósito. Unificarlos en uno que
 * reciba «columna destino» obligaría a un `if` para decidir si aplica la
 * matriz, y ese `if` es exactamente el que alguien simplifica el día que no
 * sepa por qué está.
 *
 * # Optimista, pero con reversión de verdad
 *
 * La tarjeta se mueve en la pantalla antes de que conteste el servidor, porque
 * esperar medio segundo con la tarjeta a medio camino se siente roto. Pero
 * **toda actualización optimista necesita su camino de vuelta**: una tarjeta
 * que se queda donde el usuario la soltó mientras el servidor dice que no es
 * peor que no tener arrastre, porque miente sobre lo que hay guardado.
 *
 * Antes de cada movimiento se guarda una copia de las columnas, y `onError` la
 * restaura entera.
 */
export type LecturaDeTablero = {
    proyectoId: number;
    columnas: () => ColumnaDeTablero[];
    agrupacion: () => Agrupacion;
    puedeMover: () => boolean;
};

/** Una petición de motivo pendiente de que el usuario la conteste. */
type MotivoPendiente = {
    tarea: TarjetaDeTarea;
    transicion: Transicion;
    /** Instantánea previa, para volver si se cancela. */
    respaldo: ColumnaDeTablero[];
};

const copiar = (columnas: ColumnaDeTablero[]): ColumnaDeTablero[] =>
    columnas.map((columna) => ({ ...columna, tareas: [...columna.tareas] }));

export function useTablero(lectura: LecturaDeTablero) {
    /**
     * Copia local sobre la que se arrastra.
     *
     * `vuedraggable` escribe en el array que le des, y las props de Inertia son
     * de solo lectura. Se resincroniza en cuanto el servidor manda columnas
     * nuevas: el servidor sigue siendo la fuente de verdad, esto es solo lo que
     * se está pintando mientras contesta.
     */
    const columnas = ref<ColumnaDeTablero[]>(copiar(lectura.columnas()));

    watch(
        () => lectura.columnas(),
        (nuevas) => {
            columnas.value = copiar(nuevas);
        },
        { deep: true },
    );

    /** La tarjeta que se está arrastrando ahora mismo, si hay alguna. */
    const arrastrando = ref<TarjetaDeTarea | null>(null);

    /**
     * La última columna sobre la que el puntero pidió entrar, admitida o no.
     *
     * Hace falta porque SortableJS deja la tarjeta **en la última columna que
     * la admitió**, no en la que estaba. Arrastrando de «Pendiente» a
     * «Terminada» el puntero cruza «En proceso», que sí la admite: al soltar
     * sobre la columna prohibida la tarjeta se quedaba en la del camino y se
     * guardaba una transición que nadie pidió. Comparando la intención con
     * dónde aterrizó, un soltar rechazado devuelve la tarjeta a su sitio.
     */
    const intencion = ref<string | null>(null);

    const motivoPendiente = ref<MotivoPendiente | null>(null);

    /** Lo último que anunciar a un lector de pantalla. */
    const anuncio = ref('');

    /**
     * Si una tarjeta puede ir a una columna.
     *
     * Agrupado por estado, **la matriz la da el servidor**: viene en
     * `transiciones` de cada tarjeta. Replicarla aquí sería una cuarta fuente
     * de verdad —enum, skill, diagrama y cliente— y la única que ninguna prueba
     * comprueba.
     */
    const puedeIr = (tarea: TarjetaDeTarea, clave: string): boolean => {
        if (!lectura.puedeMover()) {
            return false;
        }

        const agrupacion = lectura.agrupacion();

        if (agrupacion === 'estado') {
            return tarea.transiciones.some(
                (transicion) => transicion.valor === clave,
            );
        }

        // El tipo no cambia nunca: sus columnas no son destinos.
        if (agrupacion === 'tipo') {
            return false;
        }

        return true;
    };

    /** La columna en la que está una tarjeta ahora mismo. */
    const columnaDe = (id: number): ColumnaDeTablero | undefined =>
        columnas.value.find((columna) =>
            columna.tareas.some((tarea) => tarea.id === id),
        );

    /** Columnas marcadas como no disponibles mientras dura el arrastre. */
    const noDisponible = (clave: string): boolean => {
        const tarea = arrastrando.value;

        if (tarea === null) {
            return false;
        }

        const origen = columnaDe(tarea.id);

        return origen?.clave !== clave && !puedeIr(tarea, clave);
    };

    const revertir = (respaldo: ColumnaDeTablero[], codigo: string): void => {
        columnas.value = respaldo;
        toast.error(trans('tablero.error_al_mover', { codigo }));
    };

    /** El orden de una columna, tal y como está en la pantalla. */
    const ordenDe = (clave: string): number[] =>
        (
            columnas.value.find((columna) => columna.clave === clave)?.tareas ??
            []
        ).map((tarea) => tarea.id);

    /**
     * Persiste el orden de una columna. Una petición por operación con el
     * conjunto entero, nunca una por tarjeta: un arrastre mueve a todas las de
     * debajo, y treinta peticiones seguidas dejan la columna a medias.
     */
    const persistirOrden = (
        clave: string,
        respaldo?: ColumnaDeTablero[],
    ): void => {
        router.put(
            reordenarRuta(lectura.proyectoId).url,
            { tareas: ordenDe(clave) },
            {
                preserveScroll: true,
                preserveState: true,
                onError: () => {
                    if (respaldo !== undefined) {
                        columnas.value = respaldo;
                    }
                },
            },
        );
    };

    /**
     * Opciones de una petición que mueve una tarjeta, con su reversión.
     *
     * **No basta con `onError`.** Inertia solo lo llama cuando el servidor
     * contesta 422 con errores de validación; un 500, una caída de red o una
     * respuesta que no es de Inertia no pasan por ahí, y la tarjeta se quedaría
     * donde el usuario la soltó mientras el servidor no ha guardado nada. Por
     * eso el criterio se invierte: **se revierte salvo que se confirme el
     * éxito**, y de eso se encarga `onFinish`.
     */
    const conReversion = (
        tarea: TarjetaDeTarea,
        destino: string,
        respaldo: ColumnaDeTablero[],
    ) => {
        let resuelto = false;

        return {
            preserveScroll: true,
            onSuccess: (): void => {
                resuelto = true;
                persistirOrden(destino);
            },
            onError: (): void => {
                resuelto = true;
                revertir(respaldo, tarea.codigo);
            },
            onFinish: (): void => {
                if (!resuelto) {
                    revertir(respaldo, tarea.codigo);
                }
            },
        };
    };

    /** El cambio de estado, con su motivo si el destino lo exige. */
    const enviarCambioDeEstado = (
        tarea: TarjetaDeTarea,
        destino: string,
        respaldo: ColumnaDeTablero[],
        motivos: Record<string, string> = {},
    ): void => {
        router.put(
            cambiarEstado(tarea.id).url,
            { estado: destino, ...motivos },
            conReversion(tarea, destino, respaldo),
        );
    };

    const enviarCambioDeCampo = (
        tarea: TarjetaDeTarea,
        clave: string,
        respaldo: ColumnaDeTablero[],
    ): void => {
        const agrupacion = lectura.agrupacion();
        const campo = agrupacion === 'modulo' ? 'modulo_id' : 'prioridad';
        const valor =
            agrupacion === 'modulo'
                ? clave === ''
                    ? null
                    : Number(clave)
                : clave;

        router.put(
            moverDeColumna({ proyecto: lectura.proyectoId, tarea: tarea.id })
                .url,
            { campo, valor, [campo]: valor },
            conReversion(tarea, clave, respaldo),
        );
    };

    /**
     * Mueve una tarjeta a una columna. Es el único camino: lo llaman el
     * arrastre y el teclado.
     *
     * @param respaldo La instantánea de antes de moverla en pantalla.
     */
    const mover = (
        tarea: TarjetaDeTarea,
        clave: string,
        respaldo: ColumnaDeTablero[],
    ): void => {
        if (!puedeIr(tarea, clave)) {
            revertir(respaldo, tarea.codigo);

            return;
        }

        if (lectura.agrupacion() !== 'estado') {
            enviarCambioDeCampo(tarea, clave, respaldo);

            return;
        }

        const transicion = tarea.transiciones.find(
            (candidata) => candidata.valor === clave,
        );

        if (transicion === undefined) {
            revertir(respaldo, tarea.codigo);

            return;
        }

        // Dos destinos exigen motivo, y hay que pedirlo **antes** de confirmar:
        // mandarlo sin motivo daría un 422 y la tarjeta volvería sola, que se
        // lee como un fallo en vez de como una pregunta.
        if (transicion.requiere_motivo !== null) {
            motivoPendiente.value = { tarea, transicion, respaldo };

            return;
        }

        enviarCambioDeEstado(tarea, clave, respaldo);
    };

    /** El usuario contestó el motivo. */
    const confirmarMotivo = (motivo: string): void => {
        const pendiente = motivoPendiente.value;

        if (pendiente === null) {
            return;
        }

        motivoPendiente.value = null;

        enviarCambioDeEstado(
            pendiente.tarea,
            pendiente.transicion.valor,
            pendiente.respaldo,
            { [pendiente.transicion.requiere_motivo as string]: motivo },
        );
    };

    /**
     * El usuario cerró el diálogo sin contestar.
     *
     * La tarjeta ya se había movido en la pantalla, así que cancelar tiene que
     * devolverla a su columna. Dejarla donde estaba diría que el cambio se
     * guardó.
     */
    const cancelarMotivo = (): void => {
        const pendiente = motivoPendiente.value;

        if (pendiente === null) {
            return;
        }

        columnas.value = pendiente.respaldo;
        motivoPendiente.value = null;
    };

    /**
     * Mueve la tarjeta enfocada a la columna contigua **disponible**.
     *
     * Es el equivalente de teclado del arrastre. Salta las columnas a las que
     * la tarjeta no puede ir en lugar de pararse en ellas: pararse obligaría a
     * pulsar tres veces para llegar a la siguiente que sí acepta.
     */
    const moverConTeclado = (
        tarea: TarjetaDeTarea,
        direccion: -1 | 1,
    ): void => {
        const origen = columnas.value.findIndex((columna) =>
            columna.tareas.some((candidata) => candidata.id === tarea.id),
        );

        for (
            let indice = origen + direccion;
            indice >= 0 && indice < columnas.value.length;
            indice += direccion
        ) {
            const destino = columnas.value[indice];

            if (!puedeIr(tarea, destino.clave)) {
                continue;
            }

            const respaldo = copiar(columnas.value);

            columnas.value[origen].tareas = columnas.value[
                origen
            ].tareas.filter((candidata) => candidata.id !== tarea.id);
            columnas.value[indice].tareas = [tarea, ...destino.tareas];

            anuncio.value = trans('tablero.teclado.movida', {
                codigo: tarea.codigo,
                columna: destino.etiqueta,
            });

            mover(tarea, destino.clave, respaldo);

            return;
        }

        // Ninguna columna en esa dirección la acepta. Se dice, no se ignora en
        // silencio: quien no ve la pantalla necesita saber que pulsó bien.
        anuncio.value = trans('tablero.teclado.no_se_puede_mover', {
            codigo: tarea.codigo,
            columna: columnas.value[origen]?.etiqueta ?? '',
        });
    };

    const hayMasEn = (columna: ColumnaDeTablero): boolean =>
        columna.tareas.length < columna.total;

    return {
        columnas,
        arrastrando,
        intencion,
        motivoPendiente: computed(() => motivoPendiente.value),
        anuncio,
        copiar,
        puedeIr,
        noDisponible,
        hayMasEn,
        mover,
        moverConTeclado,
        persistirOrden,
        confirmarMotivo,
        cancelarMotivo,
    };
}
