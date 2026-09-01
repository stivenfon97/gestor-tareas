<?php

declare(strict_types=1);

return [
    'titulo' => 'Settings',
    'descripcion' => 'Your profile and account settings.',

    'secciones' => [
        'perfil' => 'Profile',
        'seguridad' => 'Security',
        'apariencia' => 'Appearance',
    ],

    'perfil' => [
        'titulo' => 'Profile',
        'descripcion' => 'Change your name and email address.',
        'nombre' => 'Name',
        'nombre_marcador' => 'Full name',
        'correo' => 'Email address',
        'correo_marcador' => 'email@example.com',
        'sin_verificar' => 'Your email address is unverified.',
        'reenviar' => 'Send the verification email again.',
        'reenviado' => 'We have sent you a new verification link.',
    ],

    'seguridad' => [
        'titulo' => 'Change your password',
        'descripcion' => 'Use a long password that is hard to guess.',
        'actual' => 'Current password',
        'nueva' => 'New password',
        'confirmar' => 'Repeat the new password',
        'guardar' => 'Save the password',
    ],

    'apariencia' => [
        'titulo' => 'Appearance',
        'descripcion' => 'Choose whether the application looks light, dark or follows the system.',
    ],

    'passkeys' => [
        'titulo' => 'Passkeys',
        'descripcion' => 'Log in with your fingerprint, face or device PIN instead of a password.',
        'ninguna' => 'No passkeys yet',
        'ninguna_ayuda' => 'Add one to log in without typing your password.',
        'no_compatible' => 'This browser does not support passkeys.',
        'anadir' => 'Add a passkey',
        'nombre' => 'Passkey name',
        'nombre_marcador' => 'Work laptop, iPhone…',
        'nombre_ayuda' => 'A name that reminds you which device it is.',
        'creada' => 'Added :cuando',
        'ultimo_uso' => 'Last used :cuando',
        'quitar' => 'Remove',
        'quitar_titulo' => 'Remove the passkey',
        'quitar_confirmar' => 'Are you sure you want to remove ":nombre"? You will have to add it again from that device.',
    ],

    'eliminar' => [
        'titulo' => 'Delete account',
        'descripcion' => 'Deletes the account and everything that depends only on it.',
        'aviso' => 'Careful',
        'aviso_cuerpo' => 'This cannot be undone. Save anything you want to keep first.',
        'boton' => 'Delete account',
        'confirmar_titulo' => 'Are you sure you want to delete your account?',
        'confirmar_cuerpo' => 'Enter your password to confirm you want to delete the account permanently.',
        'contrasena' => 'Password',
    ],
];
