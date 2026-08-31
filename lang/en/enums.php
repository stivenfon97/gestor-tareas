<?php

declare(strict_types=1);

/**
 * Domain enum labels.
 *
 * English is prepared but not translated yet: the Spanish text stands in as a
 * provisional value so no key renders raw when the locale changes.
 */
return [
    'estado_tarea' => [
        'pendiente' => 'Pending',
        'en_proceso' => 'In progress',
        'en_espera' => 'On hold',
        'terminada' => 'Finished',
        'entregada' => 'Delivered',
        'cancelada' => 'Cancelled',
    ],

    'tipo_tarea' => [
        'desarrollo' => 'Development',
        'correccion' => 'Fix',
        'soporte' => 'Support',
        'mejora' => 'Improvement',
        'investigacion' => 'Research',
    ],

    'prioridad_tarea' => [
        'baja' => 'Low',
        'media' => 'Medium',
        'alta' => 'High',
        'critica' => 'Critical',
    ],

    'estado_proyecto' => [
        'activo' => 'Active',
        'pausado' => 'Paused',
        'finalizado' => 'Finished',
    ],

    'estado_periodo' => [
        'abierto' => 'Open',
        'cerrado' => 'Closed',
    ],

    'rol_proyecto' => [
        'lider' => 'Lead',
        'colaborador' => 'Contributor',
        'observador' => 'Observer',
        'cliente' => 'Client',
    ],

    'visibilidad' => [
        'interna' => 'Internal',
        'cliente' => 'Visible to the client',
    ],

    'tipo_reunion' => [
        'reunion' => 'Meeting',
        'llamada' => 'Call',
        'correo' => 'Email',
        'mensaje' => 'Message',
    ],

    'tipo_reporte' => [
        'completo' => 'Full',
        'cliente' => 'Client',
    ],

    'origen_tasa_cambio' => [
        'api' => 'API',
        'manual' => 'Manual',
    ],

    'rol_global' => [
        'administrador' => 'Administrator',
        'miembro' => 'Member',
        'invitado' => 'Guest',
    ],

    'permiso' => [
        'clientes_crear' => 'Create clients',
        'proyectos_editar' => 'Edit projects',
        'tareas_crear' => 'Create tasks',
        'tareas_editar' => 'Edit tasks',
        'tareas_mover' => 'Move tasks',
        'periodos_cerrar' => 'Close periods',
        'pagos_ver' => 'View payments',
        'pagos_registrar' => 'Record payments',
        'reuniones_crear' => 'Create meetings',
        'archivos_subir' => 'Upload files',
        'reportes_generar' => 'Generate reports',
        'usuarios_gestionar' => 'Manage users',
    ],
];
