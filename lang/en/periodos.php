<?php

declare(strict_types=1);

return [
    'abrir' => 'Open period',
    'abrir_ayuda' => 'The sequence number is assigned automatically. The end date is filled in when it closes.',
    'abierto' => 'Period :consecutivo opened.',
    'actualizado' => 'Period updated.',
    'ya_hay_abierto_ayuda' => 'There is already an open period (:consecutivo). Close it before opening another.',
    'cerrar' => 'Close period',
    'cerrar_titulo' => 'Close period :consecutivo',
    'cerrado' => 'Period :consecutivo closed. :trasladadas tasks carried over.',
    'vencido' => 'Overdue',
    'en_curso' => 'ongoing',
    'notas' => 'Notes',
    'sin_notas' => 'No notes.',
    'fecha_fin' => 'End date',
    'fecha_fin_prevista' => 'Expected end date',
    'fecha_fin_prevista_ayuda' => 'Indicative while the period stays open: closing it always replaces this with the real date.',
    'desglose' => 'Tasks by status',
    'arrastradas_hacia_otro' => 'Carried over to the next period',
    'sin_periodos' => 'This project has no periods yet.',
    'sin_periodos_ayuda' => 'Open the first one to start splitting the work into billable blocks.',

    'validacion' => [
        'ya_hay_abierto' => 'This project already has an open period.',
        'ya_cerrado' => 'This period is already closed.',
        'no_editable_cerrado' => 'A closed period does not accept date changes.',
    ],

    'cierre' => [
        'resumen' => 'What is being closed',
        'resumen_ayuda' => ':total tasks in total, :pendientes unfinished.',
        'traslado' => 'Carrying over what is unfinished',
        'traslado_ayuda' => 'Checked by default: carried over to the next period keeping their status. Uncheck the ones that stay here.',
        'sin_pendientes' => 'There are no unfinished tasks in this period.',
        'siguiente' => 'Opening the next period',
        'pago' => 'Register the payment now',
        'pago_ayuda' => 'Optional: closing without a payment is valid, it can be added later.',
        'confirmar_titulo' => 'Confirm closing period :consecutivo',
        'confirmar_traslado' => ':trasladan tasks will be carried over to the next period. :quedan stay in this one.',
        'confirmar_siguiente' => 'A new period will open with start date :fecha.',
        'confirmar_pago' => 'A payment of USD :monto will be registered.',
        'confirmar_sin_pago' => 'No payment is registered: it can be added later.',
        'confirmar' => 'Confirm closing',
    ],
];
