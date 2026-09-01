/**
 * API pública de `TablaDatos`.
 *
 * **El componente no conoce ningún dominio.** Todo lo que sabe de lo que
 * muestra llega por estas estructuras desde la página que lo usa: no hay ni una
 * referencia a tareas, clientes o proyectos dentro de `TablaDatos/`.
 */

export type Direccion = 'asc' | 'desc';

/** Un campo del orden, con su dirección. El orden es una lista, no un campo. */
export type OrdenDeColumna = {
    campo: string;
    direccion: Direccion;
};

export type OpcionDeFiltro = {
    valor: string;
    etiqueta: string;
};

/**
 * Filtro de una columna. `clave` es el nombre que viaja como `filter[clave]`,
 * y tiene que coincidir con un `allowedFilters` del controlador: si no coincide,
 * spatie/laravel-query-builder responde con un error, que es preferible a
 * ignorarlo en silencio.
 */
export type FiltroDeColumna =
    | { tipo: 'texto'; clave: string; marcador?: string }
    | { tipo: 'opciones'; clave: string; opciones: OpcionDeFiltro[] };

export type Alineacion = 'izquierda' | 'derecha';

/**
 * Declaración de una columna. Se escribe **desde fuera**, en la página.
 *
 * @template T Forma de una fila tal y como llega del servidor.
 */
export type ColumnaDeTabla<T> = {
    /** Identificador estable. Es lo que viaja en el parámetro `columnas` de la URL. */
    id: string;

    /** Texto de la cabecera, **ya traducido** por quien declara la columna. */
    etiqueta: string;

    /** Valor a pintar si la página no da un slot `celda-{id}`. */
    valor?: (fila: T) => string | number | boolean | null;

    /** Campo `sort` del servidor. Sin él la columna no se puede ordenar. */
    ordenPor?: string;

    /** Filtro propio de la columna, además de los globales de la página. */
    filtro?: FiltroDeColumna;

    /** Se queda pegada a la izquierda al hacer scroll horizontal. */
    anclada?: boolean;

    /** Nace oculta; el usuario puede mostrarla desde el menú de columnas. */
    ocultaPorDefecto?: boolean;

    /** No se puede ocultar. Para la columna que identifica la fila. */
    fija?: boolean;

    /*
     * Por debajo de `md` la tabla se apila como tarjetas, y ahí no caben seis
     * columnas. Cuál es el titular y cuáles acompañan **lo declara el listado**:
     * el componente no conoce el dominio y no puede adivinar que en tareas
     * manda el código y en clientes el nombre.
     */

    /** Titular de la tarjeta en móvil. Solo una columna por listado. */
    principal?: boolean;

    /** Se ve en la tarjeta sin desplegar. El resto va tras «ver más». */
    prioritaria?: boolean;

    alineacion?: Alineacion;

    /**
     * Monoespaciada. Solo para valores que se comparan verticalmente entre
     * filas: códigos, fechas y montos. Ver docs/05-identidad-visual.md §2.
     */
    mono?: boolean;

    /** Longitud CSS (`'12rem'`). Va a `style`, nunca a una clase de Tailwind. */
    anchoMinimo?: string;
};

/** Una acción que opera sobre las filas seleccionadas. */
export type AccionEnLote = {
    id: string;
    etiqueta: string;
    destructiva?: boolean;
};

/** El paginador de Laravel, en lo que la tabla necesita de él. */
export type PaginacionDeTabla = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

/** Texto de un estado vacío. Siempre lo escribe la página: es del dominio. */
export type TextoVacio = {
    titulo: string;
    cuerpo: string;
};

export type Densidad = 'comoda' | 'compacta';
