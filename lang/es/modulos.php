<?php

declare(strict_types=1);

return [
    'titulo' => 'Módulos',
    'descripcion' => 'La división funcional del proyecto. Un módulo puede contener submódulos.',

    'crear_raiz' => 'Nuevo módulo',
    'crear_hijo' => 'Añadir submódulo',
    'nombre_nuevo' => 'Nombre del módulo',
    'creado' => 'Módulo creado.',
    'actualizado' => 'Módulo actualizado.',
    'reordenado' => 'Árbol reordenado.',
    'eliminado' => 'Módulo eliminado: :submodulos submódulos borrados, :tareas tareas quedaron sin módulo.',

    'renombrar' => 'Renombrar',
    'expandir' => 'Expandir rama',
    'colapsar' => 'Colapsar rama',
    'arrastrar' => 'Arrastrar para reordenar o cambiar de padre',
    'sin_modulos' => 'Este proyecto todavía no tiene módulos.',

    'tareas_contador' => ':total tareas',
    'tareas_contador_una' => '1 tarea',
    'tareas_contador_cero' => 'Sin tareas',
    'tareas_incluye_hijos' => 'Incluye las tareas de los submódulos.',

    'eliminar' => [
        'titulo' => 'Eliminar «:nombre»',
        'sin_impacto' => 'Este módulo no tiene submódulos ni tareas.',
        'submodulos' => 'Se borrarán también :total submódulos.',
        'tareas' => ':total tareas se quedarán sin módulo. No se borran.',
        'confirmar' => 'Eliminar módulo',
    ],

    'validacion' => [
        'ciclo' => 'Un módulo no puede colgar de uno de sus propios submódulos.',
        'padre_de_otro_proyecto' => 'El módulo padre no pertenece a este proyecto.',
        'modulo_de_otro_proyecto' => 'Alguno de los módulos no pertenece a este proyecto.',
    ],
];
