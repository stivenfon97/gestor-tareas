<?php

declare(strict_types=1);

/*
 * Ajustes de la cuenta: perfil, seguridad, passkeys y apariencia.
 *
 * Grupo propio y no dentro de `gestion`, que es la administración de usuarios
 * y equipos de **otras** personas. Esto es la cuenta de quien está mirando.
 */
return [
    'titulo' => 'Ajustes',
    'descripcion' => 'Tu perfil y la configuración de tu cuenta.',

    'secciones' => [
        'perfil' => 'Perfil',
        'seguridad' => 'Seguridad',
        'apariencia' => 'Apariencia',
    ],

    'perfil' => [
        'titulo' => 'Perfil',
        'descripcion' => 'Cambia tu nombre y tu correo electrónico.',
        'nombre' => 'Nombre',
        'nombre_marcador' => 'Nombre y apellidos',
        'correo' => 'Correo electrónico',
        'correo_marcador' => 'correo@ejemplo.com',
        'sin_verificar' => 'Tu correo electrónico está sin verificar.',
        'reenviar' => 'Enviar otra vez el correo de verificación.',
        'reenviado' => 'Te hemos enviado un enlace de verificación nuevo.',
    ],

    'seguridad' => [
        'titulo' => 'Cambiar la contraseña',
        'descripcion' => 'Usa una contraseña larga y difícil de adivinar.',
        'actual' => 'Contraseña actual',
        'nueva' => 'Contraseña nueva',
        'confirmar' => 'Repite la contraseña nueva',
        'guardar' => 'Guardar la contraseña',
    ],

    'apariencia' => [
        'titulo' => 'Apariencia',
        'descripcion' => 'Elige si la aplicación se ve en claro, en oscuro o como el sistema.',
    ],

    /*
     * Passkeys. El término no se traduce: es el nombre del estándar y es lo
     * que dice el propio navegador cuando pide una.
     */
    'passkeys' => [
        'titulo' => 'Passkeys',
        'descripcion' => 'Entra con la huella, la cara o el PIN del dispositivo, sin escribir la contraseña.',
        'ninguna' => 'Todavía no hay ninguna passkey',
        'ninguna_ayuda' => 'Añade una para entrar sin escribir la contraseña.',
        'no_compatible' => 'Este navegador no admite passkeys.',
        'anadir' => 'Añadir una passkey',
        'nombre' => 'Nombre de la passkey',
        'nombre_marcador' => 'Portátil del trabajo, iPhone…',
        'nombre_ayuda' => 'Un nombre que te recuerde qué dispositivo es.',
        'creada' => 'Añadida :cuando',
        'ultimo_uso' => 'Último uso :cuando',
        'quitar' => 'Quitar',
        'quitar_titulo' => 'Quitar la passkey',
        'quitar_confirmar' => '¿Seguro que quieres quitar «:nombre»? Tendrás que volver a añadirla desde ese dispositivo.',
    ],

    'eliminar' => [
        'titulo' => 'Eliminar la cuenta',
        'descripcion' => 'Se borra la cuenta y todo lo que dependa solo de ella.',
        'aviso' => 'Cuidado',
        'aviso_cuerpo' => 'Esto no se puede deshacer. Guarda antes lo que quieras conservar.',
        'boton' => 'Eliminar la cuenta',
        'confirmar_titulo' => '¿Seguro que quieres eliminar tu cuenta?',
        'confirmar_cuerpo' => 'Escribe tu contraseña para confirmar que quieres eliminar la cuenta para siempre.',
        'contrasena' => 'Contraseña',
    ],
];
