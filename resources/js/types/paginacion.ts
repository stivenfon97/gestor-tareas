/** Forma de un paginador de Laravel tal y como llega por Inertia. */
export type EnlacePaginacion = {
    url: string | null;
    label: string;
    active: boolean;
};

export type RespuestaPaginada<T> = {
    data: T[];
    links: EnlacePaginacion[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};
