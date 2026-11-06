import type { BadgeVariants } from '@/components/ui/badge';

/**
 * Qué tono de `Badge` lleva cada valor de una categoría que no es el estado de
 * una tarea (esa tiene su propio `BadgeEstado`).
 *
 * Vive en un solo sitio para que «activo» sea verde en clientes, usuarios y
 * proyectos a la vez: si cada página eligiera su color, el mismo dato se leería
 * distinto según la pantalla. Un valor que no esté en el mapa cae en `neutro`,
 * nunca en un badge sin estilo.
 */
export type TonoDeBadge = NonNullable<BadgeVariants['variant']>;

const ROL_GLOBAL: Record<string, TonoDeBadge> = {
    administrador: 'acento',
    miembro: 'info',
    invitado: 'neutro',
};

const ESTADO_PROYECTO: Record<string, TonoDeBadge> = {
    activo: 'exito',
    pausado: 'aviso',
    finalizado: 'neutro',
};

const ESTADO_PERIODO: Record<string, TonoDeBadge> = {
    abierto: 'info',
    cerrado: 'neutro',
};

const ORIGEN_TASA: Record<string, TonoDeBadge> = {
    api: 'info',
    manual: 'neutro',
};

const TIPO_REPORTE: Record<string, TonoDeBadge> = {
    completo: 'acento',
    cliente: 'info',
};

export const tonoDeOrigenTasa = (origen: string): TonoDeBadge =>
    ORIGEN_TASA[origen] ?? 'neutro';

export const tonoDeTipoReporte = (tipo: string): TonoDeBadge =>
    TIPO_REPORTE[tipo] ?? 'neutro';

export const tonoDeActivo = (activo: boolean): TonoDeBadge =>
    activo ? 'exito' : 'neutro';

export const tonoDeRolGlobal = (rol: string | null): TonoDeBadge =>
    (rol !== null ? ROL_GLOBAL[rol] : undefined) ?? 'neutro';

export const tonoDeEstadoProyecto = (estado: string): TonoDeBadge =>
    ESTADO_PROYECTO[estado] ?? 'neutro';

export const tonoDeEstadoPeriodo = (estado: string): TonoDeBadge =>
    ESTADO_PERIODO[estado] ?? 'neutro';

/**
 * Historial de cambios: lo que añade en verde, lo que modifica en azul, lo
 * que quita en ámbar o rojo. Un borrado es `peligro` porque es irreversible
 * en los modelos sin borrado suave.
 */
const EVENTO_HISTORIAL: Record<string, TonoDeBadge> = {
    creado: 'exito',
    actualizado: 'info',
    eliminado: 'peligro',
    restaurado: 'acento',
    miembro_agregado: 'exito',
    miembro_actualizado: 'info',
    miembro_retirado: 'aviso',
};

/** Módulo del registro, agrupado como en el menú lateral. */
const TIPO_DE_REGISTRO: Record<string, TonoDeBadge> = {
    tarea: 'info',
    proyecto: 'info',
    modulo: 'info',
    cliente: 'acento',
    periodo: 'acento',
    pago: 'acento',
    reunion: 'aviso',
    usuario: 'neutro',
    equipo: 'neutro',
};

export const tonoDeEventoHistorial = (evento: string): TonoDeBadge =>
    EVENTO_HISTORIAL[evento] ?? 'neutro';

export const tonoDeTipoDeRegistro = (tipo: string): TonoDeBadge =>
    TIPO_DE_REGISTRO[tipo] ?? 'neutro';
