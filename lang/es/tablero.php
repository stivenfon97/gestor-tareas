<?php

declare(strict_types=1);

return [
    'titulo' => 'Tablero',
    'descripcion' => 'Las tareas del proyecto, agrupadas y movibles.',

    'agrupacion' => [
        'titulo' => 'Agrupar por',
        'estado' => 'Estado',
        'modulo' => 'Módulo',
        'prioridad' => 'Prioridad',
        'tipo' => 'Tipo',
        'periodo' => 'Periodo',
    ],

    'columna' => [
        'cargadas' => ':cargadas de :total',
        'cargar_mas' => 'Cargar más',
        'cargando' => 'Cargando…',
        'vacia' => 'Nada aquí',
        'no_disponible' => 'No se puede mover aquí desde :estado',
        'soltar_aqui' => 'Soltar aquí',
    ],

    'tarjeta' => [
        'mover_a' => 'Mover a :columna',
        'abrir' => 'Abrir :codigo',
    ],

    'teclado' => [
        'ayuda' => 'Flechas para moverte entre tarjetas y columnas. Ctrl y flecha para mover la tarjeta enfocada. Intro para abrirla.',
        'movida' => ':codigo movida a :columna.',
        'no_se_puede_mover' => ':codigo no se puede mover a :columna.',
    ],

    'motivo' => [
        'titulo' => 'Hace falta un motivo',
        'espera' => 'Por qué queda en espera',
        'cancelacion' => 'Por qué se cancela',
        'ayuda_espera' => 'En espera significa que falta información o recursos para entenderla o ejecutarla. Escribe qué falta.',
        'ayuda_cancelacion' => 'Cancelar es definitivo: no se reabre. Escribe por qué se descarta.',
    ],

    'error_al_mover' => 'No se pudo mover :codigo. Vuelve a su columna.',
    'sin_permiso_para_mover' => 'Puedes ver el tablero, pero no mover tarjetas.',

    'lista' => [
        'titulo' => 'Lista',
        'descripcion' => 'Las tareas del proyecto de arriba abajo, por grupos.',
        'plegar' => 'Plegar :grupo',
        'desplegar' => 'Desplegar :grupo',
        'vacio' => 'Sin tareas en este grupo.',
    ],

    'agenda' => [
        'titulo' => 'Calendario',
        'descripcion' => 'Las tareas del proyecto por su fecha de fin.',
        'mes_anterior' => 'Mes anterior',
        'mes_siguiente' => 'Mes siguiente',
        'hoy' => 'Hoy',
        'sin_fecha' => 'Sin fecha de fin',
        'sin_fecha_ayuda' => 'Estas tareas no aparecen en ninguna casilla porque nadie les ha puesto fecha todavía.',
        'sin_fecha_recortadas' => 'Se muestran :mostradas de :total.',
        'dias' => [
            'lunes' => 'Lun',
            'martes' => 'Mar',
            'miercoles' => 'Mié',
            'jueves' => 'Jue',
            'viernes' => 'Vie',
            'sabado' => 'Sáb',
            'domingo' => 'Dom',
        ],
    ],

    'vistas' => [
        'tabla' => 'Tabla',
        'tablero' => 'Tablero',
        'lista' => 'Lista',
        'calendario' => 'Calendario',
        'cambiar' => 'Cambiar de vista',
    ],
];
