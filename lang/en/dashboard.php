<?php

declare(strict_types=1);

return [
    'titulo' => 'Dashboard',
    'descripcion' => 'A summary of your work. No data yet: it arrives in phase 12.',
    'pendiente' => 'Pending: phase 12.',

    'bloques' => [
        'tareas' => [
            'titulo' => 'Your work today',
            'cuerpo' => 'Your in-progress and blocked tasks will go here, overdue ones first.',
            'accion' => 'View all tasks',
        ],
        'periodos' => [
            'titulo' => 'Open periods',
            'cuerpo' => 'The open period of each project and how many days it has been running.',
            'accion' => 'View projects',
        ],
        'bitacora' => [
            'titulo' => 'Latest meetings',
            'cuerpo' => 'The most recent meetings from each project log will go here.',
            'accion' => 'View clients',
        ],
    ],
];
