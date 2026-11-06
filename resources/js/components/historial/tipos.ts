/**
 * Forma de una entrada del historial de cambios tal y como la sirve
 * `App\Services\PresentarHistorial`.
 *
 * El servidor ya resolvió las referencias (un `modulo_id` llega como el nombre
 * del módulo) y le pone a cada valor un `tipo` que dice cómo pintarlo; aquí
 * solo se formatea según el idioma activo.
 */
export type TipoDeValor =
    | 'texto'
    | 'clave'
    | 'fecha'
    | 'fecha_hora'
    | 'monto_usd'
    | 'monto_cop'
    | 'booleano'
    | 'vacio'
    | 'oculto';

export type ValorDeHistorial = {
    tipo: TipoDeValor;
    valor: string | number | boolean | null;
};

export type CambioDeHistorial = {
    campo: string;
    /** Clave de traducción del nombre del campo; `null` si no hay ninguna. */
    etiqueta: string | null;
    /** `null` cuando ese lado no existe (lo anterior de una creación). */
    anterior: ValorDeHistorial | null;
    nuevo: ValorDeHistorial | null;
};

export type EntradaDeHistorial = {
    id: number;
    evento: string;
    fecha: string | null;
    usuario: { id: number; name: string } | null;
    cambios: CambioDeHistorial[];
    /** Solo en la pantalla global: a qué registro pertenece la entrada. */
    registro?: {
        tipo: string;
        id: number;
        etiqueta: string | null;
        url: string | null;
    };
};

/** Alias de `HistorialCambio::TIPOS`. */
export type TipoDeRegistro =
    | 'cliente'
    | 'proyecto'
    | 'modulo'
    | 'tarea'
    | 'periodo'
    | 'pago'
    | 'reunion'
    | 'equipo'
    | 'usuario';
