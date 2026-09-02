<?php

declare(strict_types=1);

return [
    'titulo' => 'Projects',
    'cliente' => 'Client',
    'prefijo' => 'Prefix',
    'mi_rol' => 'Your role on this project',

    'crear' => 'New project',
    'editar' => 'Edit project',
    'creado' => 'Project created.',
    'actualizado' => 'Project updated.',
    'eliminado' => 'Project deleted.',
    'eliminar' => 'Delete project',
    'eliminar_confirmar_titulo' => 'Delete this project',
    'eliminar_confirmar_cuerpo' => 'Its modules, tasks, periods, payments and meeting log go with it. There is no way back.',
    'eliminar_confirmar_campo' => 'Type “:nombre” to confirm',
    'eliminar_ayuda' => 'Only to undo a mistaken creation. To archive a project that did real work, set it to “finished”.',
    'eliminar_confirmar' => 'Delete “:nombre”? The delete is soft and keeps its history.',

    'slug' => 'URL identifier',
    'slug_ayuda' => 'Generated from the name if left blank. Lowercase letters, digits and hyphens only.',
    'prefijo_ayuda' => 'Two to ten letters or digits. It feeds every task code (:ejemplo).',
    'prefijo_inmutable_aviso' => 'Cannot be changed once the first task exists.',
    'prefijo_bloqueado' => 'This project already issued task codes, so the prefix is now fixed.',
    'descripcion' => 'Description',
    'buscar' => 'Search by name',
    'todos_los_estados' => 'All statuses',
    'descripcion_listado' => 'Each project has its own modules, tasks and periods.',
    'ver_tareas' => 'View the project tasks',
    'tareas_abiertas' => 'Open tasks',
    'sin_proyectos_ayuda' => 'Projects you take part in will show up here.',
    'fecha_creacion' => 'Created',
    'sin_proyectos' => 'You do not take part in any project yet.',

    'validacion' => [
        'cliente_inactivo' => 'That client does not exist or is deactivated. A deactivated client takes no new projects.',
        'slug_formato' => 'The identifier only accepts lowercase letters, digits and hyphens.',
        'prefijo_formato' => 'The prefix only accepts letters and digits, between two and ten characters.',
        'prefijo_inmutable' => 'The prefix cannot be changed: this project already issued task codes that reached the client.',
    ],

    'resumen' => [
        'titulo' => 'Summary',
        'tareas_por_estado' => 'Tasks by status',
        'sin_tareas' => 'No tasks yet.',
        'periodo_abierto' => 'Open period',
        'sin_periodo_abierto' => 'There is no open period.',
        'periodo_numero' => 'Period :consecutivo',
        'dias_transcurridos' => ':dias days elapsed',
        'vencido' => 'Past its end date',
        'vencido_ayuda' => 'Not an error: periods are closed by hand.',
        'ultima_reunion' => 'Last meeting',
        'sin_reuniones' => 'No meetings recorded yet.',
        'modulos' => 'Modules',
        'miembros' => 'People',
        'periodos_cerrados' => ':total closed periods.',
        'total_pagado' => 'USD :monto collected in total.',
    ],

    'pendiente_de_fase' => 'This section arrives in phase :fase.',

    'miembros' => [
        'titulo' => 'Project members',
        'asignar' => 'Assign person',
        'asignado' => 'Person assigned to the project.',
        'rol_actualizado' => 'Role updated.',
        'retirado' => 'Person removed from the project.',
        'asignar_equipo' => 'Assign a whole team',
        'equipo_asignado' => 'Team assigned: :agregados added, :omitidos skipped.',
        'omitidos_explicacion' => 'People who already took part keep their role: a bulk assignment never demotes anyone.',
    ],

    'secciones' => [
        'resumen' => 'Summary',
        'modulos' => 'Modules',
        'equipo' => 'Team',
        'tareas' => 'Tasks',
        'periodos' => 'Periods',
        'pagos' => 'Payments',
        'reuniones' => 'Log',
        'archivos' => 'Files',
        'reportes' => 'Reports',
    ],
];
