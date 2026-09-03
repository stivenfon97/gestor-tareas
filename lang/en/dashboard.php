<?php

declare(strict_types=1);

return [
    'titulo' => 'Dashboard',
    'descripcion' => 'A summary of your work across all your projects.',

    'bloques' => [
        'trabajo' => [
            'titulo' => 'Your work today',
            'descripcion' => 'In-progress and blocked tasks, longest-stalled first.',
            'vacio_titulo' => 'Nothing stalled',
            'vacio_cuerpo' => 'You have no in-progress or blocked tasks in any project.',
            'dias_en_estado' => ':dias day(s) in this state',
            'motivo' => 'Reason: :motivo',
            'accion' => 'View all tasks',
        ],

        'periodos' => [
            'titulo' => 'Open periods',
            'descripcion' => 'One per project: days elapsed and unfinished tasks.',
            'vacio_titulo' => 'No open periods',
            'vacio_cuerpo' => 'None of your projects has an open period right now.',
            'dias_transcurridos' => ':dias day(s) elapsed',
            'tareas_sin_terminar' => ':total unfinished task(s)',
            'vencido' => 'Overdue',
            'vencido_ayuda' => 'The expected end date already passed. Closing is still manual.',
        ],

        'reuniones' => [
            'titulo' => 'Latest meetings',
            'descripcion' => 'The most recent entries in your projects\' logs.',
            'vacio_titulo' => 'No meetings',
            'vacio_cuerpo' => 'No meeting has been logged in your projects yet.',
        ],

        'ingresos' => [
            'titulo' => 'Income by closed period',
            'descripcion' => 'Collected in COP, by client.',
            'vacio_titulo' => 'No closed periods',
            'vacio_cuerpo' => 'No period with a registered payment has been closed yet.',
            'periodos_count' => ':total closed period(s)',
        ],
    ],
];
