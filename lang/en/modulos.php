<?php

declare(strict_types=1);

return [
    'titulo' => 'Modules',
    'descripcion' => 'The functional breakdown of the project. A module can contain submodules.',

    'crear_raiz' => 'New module',
    'crear_hijo' => 'Add submodule',
    'nombre_nuevo' => 'Module name',
    'creado' => 'Module created.',
    'actualizado' => 'Module updated.',
    'reordenado' => 'Tree reordered.',
    'eliminado' => 'Module deleted: :submodulos submodules removed, :tareas tasks left without a module.',

    'renombrar' => 'Rename',
    'expandir' => 'Expand branch',
    'colapsar' => 'Collapse branch',
    'arrastrar' => 'Drag to reorder or change parent',
    'sin_modulos' => 'This project has no modules yet.',

    'tareas_contador' => ':total tasks',
    'tareas_contador_una' => '1 task',
    'tareas_contador_cero' => 'No tasks',
    'tareas_incluye_hijos' => 'Includes tasks from submodules.',

    'eliminar' => [
        'titulo' => 'Delete “:nombre”',
        'sin_impacto' => 'This module has no submodules and no tasks.',
        'submodulos' => ':total submodules will be deleted as well.',
        'tareas' => ':total tasks will be left without a module. They are not deleted.',
        'confirmar' => 'Delete module',
    ],

    'validacion' => [
        'ciclo' => 'A module cannot hang from one of its own submodules.',
        'padre_de_otro_proyecto' => 'The parent module does not belong to this project.',
        'modulo_de_otro_proyecto' => 'One of the modules does not belong to this project.',
    ],
];
