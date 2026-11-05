<?php

declare(strict_types=1);

return [
    'registrar' => 'Registrar pago',
    'registrado' => 'Pago registrado.',
    'editar' => 'Editar pago',
    'actualizado' => 'Pago actualizado.',
    'periodo' => 'Periodo',
    'elegir_periodo' => 'Elige un periodo',
    'sin_periodos_disponibles' => 'Todos los periodos de este proyecto ya tienen su pago.',
    'sin_pago_periodo' => 'Este periodo todavía no tiene pago registrado.',
    'sin_pagos' => 'Este proyecto todavía no tiene pagos.',
    'sin_pagos_ayuda' => 'Los pagos se registran desde el periodo que cerraron, o cuando llegue el cobro.',
    'fecha_pago' => 'Fecha de pago',
    'fecha_pago_ayuda' => 'Al elegirla se precarga la TRM de ese día. El campo de tasa sigue siendo editable a mano.',
    'monto_usd' => 'Monto (USD)',
    'monto_usd_marcador' => 'Ej. 1500.00',
    'tasa_cambio' => 'Tasa de cambio (TRM)',
    'tasa_cambio_marcador' => 'Ej. 4100.50',
    'origen_tasa' => 'Origen de la tasa',
    'origen_tasa_ayuda' => 'Se marca sola según de dónde vino el valor; cámbiala si hace falta.',
    'referencia' => 'Referencia',
    'referencia_marcador' => 'Número de transferencia o de factura',
    'notas' => 'Notas',
    'notas_marcador' => 'Detalles del pago, si hacen falta',
    'usd' => 'USD',
    'cop' => 'COP',

    'formulario' => [
        'periodo' => 'Periodo y fecha',
        'periodo_ayuda' => 'Qué periodo se cobra y cuándo se recibió el pago.',
        'montos' => 'Montos',
        'montos_ayuda' => 'El monto en dólares y la TRM del día del pago; el valor en pesos se calcula.',
        'referencia' => 'Referencia',
        'referencia_ayuda' => 'Datos para encontrar el pago en el banco.',
    ],
];
