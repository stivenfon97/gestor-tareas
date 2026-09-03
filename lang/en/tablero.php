<?php

declare(strict_types=1);

return [
    'titulo' => 'Board',
    'descripcion' => 'The project tasks, grouped and movable.',

    'agrupacion' => [
        'titulo' => 'Group by',
        'estado' => 'Status',
        'modulo' => 'Module',
        'prioridad' => 'Priority',
        'tipo' => 'Type',
        'periodo' => 'Period',
    ],

    'columna' => [
        'cargadas' => ':cargadas of :total',
        'cargar_mas' => 'Load more',
        'cargando' => 'Loading…',
        'vacia' => 'Nothing here',
        'no_disponible' => 'Cannot move here from :estado',
        'soltar_aqui' => 'Drop here',
    ],

    'tarjeta' => [
        'mover_a' => 'Move to :columna',
        'abrir' => 'Open :codigo',
    ],

    'teclado' => [
        'ayuda' => 'Arrows to move between cards and columns. Ctrl and arrow to move the focused card. Enter to open it.',
        'movida' => ':codigo moved to :columna.',
        'no_se_puede_mover' => ':codigo cannot be moved to :columna.',
    ],

    'motivo' => [
        'titulo' => 'A reason is required',
        'espera' => 'Why it is on hold',
        'cancelacion' => 'Why it is cancelled',
        'ayuda_espera' => 'On hold means information or resources are missing. Write down what is missing.',
        'ayuda_cancelacion' => 'Cancelling is final: it is never reopened. Write down why it is dropped.',
    ],

    'error_al_mover' => ':codigo could not be moved. It goes back to its column.',
    'sin_permiso_para_mover' => 'You can see the board, but not move cards.',

    'lista' => [
        'titulo' => 'List',
        'descripcion' => 'The project tasks top to bottom, in groups.',
        'plegar' => 'Collapse :grupo',
        'desplegar' => 'Expand :grupo',
        'vacio' => 'No tasks in this group.',
    ],

    'agenda' => [
        'titulo' => 'Calendar',
        'descripcion' => 'The project tasks by their end date.',
        'mes_anterior' => 'Previous month',
        'mes_siguiente' => 'Next month',
        'hoy' => 'Today',
        'sin_fecha' => 'No end date',
        'sin_fecha_ayuda' => 'These tasks are in no cell because nobody has dated them yet.',
        'sin_fecha_recortadas' => 'Showing :mostradas of :total.',
        'mas_tareas' => '+:cantidad more',
        'tareas_del_dia' => 'Tasks on :fecha',
        'dias' => [
            'lunes' => 'Mon',
            'martes' => 'Tue',
            'miercoles' => 'Wed',
            'jueves' => 'Thu',
            'viernes' => 'Fri',
            'sabado' => 'Sat',
            'domingo' => 'Sun',
        ],
    ],

    'vistas' => [
        'tabla' => 'Table',
        'tablero' => 'Board',
        'lista' => 'List',
        'calendario' => 'Calendar',
        'cambiar' => 'Switch view',
    ],
];
