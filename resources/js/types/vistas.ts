import type {
    EstadoTarea,
    PrioridadTarea,
    TipoTarea,
    Transicion,
} from './tareas';

/** Las cuatro formas de ver las mismas tareas. */
export type TipoDeVista = 'tabla' | 'kanban' | 'lista' | 'calendario';

/** Por qué eje se parte el conjunto. */
export type Agrupacion = 'estado' | 'modulo' | 'prioridad' | 'tipo' | 'periodo';

/**
 * Una tarjeta del tablero o de la lista.
 *
 * Trae `transiciones` porque **la interfaz nunca deduce la matriz**: cuáles son
 * los destinos válidos desde el estado de esta tarjeta lo dice el servidor, y
 * replicarlo en Vue sería una cuarta fuente de verdad —enum, skill, diagrama y
 * cliente— y la única que ninguna prueba comprueba.
 */
export type TarjetaDeTarea = {
    id: number;
    codigo: string;
    nombre: string;
    estado: EstadoTarea;
    tipo: TipoTarea;
    prioridad: PrioridadTarea;
    modulo_id: number | null;
    modulo: string | null;
    periodo: number | null;
    arrastrada: boolean;
    fecha_fin: string | null;
    orden: number;
    transiciones: Transicion[];
};

/**
 * Una columna del tablero o un grupo de la lista.
 *
 * `total` es el **total real**, no el número de tarjetas cargadas: una columna
 * con doscientas entregadas trae cincuenta y sigue diciendo doscientas.
 */
export type ColumnaDeTablero = {
    clave: string;
    etiqueta: string;
    total: number;
    tareas: TarjetaDeTarea[];
};

/** Una tarjeta del calendario: sin transiciones, porque ahí no se mueve nada. */
export type TarjetaDeAgenda = {
    id: number;
    codigo: string;
    nombre: string;
    estado: EstadoTarea;
    tipo: TipoTarea;
    prioridad: PrioridadTarea;
    modulo: string | null;
    fecha_fin: string | null;
};

/**
 * Lo que una vista guarda: los mismos parámetros que viajan en la URL.
 *
 * `filter` es un objeto anidado (`filter[estado]=en_espera`); el resto —`sort`,
 * `columnas`, `agrupar`, `por_pagina`— son escalares. Está tipado y no como
 * `unknown` porque tiene que poder viajar como cuerpo de una petición.
 */
export type ConfiguracionDeVista = Record<
    string,
    string | number | Record<string, string>
>;

/**
 * Una vista guardada, ya saneada por el servidor.
 *
 * `parcial` avisa de que su configuración apuntaba a algo que ya no existe —un
 * módulo borrado, un periodo de otro proyecto— y se ignoró esa parte. Es lo que
 * evita las dos respuestas malas: reventar, o devolver cero filas fingiendo que
 * no hay trabajo.
 */
export type VistaGuardada = {
    id: number;
    nombre: string;
    tipo: TipoDeVista;
    es_predeterminada: boolean;
    ambito: 'global' | 'proyecto';
    configuracion: ConfiguracionDeVista;
    parcial: boolean;
};
