<?php

declare(strict_types=1);

return [
    'bitacora' => 'Log',
    'crear' => 'New meeting',
    'editar' => 'Edit meeting',
    'eliminar_confirmar' => 'Delete this meeting? Its attachments are deleted too.',
    'creada' => 'Meeting created.',
    'actualizada' => 'Meeting updated.',
    'eliminada' => 'Meeting deleted.',
    'vacio' => 'No meetings in the log yet.',

    'campos' => [
        'fecha' => 'Date and time',
        'tipo' => 'Type',
        'titulo' => 'Title',
        'contenido' => 'Content',
        'acuerdos' => 'Agreements',
        'visibilidad' => 'Visibility',
    ],

    'filtros' => [
        'tipo' => 'Type',
        'desde' => 'From',
        'hasta' => 'To',
        'limpiar' => 'Clear filters',
    ],

    'asistentes' => [
        'titulo' => 'Attendees',
        'nombre' => 'Name',
        'organizacion' => 'Organization',
        'usuario' => 'System user (optional)',
        'usuario_ninguno' => 'Not a system user',
        'anadir' => 'Add attendee',
        'quitar' => 'Remove',
        'ninguno_ayuda' => 'Add at least one attendee.',
    ],

    'tareas' => [
        'titulo' => 'Linked tasks',
        'vacio' => 'No tasks linked yet.',
        'vincular' => 'Link existing tasks',
        'vincular_confirmar' => 'Link',
        'vinculadas' => 'Tasks linked.',
        'desvincular_confirmar' => 'Remove the link with this task? The task itself is not deleted.',
        'desvinculada' => 'Task unlinked.',
        'disponibles_vacio' => 'No more project tasks left to link.',
        'crear_titulo' => 'Create tasks from this meeting',
        'crear_ayuda' => 'Paste the agreements and turn them into tasks without leaving this screen. They stay linked to this meeting.',
    ],

    'editor' => [
        'negrita' => 'Bold',
        'cursiva' => 'Italic',
        'tachado' => 'Strikethrough',
        'resaltado' => 'Highlight',
        'encabezado_1' => 'Heading 1',
        'encabezado_2' => 'Heading 2',
        'encabezado_3' => 'Heading 3',
        'lista_desordenada' => 'Bullet list',
        'lista_ordenada' => 'Numbered list',
        'cita' => 'Quote',
        'codigo' => 'Code',
        'enlace' => 'Link',
        'enlace_quitar' => 'Remove link',
        'enlace_url' => 'Link URL',
        'tabla' => 'Insert table',
    ],

    'validacion' => [
        'documento_invalido' => 'The meeting content is not valid.',
    ],
];
