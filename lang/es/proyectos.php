<?php

declare(strict_types=1);

return [
    'titulo' => 'Proyectos',
    'cliente' => 'Cliente',
    'prefijo' => 'Prefijo',
    'mi_rol' => 'Tu rol en el proyecto',

    'crear' => 'Nuevo proyecto',
    'editar' => 'Editar proyecto',
    'creado' => 'Proyecto creado.',
    'actualizado' => 'Proyecto actualizado.',
    'eliminado' => 'Proyecto eliminado.',
    'eliminar' => 'Eliminar proyecto',
    'eliminar_ayuda' => 'Solo para deshacer una creación equivocada. Para archivar un proyecto que sí trabajó, cámbialo a «finalizado».',
    'eliminar_confirmar' => '¿Eliminar «:nombre»? El borrado es suave y conserva su historial.',

    'slug' => 'Identificador en la URL',
    'slug_ayuda' => 'Se genera desde el nombre si lo dejas vacío. Solo minúsculas, números y guiones.',
    'prefijo_ayuda' => 'De dos a diez letras o números. Alimenta el código de cada tarea (:ejemplo).',
    'prefijo_inmutable_aviso' => 'No se puede cambiar después de crear la primera tarea.',
    'prefijo_bloqueado' => 'Este proyecto ya emitió códigos de tarea, así que el prefijo queda fijo.',
    'descripcion' => 'Descripción',
    'buscar' => 'Buscar por nombre',
    'todos_los_estados' => 'Todos los estados',
    'tareas_abiertas' => 'Tareas abiertas',
    'sin_proyectos_ayuda' => 'Los proyectos en los que participes aparecerán aquí.',
    'fecha_creacion' => 'Alta',
    'sin_proyectos' => 'Todavía no participas en ningún proyecto.',

    'validacion' => [
        'cliente_inactivo' => 'Ese cliente no existe o está desactivado. Un cliente desactivado no admite proyectos nuevos.',
        'slug_formato' => 'El identificador solo admite minúsculas, números y guiones.',
        'prefijo_formato' => 'El prefijo solo admite letras y números, entre dos y diez caracteres.',
        'prefijo_inmutable' => 'El prefijo no se puede cambiar: este proyecto ya emitió códigos de tarea que viajaron al cliente.',
    ],

    'resumen' => [
        'titulo' => 'Resumen',
        'tareas_por_estado' => 'Tareas por estado',
        'sin_tareas' => 'Todavía no hay tareas.',
        'periodo_abierto' => 'Periodo abierto',
        'sin_periodo_abierto' => 'No hay ningún periodo abierto.',
        'periodo_numero' => 'Periodo :consecutivo',
        'dias_transcurridos' => ':dias días transcurridos',
        'vencido' => 'Pasado de su fecha de fin',
        'vencido_ayuda' => 'No es un error: los periodos se cierran a mano.',
        'ultima_reunion' => 'Última reunión',
        'sin_reuniones' => 'Todavía no hay reuniones registradas.',
        'modulos' => 'Módulos',
        'miembros' => 'Personas',
    ],

    'pendiente_de_fase' => 'Esta sección llega en la fase :fase.',

    'miembros' => [
        'titulo' => 'Miembros del proyecto',
        'asignar' => 'Asignar persona',
        'asignado' => 'Persona asignada al proyecto.',
        'rol_actualizado' => 'Rol actualizado.',
        'retirado' => 'Persona retirada del proyecto.',
        'asignar_equipo' => 'Asignar un equipo completo',
        'equipo_asignado' => 'Equipo asignado: :agregados agregados, :omitidos omitidos.',
        'omitidos_explicacion' => 'Quienes ya participaban conservan su rol: una asignación masiva no degrada a nadie.',
    ],

    'secciones' => [
        'resumen' => 'Resumen',
        'modulos' => 'Módulos',
        'equipo' => 'Equipo',
        'tareas' => 'Tareas',
        'periodos' => 'Periodos',
        'pagos' => 'Pagos',
        'reuniones' => 'Bitácora',
        'archivos' => 'Archivos',
        'reportes' => 'Reportes',
    ],
];
