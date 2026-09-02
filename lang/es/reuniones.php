<?php

declare(strict_types=1);

return [
    'bitacora' => 'Bitácora',
    'crear' => 'Nueva reunión',
    'editar' => 'Editar reunión',
    'eliminar_confirmar' => '¿Eliminar esta reunión? También se borran sus adjuntos.',
    'creada' => 'Reunión creada.',
    'actualizada' => 'Reunión actualizada.',
    'eliminada' => 'Reunión eliminada.',
    'vacio' => 'Todavía no hay reuniones en la bitácora.',

    'campos' => [
        'fecha' => 'Fecha y hora',
        'tipo' => 'Tipo',
        'titulo' => 'Título',
        'contenido' => 'Contenido',
        'acuerdos' => 'Acuerdos',
        'visibilidad' => 'Visibilidad',
    ],

    'filtros' => [
        'tipo' => 'Tipo',
        'desde' => 'Desde',
        'hasta' => 'Hasta',
        'limpiar' => 'Limpiar filtros',
    ],

    'asistentes' => [
        'titulo' => 'Asistentes',
        'nombre' => 'Nombre',
        'organizacion' => 'Organización',
        'usuario' => 'Usuario del sistema (opcional)',
        'usuario_ninguno' => 'No es usuario del sistema',
        'anadir' => 'Añadir asistente',
        'quitar' => 'Quitar',
        'ninguno_ayuda' => 'Añade al menos un asistente.',
    ],

    'tareas' => [
        'titulo' => 'Tareas vinculadas',
        'vacio' => 'Ninguna tarea vinculada todavía.',
        'vincular' => 'Vincular tareas existentes',
        'vincular_confirmar' => 'Vincular',
        'vinculadas' => 'Tareas vinculadas.',
        'desvincular_confirmar' => '¿Quitar el vínculo con esta tarea? La tarea no se borra.',
        'desvinculada' => 'Tarea desvinculada.',
        'disponibles_vacio' => 'No quedan tareas del proyecto por vincular.',
        'crear_titulo' => 'Crear tareas desde esta reunión',
        'crear_ayuda' => 'Pega los acuerdos y conviértelos en tareas sin cambiar de pantalla. Quedan vinculadas a esta reunión.',
    ],

    'editor' => [
        'negrita' => 'Negrita',
        'cursiva' => 'Cursiva',
        'tachado' => 'Tachado',
        'resaltado' => 'Resaltar',
        'encabezado_1' => 'Encabezado 1',
        'encabezado_2' => 'Encabezado 2',
        'encabezado_3' => 'Encabezado 3',
        'lista_desordenada' => 'Lista con viñetas',
        'lista_ordenada' => 'Lista numerada',
        'cita' => 'Cita',
        'codigo' => 'Código',
        'enlace' => 'Enlace',
        'enlace_quitar' => 'Quitar enlace',
        'enlace_url' => 'URL del enlace',
        'tabla' => 'Insertar tabla',
    ],

    'validacion' => [
        'documento_invalido' => 'El contenido de la reunión no es válido.',
    ],
];
