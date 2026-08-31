export type EstadoTarea =
    | 'pendiente'
    | 'en_proceso'
    | 'en_espera'
    | 'terminada'
    | 'entregada'
    | 'cancelada';

export type TipoTarea = 'desarrollo' | 'correccion' | 'soporte' | 'mejora' | 'investigacion';

export type PrioridadTarea = 'baja' | 'media' | 'alta' | 'critica';

export type Opcion = { valor: string; etiqueta: string };

/**
 * Una transición ofrecida por el servidor.
 *
 * `requiere_motivo` trae el **nombre del campo** (`motivo_espera` o
 * `motivo_cancelacion`), no un booleano: son campos distintos y el formulario
 * tiene que enviar el correcto.
 */
export type Transicion = {
    valor: EstadoTarea;
    etiqueta: string;
    requiere_motivo: 'motivo_espera' | 'motivo_cancelacion' | null;
};

export type FilaTarea = {
    id: number;
    codigo: string;
    nombre: string;
    estado: EstadoTarea;
    tipo: TipoTarea;
    prioridad: PrioridadTarea;
    modulo: string | null;
    periodo: number | null;
    arrastrada: boolean;
    fecha_fin: string | null;
};

export type OpcionesDeTarea = {
    estados: Opcion[];
    tipos: Opcion[];
    prioridades: Opcion[];
    modulos: Array<{ id: number; ruta: string }>;
    periodos: Array<{ id: number; consecutivo: number; estado: string }>;
};

/** Una fila de la previsualización de captura rápida, ya editable. */
export type FilaCapturaRapida = {
    linea: number;
    original: string;
    nombre: string;
    estado: EstadoTarea;
    descripcion: string | null;
    modulo: string | null;
    modulo_id: number | null;
    modulo_por_crear: boolean;
    requiere_motivo: 'motivo_espera' | 'motivo_cancelacion' | null;
    avisos: string[];
    tipo: TipoTarea;
    prioridad: PrioridadTarea | null;
    motivo_espera: string | null;
    motivo_cancelacion: string | null;
};
