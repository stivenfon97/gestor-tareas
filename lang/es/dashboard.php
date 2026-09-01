<?php

declare(strict_types=1);

/*
 * Tablero. Hasta la fase 12 no tiene datos: cada bloque dice qué va a haber
 * ahí y ofrece el camino que hoy sí existe. **Nunca cifras de mentira.**
 */
return [
    'titulo' => 'Tablero',
    'descripcion' => 'El resumen de tu trabajo. Todavía no tiene datos: llegan en la fase 12.',
    'pendiente' => 'Pendiente: fase 12.',

    'bloques' => [
        'tareas' => [
            'titulo' => 'Tu trabajo de hoy',
            'cuerpo' => 'Aquí irán tus tareas en proceso y en espera, con lo que se pasó de fecha primero.',
            'accion' => 'Ver todas las tareas',
        ],
        'periodos' => [
            'titulo' => 'Periodos abiertos',
            'cuerpo' => 'Aquí irá el periodo abierto de cada proyecto y cuántos días lleva.',
            'accion' => 'Ver proyectos',
        ],
        'bitacora' => [
            'titulo' => 'Últimas reuniones',
            'cuerpo' => 'Aquí irán las reuniones más recientes de la bitácora de cada proyecto.',
            'accion' => 'Ver clientes',
        ],
    ],
];
