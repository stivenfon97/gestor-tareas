<?php

declare(strict_types=1);

return [
    'titulo' => 'Tasks',
    'crear' => 'New task',
    'editar' => 'Edit task',
    'creada' => 'Task :codigo created.',
    'actualizada' => 'Task updated.',
    'eliminada' => 'Task deleted.',
    'eliminar' => 'Delete task',
    'eliminar_confirmar' => 'Delete “:codigo”? The delete is soft: the code is never reused and the history is kept.',
    'sin_tareas' => 'No tasks to show yet.',
    'sin_tareas_ayuda' => 'Tasks you create in this project will show up here.',

    'codigo' => 'Code',
    'proyecto' => 'Project',
    'modulo' => 'Module',
    'sin_modulo' => 'No module',
    'periodo' => 'Period',
    'sin_periodo' => 'No period',
    'periodo_numero' => 'Period :consecutivo',
    'tipo' => 'Type',
    'prioridad' => 'Priority',
    'descripcion' => 'Description',
    'creada_por' => 'Created by',
    'fecha_inicio' => 'Started',
    'fecha_fin' => 'Finished',
    'arrastrada' => 'Carried over',
    'arrastrada_de' => 'Carried over from period :consecutivo',
    'tipo_no_cambia' => 'The type is set on creation and never changes.',
    'sin_periodo_abierto' => 'This project has no open period. The task will be created without one, and will need a period assigned once one is open.',

    'origen' => 'Fixes',
    'origen_ayuda' => 'Only for fix or support tasks. Links this task to the delivery that caused it.',
    'correcciones' => 'Fixes it caused',
    'correcciones_conteo' => ':total fixes',
    'sin_correcciones' => 'This task has caused no fixes.',

    'reuniones' => [
        'titulo' => 'Meetings',
        'vacio' => 'This task did not come out of any recorded meeting.',
    ],

    'todas' => [
        'titulo' => 'All tasks',
        'descripcion' => 'Tasks across all of your projects, most recently touched first.',
        'sin_tareas_ayuda' => 'Once you take part in a project with tasks, they will show up here.',
    ],

    'estado' => [
        'titulo' => 'Status',
        'cambiar' => 'Change status',
        'sin_transiciones' => 'This status is terminal: it allows no transitions.',
        'mover_a' => 'Move to :estado',
        'motivo_espera' => 'Reason for waiting',
        'motivo_cancelacion' => 'Reason for cancelling',
        'motivo_espera_ayuda' => 'Missing information or resources. A blocked task with no reason recorded is one nobody knows how to unblock.',
        'motivo_cancelacion_ayuda' => 'It stays in the history even after the task leaves this status.',
    ],
    'estado_cambiado' => 'Status updated.',

    'historial' => [
        'titulo' => 'Status history',
        'vacio' => 'It has not changed status yet.',
        'inicial' => 'Created',
        'por' => 'by :usuario',
    ],

    'notas' => [
        'titulo' => 'Notes',
        'creada' => 'Note added.',
        'agregar' => 'Add note',
        'contenido' => 'Note',
        'vacio' => 'No notes yet.',
        'visibilidad' => 'Visibility',
        'interna_ayuda' => 'Notes are internal by default. Marking one visible to the client is an explicit decision.',
    ],

    'filtros' => [
        'texto' => 'Search name, code and description',
        'todos' => 'All',
        'modulo_incluye_hijos' => 'Includes tasks from submodules.',
        'solo_arrastradas' => 'Carried over only',
        'limpiar' => 'Clear filters',
    ],

    'orden' => [
        'titulo' => 'Sort by',
        'codigo' => 'Code',
        'nombre' => 'Name',
        'estado' => 'Status',
        'prioridad' => 'Priority',
        'fecha_fin' => 'Finish date',
        'updated_at' => 'Last updated',
    ],

    'rapida' => [
        'titulo' => 'Quick capture',
        'descripcion' => 'Paste several lines at once. One task is created per line.',
        'formato' => 'Format: name | status | description | module. Only the name is required.',
        'ejemplo' => "migrate the client interface | Pendiente | move the UI to the new design | Clientes\nreview the VAT calculation\nfix the PDF footer | En Proceso | the tax id is missing",
        'texto' => 'Lines to capture',
        'previsualizar' => 'Preview',
        'previsualizacion' => 'This is what will be created',
        'previsualizacion_ayuda' => 'Review and fix before saving. Nothing has been created yet.',
        'confirmar' => 'Create :total tasks',
        'creadas' => ':total tasks created.',
        'quitar_fila' => 'Discard this line',
        'linea' => 'Line :numero',
        'modulo_nuevo' => 'Create module “:nombre”',
        'sin_filas' => 'Paste some lines and hit preview.',

        'avisos' => [
            'sin_nombre' => 'This line has no name, and the name is required.',
            'estado_desconocido' => 'The status was not recognised; “Pending” will be used.',
            'modulo_nuevo' => 'That module does not exist in the project. It will be created on save.',
            'requiere_motivo' => 'This status requires a reason.',
        ],
    ],

    'validacion' => [
        'tarea_de_otro_proyecto' => 'That task does not belong to this project.',
        'modulo_de_otro_proyecto' => 'The module does not belong to this project.',
        'periodo_de_otro_proyecto' => 'The period does not belong to this project.',
        'periodo_cerrado' => 'That period is already closed: it does not accept new tasks.',
        'origen_de_otro_proyecto' => 'The origin task does not belong to this project.',
        'origen_solo_correccion_o_soporte' => 'Only a fix or support task can have an origin task.',
        'origen_a_si_misma' => 'A task cannot be its own origin.',
        'origen_cadena_circular' => 'That link would create a circular chain of origins.',
        'motivo_espera_obligatorio' => 'Moving to “Waiting” requires explaining what is missing.',
        'motivo_cancelacion_obligatorio' => 'Cancelling requires explaining why.',
        'transicion_no_permitida' => 'Cannot move from “:desde” to “:hacia”.',
    ],

    'lote' => [
        'copiar_codigos' => 'Copy codes',
        'codigos_copiados' => ':total codes copied to the clipboard.',
    ],
];
