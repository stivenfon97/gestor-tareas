<?php

declare(strict_types=1);

return [
    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'desactivado' => 'This account is deactivated. Contact the administrator.',

    'acceso' => [
        'titulo' => 'Log in to your account',
        'descripcion' => 'Enter your email and password.',
        'correo' => 'Email address',
        'correo_marcador' => 'email@example.com',
        'contrasena' => 'Password',
        'contrasena_marcador' => 'Password',
        'recordarme' => 'Keep me signed in',
        'olvidaste' => 'Forgot your password?',
        'entrar' => 'Log in',
        'con_passkey' => 'Log in with a passkey',
        'con_passkey_cargando' => 'Checking…',
        'con_passkey_separador' => 'Or log in with your password',
    ],

    'recuperar' => [
        'titulo' => 'Recover your password',
        'descripcion' => 'Enter your email and we will send you a link to change it.',
        'enviar' => 'Send the link',
        'volver' => 'Or go back to',
        'volver_enlace' => 'log in',
    ],

    'restablecer' => [
        'titulo' => 'New password',
        'descripcion' => 'Enter your new password.',
        'contrasena' => 'New password',
        'confirmar' => 'Repeat the password',
        'guardar' => 'Save the password',
    ],

    'verificar' => [
        'titulo' => 'Verify your email',
        'descripcion' => 'Open the link we just emailed you.',
        'reenviado' => 'We have sent you a new verification link.',
        'reenviar' => 'Send another link',
    ],

    'confirmar' => [
        'titulo' => 'Confirm your password',
        'descripcion' => 'This is a protected area. Confirm your password to continue.',
        'contrasena' => 'Password',
        'confirmar' => 'Confirm',
        'con_passkey' => 'Confirm with a passkey',
        'con_passkey_cargando' => 'Confirming…',
        'con_passkey_separador' => 'Or confirm with your password',
    ],

    'salir' => 'Log out',
];
