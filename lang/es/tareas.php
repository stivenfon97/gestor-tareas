<?php

declare(strict_types=1);

return [
    'titulo' => 'Tareas',
    'crear' => 'Nueva tarea',
    'editar' => 'Editar tarea',
    'creada' => 'Tarea :codigo creada.',
    'actualizada' => 'Tarea actualizada.',
    'eliminada' => 'Tarea eliminada.',
    'eliminar' => 'Eliminar tarea',
    'eliminar_confirmar' => '¿Eliminar «:codigo»? El borrado es suave: el código no se reutiliza y el historial se conserva.',
    'sin_tareas' => 'Todavía no hay tareas que mostrar.',
    'sin_tareas_ayuda' => 'Las tareas que crees en este proyecto aparecerán aquí.',

    'codigo' => 'Código',
    'modulo' => 'Módulo',
    'sin_modulo' => 'Sin módulo',
    'periodo' => 'Periodo',
    'sin_periodo' => 'Sin periodo',
    'tipo' => 'Tipo',
    'prioridad' => 'Prioridad',
    'descripcion' => 'Descripción',
    'creada_por' => 'Creada por',
    'fecha_inicio' => 'Inicio',
    'fecha_fin' => 'Fin',
    'arrastrada' => 'Arrastrada',
    'arrastrada_de' => 'Arrastrada del periodo :consecutivo',
    'tipo_no_cambia' => 'El tipo se fija al crear y no cambia después.',
    'sin_periodo_abierto' => 'Este proyecto no tiene ningún periodo abierto. La tarea se creará sin periodo y habrá que asignárselo cuando se abra uno.',

    'origen' => 'Corrige a',
    'origen_ayuda' => 'Solo para tareas de tipo corrección o soporte. Vincula esta tarea con la entrega que la originó.',
    'correcciones' => 'Correcciones que generó',
    'correcciones_conteo' => ':total correcciones',
    'sin_correcciones' => 'Esta tarea no ha generado ninguna corrección.',

    'estado' => [
        'titulo' => 'Estado',
        'cambiar' => 'Cambiar estado',
        'sin_transiciones' => 'Este estado es terminal: no admite ninguna transición.',
        'mover_a' => 'Mover a :estado',
        'motivo_espera' => 'Motivo de la espera',
        'motivo_cancelacion' => 'Motivo de la cancelación',
        'motivo_espera_ayuda' => 'Falta de información o de recursos. Una tarea bloqueada sin motivo es una tarea que nadie sabe cómo desbloquear.',
        'motivo_cancelacion_ayuda' => 'Queda en el historial aunque la tarea salga de este estado.',
    ],
    'estado_cambiado' => 'Estado actualizado.',

    'historial' => [
        'titulo' => 'Historial de estados',
        'vacio' => 'Todavía no ha cambiado de estado.',
        'inicial' => 'Creada',
        'por' => 'por :usuario',
    ],

    'notas' => [
        'titulo' => 'Notas',
        'creada' => 'Nota añadida.',
        'agregar' => 'Añadir nota',
        'contenido' => 'Nota',
        'vacio' => 'Todavía no hay notas.',
        'visibilidad' => 'Visibilidad',
        'interna_ayuda' => 'Las notas nacen internas. Marcarla como visible para el cliente es una decisión explícita.',
    ],

    'filtros' => [
        'texto' => 'Buscar en nombre, código y descripción',
        'todos' => 'Todos',
        'modulo_incluye_hijos' => 'Incluye las tareas de los submódulos.',
        'solo_arrastradas' => 'Solo arrastradas',
        'limpiar' => 'Limpiar filtros',
    ],

    'orden' => [
        'titulo' => 'Ordenar por',
        'codigo' => 'Código',
        'nombre' => 'Nombre',
        'estado' => 'Estado',
        'prioridad' => 'Prioridad',
        'fecha_fin' => 'Fecha de fin',
        'updated_at' => 'Última actualización',
    ],

    'rapida' => [
        'titulo' => 'Captura rápida',
        'descripcion' => 'Pega varias líneas de golpe. Se crea una tarea por línea.',
        'formato' => 'Formato: nombre | estado | descripción | módulo. Solo el nombre es obligatorio.',
        'ejemplo' => "migracion de interfaz de clientes | Pendiente | se debe migrar la interfaz al nuevo diseño | Clientes\nrevisar el cálculo del IVA\najustar el pie del PDF | En Proceso | falta el NIT",
        'texto' => 'Líneas a capturar',
        'previsualizar' => 'Previsualizar',
        'previsualizacion' => 'Esto es lo que se va a crear',
        'previsualizacion_ayuda' => 'Revisa y corrige antes de guardar. Nada se ha creado todavía.',
        'confirmar' => 'Crear :total tareas',
        'creadas' => ':total tareas creadas.',
        'quitar_fila' => 'Descartar esta línea',
        'linea' => 'Línea :numero',
        'modulo_nuevo' => 'Crear módulo «:nombre»',
        'sin_filas' => 'Pega algunas líneas y pulsa previsualizar.',

        'avisos' => [
            'sin_nombre' => 'Esta línea no tiene nombre y el nombre es obligatorio.',
            'estado_desconocido' => 'No se reconoció el estado; se usará «Pendiente».',
            'modulo_nuevo' => 'Ese módulo no existe en el proyecto. Se creará al guardar.',
            'requiere_motivo' => 'Este estado exige un motivo.',
        ],
    ],

    'validacion' => [
        'modulo_de_otro_proyecto' => 'El módulo no pertenece a este proyecto.',
        'periodo_de_otro_proyecto' => 'El periodo no pertenece a este proyecto.',
        'origen_de_otro_proyecto' => 'La tarea de origen no pertenece a este proyecto.',
        'origen_solo_correccion_o_soporte' => 'Solo una tarea de tipo corrección o soporte puede tener tarea de origen.',
        'origen_a_si_misma' => 'Una tarea no puede ser su propio origen.',
        'origen_cadena_circular' => 'Ese vínculo crearía una cadena circular de orígenes.',
        'motivo_espera_obligatorio' => 'Pasar a «En espera» exige explicar qué falta.',
        'motivo_cancelacion_obligatorio' => 'Cancelar exige explicar por qué.',
        'transicion_no_permitida' => 'No se puede pasar de «:desde» a «:hacia».',
    ],

    'lote' => [
        'copiar_codigos' => 'Copiar códigos',
        'codigos_copiados' => ':total códigos copiados al portapapeles.',
    ],
];
