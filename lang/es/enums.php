<?php

declare(strict_types=1);

/**
 * Etiquetas de los enums de dominio.
 *
 * Las claves las devuelve `etiqueta()` de cada enum. Toda clave añadida aquí
 * debe existir también en `lang/en/enums.php`.
 */
return [
    'estado_tarea' => [
        'pendiente' => 'Pendiente',
        'en_proceso' => 'En proceso',
        'en_espera' => 'En espera',
        'terminada' => 'Terminada',
        'entregada' => 'Entregada',
        'cancelada' => 'Cancelada',
    ],

    'tipo_tarea' => [
        'desarrollo' => 'Desarrollo',
        'correccion' => 'Corrección',
        'soporte' => 'Soporte',
        'mejora' => 'Mejora',
        'investigacion' => 'Investigación',
    ],

    'prioridad_tarea' => [
        'baja' => 'Baja',
        'media' => 'Media',
        'alta' => 'Alta',
        'critica' => 'Crítica',
    ],

    'estado_proyecto' => [
        'activo' => 'Activo',
        'pausado' => 'Pausado',
        'finalizado' => 'Finalizado',
    ],

    'estado_periodo' => [
        'abierto' => 'Abierto',
        'cerrado' => 'Cerrado',
    ],

    'rol_proyecto' => [
        'lider' => 'Líder',
        'colaborador' => 'Colaborador',
        'observador' => 'Observador',
        'cliente' => 'Cliente',
    ],

    'visibilidad' => [
        'interna' => 'Interna',
        'cliente' => 'Visible para el cliente',
    ],

    'tipo_reunion' => [
        'reunion' => 'Reunión',
        'llamada' => 'Llamada',
        'correo' => 'Correo',
        'mensaje' => 'Mensaje',
    ],

    'tipo_reporte' => [
        'completo' => 'Completo',
        'cliente' => 'De cliente',
    ],

    'origen_tasa_cambio' => [
        'api' => 'API',
        'manual' => 'Manual',
    ],

    'rol_global' => [
        'administrador' => 'Administrador',
        'miembro' => 'Miembro',
        'invitado' => 'Invitado',
    ],

    'permiso' => [
        'clientes_crear' => 'Crear clientes',
        'proyectos_editar' => 'Editar proyectos',
        'tareas_crear' => 'Crear tareas',
        'tareas_editar' => 'Editar tareas',
        'tareas_mover' => 'Mover tareas',
        'periodos_cerrar' => 'Cerrar periodos',
        'pagos_ver' => 'Ver pagos',
        'pagos_registrar' => 'Registrar pagos',
        'reuniones_crear' => 'Crear reuniones',
        'archivos_subir' => 'Subir archivos',
        'reportes_generar' => 'Generar reportes',
        'usuarios_gestionar' => 'Gestionar usuarios',
    ],
];
