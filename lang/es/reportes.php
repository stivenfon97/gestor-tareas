<?php

declare(strict_types=1);

return [
    'titulo' => 'Reportes',
    'generado' => 'Reporte generado.',
    'si' => 'Sí',
    'no' => 'No',

    'sin_periodos' => 'Todavía no hay periodos',
    'sin_periodos_ayuda' => 'Un reporte se genera sobre un periodo del proyecto. Abre uno primero.',

    'generar' => [
        'titulo' => 'Generar un reporte',
        'periodo' => 'Periodo',
        'periodo_numero' => 'Periodo :consecutivo',
        'preview' => ':total tareas entrarían en el reporte.',
        'cliente_ayuda' => 'Tarea, estado, descripción y módulo. Sin datos financieros: listo para enviar.',
        'completo_ayuda' => 'Incluye los pagos del periodo. Uso interno: nunca se lo mandes al cliente.',
        'boton' => 'Generar y descargar',
    ],

    'historial' => [
        'fecha' => 'Generado el',
        'tipo' => 'Tipo',
        'periodo' => 'Periodo',
        'generado_por' => 'Generado por',
        'total_tareas' => 'Tareas',
        'hash' => 'Hash',
        'duplicado' => 'Igual a otro ya emitido',
        'descargar_historico' => 'Descargar histórico',
        'vacio' => 'Todavía no se ha generado ningún reporte',
        'vacio_ayuda' => 'El primero que generes queda aquí, con su registro permanente.',
    ],

    // Hoja «Resumen» -solo en el reporte completo- del cliente, proyecto,
    // periodo, conteo por estado y fecha de emisión.
    'resumen' => [
        'campo' => 'Campo',
        'valor' => 'Valor',
        'cliente' => 'Cliente',
        'proyecto' => 'Proyecto',
        'periodo' => 'Periodo',
        'fecha_inicio' => 'Fecha de inicio',
        'fecha_fin' => 'Fecha de fin',
        'total_tareas' => 'Total de tareas',
        'arrastradas' => 'Tareas arrastradas',
        'fecha_generacion' => 'Fecha de generación',
    ],

    // Hoja «Tareas» del reporte completo: todo lo trabajado en el periodo.
    'tareas' => [
        'codigo' => 'Código',
        'tipo' => 'Tipo',
        'modulo' => 'Módulo',
        'tarea' => 'Tarea',
        'estado' => 'Estado',
        'prioridad' => 'Prioridad',
        'descripcion' => 'Descripción',
        'fecha_inicio' => 'Fecha inicio',
        'fecha_fin' => 'Fecha fin',
        'arrastrada' => 'Arrastrada',
        'notas' => 'Notas',
    ],

    // Hoja «Notas» del reporte completo: detalle fecha a fecha, con autor.
    'notas' => [
        'codigo' => 'Código de tarea',
        'fecha' => 'Fecha',
        'autor' => 'Autor',
        'nota' => 'Nota',
    ],

    // Hoja «Pagos» del reporte completo. Nunca en el de cliente.
    'pagos' => [
        'fecha' => 'Fecha',
        'monto_usd' => 'Monto USD',
        'tasa_cambio' => 'Tasa (TRM)',
        'monto_cop' => 'Monto COP',
        'origen_tasa' => 'Origen de la tasa',
        'referencia' => 'Referencia',
    ],

    // Única hoja del reporte de cliente. Exactamente estas cuatro columnas.
    'cliente' => [
        'tarea' => 'Tarea',
        'estado' => 'Estado',
        'descripcion' => 'Descripción',
        'modulo' => 'Módulo',
    ],

    'hojas' => [
        'resumen' => 'Resumen',
        'tareas' => 'Tareas',
        'notas' => 'Notas',
        'pagos' => 'Pagos',
    ],
];
