<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Activa o desactiva un usuario. **Nunca lo borra**: `tareas.creado_por` y
 * `reuniones.creado_por` son `restrict`, y el borrado rompería el historial de
 * quién hizo qué.
 */
class EstadoUsuarioController extends Controller
{
    public function update(Request $request, User $usuario): RedirectResponse
    {
        // Desactivarse a uno mismo deja la gestión sin quien la use.
        abort_if($usuario->is($request->user()), 403, __('gestion.usuarios.no_puedes_desactivarte'));

        $usuario->update(['activo' => ! $usuario->activo]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $usuario->activo
                ? __('gestion.usuarios.activado')
                : __('gestion.usuarios.desactivado'),
        ]);

        return back();
    }
}
