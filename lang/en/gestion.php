<?php

declare(strict_types=1);

return [
    'usuarios' => [
        'titulo' => 'Users',
        'crear' => 'New user',
        'editar' => 'Edit user',
        'creado' => 'User created.',
        'actualizado' => 'User updated.',
        'activado' => 'User activated.',
        'desactivado' => 'User deactivated.',
        'activo' => 'Active',
        'inactivo' => 'Inactive',
        'activar' => 'Activate',
        'desactivar' => 'Deactivate',
        'rol_global' => 'Global role',
        'contrasena' => 'Password',
        'confirmar_contrasena' => 'Confirm password',
        'no_puedes_desactivarte' => 'You cannot deactivate your own account.',
        'sin_registro_publico' => 'There is no public sign-up: the administrator creates accounts.',
        'nota_desactivar' => 'A user is deactivated rather than deleted, so the record of who did what stays intact.',
    ],

    'equipos' => [
        'titulo' => 'Teams',
        'crear' => 'New team',
        'editar' => 'Edit team',
        'creado' => 'Team created.',
        'actualizado' => 'Team updated.',
        'eliminado' => 'Team deleted.',
        'miembros' => 'Members',
        'miembro_agregado' => 'Member added to the team.',
        'miembro_retirado' => 'Member removed from the team.',
        'cantidad_miembros' => 'Members',
        'nota_no_otorga_permisos' => 'A team grants no permissions by itself: it is a shortcut for assigning people to a project. The effective role always lives on the project.',
    ],
];
