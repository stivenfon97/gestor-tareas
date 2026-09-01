<?php

declare(strict_types=1);

return [
    'failed' => 'Estas credenciales no coinciden con nuestros registros.',
    'password' => 'La contraseña es incorrecta.',
    'throttle' => 'Demasiados intentos de acceso. Inténtalo de nuevo en :seconds segundos.',
    'desactivado' => 'Esta cuenta está desactivada. Habla con el administrador.',

    /*
     * Pantallas de acceso. Están fuera del menú y son las que menos se revisan,
     * precisamente porque se pasa por ellas con prisa: eran las últimas que
     * quedaban con el texto en inglés incrustado en el componente.
     *
     * `titulo` y `descripcion` viajan como layout props y las resuelve
     * `AuthSimpleLayout`, igual que `nav.*` lo hace `NavMain`.
     */
    'acceso' => [
        'titulo' => 'Entra a tu cuenta',
        'descripcion' => 'Escribe tu correo y tu contraseña.',
        'correo' => 'Correo electrónico',
        'correo_marcador' => 'correo@ejemplo.com',
        'contrasena' => 'Contraseña',
        'contrasena_marcador' => 'Contraseña',
        'recordarme' => 'No cerrar la sesión',
        'olvidaste' => '¿Olvidaste tu contraseña?',
        'entrar' => 'Entrar',
        'con_passkey' => 'Entrar con passkey',
        'con_passkey_cargando' => 'Comprobando…',
        'con_passkey_separador' => 'O entra con tu contraseña',
    ],

    'recuperar' => [
        'titulo' => 'Recuperar la contraseña',
        'descripcion' => 'Escribe tu correo y te enviamos un enlace para cambiarla.',
        'enviar' => 'Enviar el enlace',
        'volver' => 'O vuelve a',
        'volver_enlace' => 'entrar',
    ],

    'restablecer' => [
        'titulo' => 'Nueva contraseña',
        'descripcion' => 'Escribe la contraseña nueva.',
        'contrasena' => 'Contraseña nueva',
        'confirmar' => 'Repite la contraseña',
        'guardar' => 'Guardar la contraseña',
    ],

    'verificar' => [
        'titulo' => 'Verifica tu correo',
        'descripcion' => 'Abre el enlace que acabamos de enviarte por correo.',
        'reenviado' => 'Te hemos enviado un enlace de verificación nuevo.',
        'reenviar' => 'Enviar otro enlace',
    ],

    'confirmar' => [
        'titulo' => 'Confirma tu contraseña',
        'descripcion' => 'Esta es una zona protegida. Confirma tu contraseña para continuar.',
        'contrasena' => 'Contraseña',
        'confirmar' => 'Confirmar',
        'con_passkey' => 'Confirmar con passkey',
        'con_passkey_cargando' => 'Confirmando…',
        'con_passkey_separador' => 'O confirma con tu contraseña',
    ],

    'salir' => 'Cerrar sesión',
];
