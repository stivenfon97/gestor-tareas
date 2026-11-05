<?php

declare(strict_types=1);

return [
    'titulo' => 'Clients',
    'crear' => 'New client',
    'editar' => 'Edit client',
    'ver' => 'View client',
    'creado' => 'Client created.',
    'actualizado' => 'Client updated.',
    'activado' => 'Client activated.',
    'desactivado' => 'Client deactivated. Its projects and history stay intact.',
    'activar' => 'Activate',
    'desactivar' => 'Deactivate',
    'activo' => 'Active',
    'inactivo' => 'Inactive',
    'sin_clientes' => 'No clients yet.',
    'sin_clientes_ayuda' => 'Clients you add will show up here, along with their projects.',
    'nota_desactivar' => 'Clients are never deleted, only deactivated. An inactive client no longer shows up when creating projects, but keeps its projects, periods and payments.',

    'contacto_nombre' => 'Contact person',
    'contacto_email' => 'Contact email',
    'contacto_telefono' => 'Contact phone',
    'notas' => 'Internal notes',
    'notas_ayuda' => 'Visible to the team only. The client never reaches this field.',

    'nombre_marcador' => 'Legal or trade name',
    'contacto_nombre_marcador' => 'First and last name',
    'contacto_email_marcador' => 'contact@company.com',
    'contacto_telefono_marcador' => '+57 300 123 4567',
    'notas_marcador' => 'Agreements, terms and details useful to the team',

    'buscar' => 'Search by name, contact or email…',
    'solo_activos' => 'Active only',
    'todos' => 'All',

    'proyectos' => 'Projects',
    'proyectos_activos' => 'Active projects',
    'proyectos_de_total' => ':activos active projects out of :total.',
    'sin_proyectos' => 'This client has no projects yet.',

    'resumen' => [
        'titulo' => 'Summary',
        'proyectos_activos' => 'Active projects',
        'tareas_abiertas' => 'Open tasks',
        'ultimo_periodo_cerrado' => 'Last closed period',
        'sin_periodos_cerrados' => 'None yet',
        'periodo' => 'Period :consecutivo · :proyecto',
    ],

    'formulario' => [
        'datos' => 'Client details',
        'datos_ayuda' => 'How the client appears in projects, reports and payments.',
        'contacto' => 'Contact',
        'contacto_ayuda' => 'The person you coordinate the work with.',
        'notas' => 'Internal notes',
        'notas_ayuda' => 'Agreements and terms the team should keep at hand.',
    ],
];
