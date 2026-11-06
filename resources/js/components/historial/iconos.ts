import {
    Banknote,
    Building2,
    CalendarRange,
    FileQuestion,
    FolderGit2,
    ListChecks,
    Network,
    NotebookPen,
    Users,
    UsersRound,
} from '@lucide/vue';
import type { Component } from 'vue';

/**
 * El icono de cada tipo de registro es el de su sección en el menú lateral:
 * en el historial global se reconoce el módulo antes de leerlo.
 */
const ICONO_DE_TIPO: Record<string, Component> = {
    tarea: ListChecks,
    proyecto: FolderGit2,
    modulo: Network,
    cliente: Building2,
    periodo: CalendarRange,
    pago: Banknote,
    reunion: NotebookPen,
    usuario: Users,
    equipo: UsersRound,
};

export const iconoDeTipoDeRegistro = (tipo: string): Component =>
    ICONO_DE_TIPO[tipo] ?? FileQuestion;
