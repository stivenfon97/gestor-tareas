/** Un nodo del árbol de módulos tal y como lo entrega `ArbolDeModulos`. */
export type NodoModulo = {
    id: number;
    parent_id: number | null;
    nombre: string;
    descripcion: string | null;
    orden: number;
    hijos: NodoModulo[];
    /** Tareas colgadas directamente de este módulo. */
    tareas_directas: number;
    /** Incluye las de todos sus descendientes. */
    tareas_totales: number;
    submodulos_totales: number;
};

/** Un nivel del árbol: un padre y el orden completo de sus hijos. */
export type NivelDeModulos = {
    parent_id: number | null;
    modulos: number[];
};
