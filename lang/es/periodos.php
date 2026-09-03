<?php

declare(strict_types=1);

return [
    'abrir' => 'Abrir periodo',
    'abrir_ayuda' => 'El consecutivo se asigna solo. La fecha de fin se llena al cerrar.',
    'abierto' => 'Periodo :consecutivo abierto.',
    'actualizado' => 'Periodo actualizado.',
    'ya_hay_abierto_ayuda' => 'Ya hay un periodo abierto (:consecutivo). Ciérralo antes de abrir otro.',
    'cerrar' => 'Cerrar periodo',
    'cerrar_titulo' => 'Cerrar periodo :consecutivo',
    'cerrado' => 'Periodo :consecutivo cerrado. :trasladadas tareas trasladadas.',
    'vencido' => 'Vencido',
    'en_curso' => 'en curso',
    'notas' => 'Notas',
    'sin_notas' => 'Sin notas.',
    'fecha_fin' => 'Fecha de fin',
    'fecha_fin_prevista' => 'Fecha de fin prevista',
    'fecha_fin_prevista_ayuda' => 'Orientativa mientras el periodo sigue abierto: el cierre siempre la sustituye por la fecha real.',
    'desglose' => 'Tareas por estado',
    'arrastradas_hacia_otro' => 'Trasladadas al siguiente periodo',
    'sin_periodos' => 'Este proyecto todavía no tiene periodos.',
    'sin_periodos_ayuda' => 'Abre el primero para empezar a repartir el trabajo en bloques facturables.',

    'validacion' => [
        'ya_hay_abierto' => 'Este proyecto ya tiene un periodo abierto.',
        'ya_cerrado' => 'Este periodo ya está cerrado.',
        'no_editable_cerrado' => 'Un periodo cerrado no admite cambios de fecha.',
    ],

    'cierre' => [
        'resumen' => 'Lo que se cierra',
        'resumen_ayuda' => ':total tareas en total, :pendientes sin entregar.',
        'traslado' => 'Traslado de pendientes',
        'traslado_ayuda' => 'Marcadas por defecto: se trasladan al periodo siguiente conservando su estado. Desmarca las que se quedan aquí.',
        'sin_pendientes' => 'No hay tareas sin entregar en este periodo.',
        'siguiente' => 'Apertura del siguiente periodo',
        'pago' => 'Registrar el pago ahora',
        'pago_ayuda' => 'Opcional: cerrar sin pago es válido, se puede añadir después.',
        'confirmar_titulo' => 'Confirmar el cierre del periodo :consecutivo',
        'confirmar_traslado' => 'Se trasladarán :trasladan tareas al periodo siguiente. :quedan se quedan en este.',
        'confirmar_siguiente' => 'Se abrirá un periodo nuevo con fecha de inicio :fecha.',
        'confirmar_pago' => 'Se registrará un pago de USD :monto.',
        'confirmar_sin_pago' => 'No se registra pago: se puede añadir después.',
        'confirmar' => 'Confirmar cierre',
    ],
];
