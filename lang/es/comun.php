<?php

declare(strict_types=1);

return [
    'paginacion' => 'Paginación',
    'anterior' => 'Anterior',
    'siguiente' => 'Siguiente',
    'ver' => 'Ver',
    'confirmar' => 'Confirmar',
    'opcional' => 'Opcional',
    'guardar' => 'Guardar',
    'procesando' => 'Procesando…',
    'cancelar' => 'Cancelar',
    'crear' => 'Crear',
    'editar' => 'Editar',
    'eliminar' => 'Eliminar',
    'agregar' => 'Agregar',
    'quitar' => 'Quitar',
    'buscar' => 'Buscar',
    'volver' => 'Volver',
    'acciones' => 'Acciones',
    'mas_acciones' => 'Más acciones',
    'sin_resultados' => 'No hay nada que mostrar todavía.',
    'nombre' => 'Nombre',
    'correo' => 'Correo electrónico',
    'descripcion' => 'Descripción',
    'rol' => 'Rol',
    'estado' => 'Estado',

    // El menú lateral es un cajón por debajo de `md`: estos textos los lee el
    // lector de pantalla, y un texto que se lee es un texto visible.
    'menu_lateral' => 'Menú lateral',
    'menu_lateral_ayuda' => 'Navegación principal de la aplicación.',
    'alternar_menu' => 'Abrir o cerrar el menú',

    'tema' => [
        'titulo' => 'Tema',
        'claro' => 'Claro',
        'oscuro' => 'Oscuro',
        'sistema' => 'Sistema',
    ],

    /*
     * Textos del componente TablaDatos. Viven en `comun` y no en el archivo de
     * cada recurso porque el componente no conoce ningún dominio: los mismos
     * textos sirven al listado de clientes y al de tareas.
     */
    'tabla' => [
        'columnas' => 'Columnas',
        'columnas_ayuda' => 'Elige qué columnas se muestran.',
        'restablecer_columnas' => 'Restablecer columnas',
        'densidad' => 'Densidad',
        'densidad_comoda' => 'Cómoda',
        'densidad_compacta' => 'Compacta',
        'ordenar_por' => 'Ordenar por :columna',
        'orden_ascendente' => 'ascendente',
        'orden_descendente' => 'descendente',
        'orden_posicion' => 'posición :posicion del orden',
        'orden_multiple_ayuda' => 'Mantén Mayús al pulsar una cabecera para ordenar por varias columnas.',
        'limpiar_orden' => 'Limpiar orden',
        'filtrar_por' => 'Filtrar por :columna',
        'filtro_puesto' => 'con filtro puesto',
        'limpiar_filtro' => 'Quitar este filtro',
        'leyenda_de' => 'Qué significa cada valor de :columna',
        'filtros_activos' => ':total filtros activos',
        'filtros' => 'Filtros',
        'limpiar_filtros' => 'Limpiar filtros',
        'todos' => 'Todos',
        'seleccionar_fila' => 'Seleccionar esta fila',
        'seleccionar_pagina' => 'Seleccionar todas las filas de la página',
        'seleccionadas' => ':total seleccionadas',
        'limpiar_seleccion' => 'Quitar la selección',
        'cargando' => 'Cargando resultados',
        'por_pagina' => 'Por página',
        'pagina_de' => 'Página :actual de :total',
        'total_resultados' => ':total resultados',
        'sin_resultados_titulo' => 'Ningún resultado con estos filtros',
        'sin_resultados_cuerpo' => 'Hay datos, pero ninguno cumple lo que has pedido. Prueba a quitar algún filtro.',
        'error_titulo' => 'No se pudo cargar el listado',
        'reintentar' => 'Reintentar',
        'ayuda_teclado' => 'Usa las flechas para moverte entre filas, Intro para abrir la enfocada y Espacio para marcarla.',
        // Por debajo de `md` la tabla se apila en tarjetas: solo el titular y
        // las columnas prioritarias, y el resto detrás de esto.
        'opciones' => 'Opciones',
        'ver_mas' => 'Ver más',
    ],
];
