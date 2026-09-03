<?php

declare(strict_types=1);

return [
    'titulo' => 'Tablero',
    'descripcion' => 'El resumen de tu trabajo en todos tus proyectos.',

    'bloques' => [
        'trabajo' => [
            'titulo' => 'Tu trabajo de hoy',
            'descripcion' => 'Tareas en proceso y en espera, lo que más tiempo lleva parado primero.',
            'vacio_titulo' => 'Nada parado',
            'vacio_cuerpo' => 'No tienes tareas en proceso ni en espera en ningún proyecto.',
            'dias_en_estado' => ':dias día(s) en este estado',
            'motivo' => 'Motivo: :motivo',
            'accion' => 'Ver todas las tareas',
        ],

        'periodos' => [
            'titulo' => 'Periodos abiertos',
            'descripcion' => 'Uno por proyecto: días transcurridos y tareas sin terminar.',
            'vacio_titulo' => 'Sin periodos abiertos',
            'vacio_cuerpo' => 'Ninguno de tus proyectos tiene un periodo abierto ahora mismo.',
            'dias_transcurridos' => ':dias día(s) transcurridos',
            'tareas_sin_terminar' => ':total tarea(s) sin terminar',
            'vencido' => 'Vencido',
            'vencido_ayuda' => 'Ya pasó la fecha de fin prevista. El cierre sigue siendo manual.',
        ],

        'reuniones' => [
            'titulo' => 'Últimas reuniones',
            'descripcion' => 'Lo más reciente de la bitácora de tus proyectos.',
            'vacio_titulo' => 'Sin reuniones',
            'vacio_cuerpo' => 'Todavía no hay ninguna reunión registrada en tus proyectos.',
        ],

        'ingresos' => [
            'titulo' => 'Ingresos por periodo cerrado',
            'descripcion' => 'Lo cobrado en COP, por cliente.',
            'vacio_titulo' => 'Sin periodos cerrados',
            'vacio_cuerpo' => 'Todavía no hay ningún periodo cerrado con pago registrado.',
            'periodos_count' => ':total periodo(s) cerrado(s)',
        ],
    ],
];
