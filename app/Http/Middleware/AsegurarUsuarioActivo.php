<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as RespuestaBase;

/**
 * Cierra la sesión de un usuario desactivado.
 *
 * `Fortify::authenticateUsing` ya impide el login por contraseña, pero no cubre
 * las otras puertas: una passkey, un "recuérdame" o una sesión que seguía
 * abierta cuando lo desactivaron. Desactivar a alguien tiene que echarlo del
 * sistema, no solo impedirle volver a entrar.
 */
class AsegurarUsuarioActivo
{
    public function handle(Request $request, Closure $next): RespuestaBase
    {
        $usuario = $request->user();

        if ($usuario !== null && ! $usuario->activo) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('auth.desactivado'),
            ]);
        }

        return $next($request);
    }
}
