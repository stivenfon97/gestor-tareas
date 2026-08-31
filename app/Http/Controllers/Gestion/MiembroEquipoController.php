<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Alta y baja de miembros de un equipo.
 *
 * Sacar a alguien del equipo **no** lo saca de los proyectos donde el equipo lo
 * dio de alta: esas filas del pivote son independientes desde el momento en que
 * se crearon.
 */
class MiembroEquipoController extends Controller
{
    public function store(Request $request, Equipo $equipo): RedirectResponse
    {
        Gate::authorize('update', $equipo);

        $datos = $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $equipo->usuarios()->syncWithoutDetaching([$datos['usuario_id']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('gestion.equipos.miembro_agregado')]);

        return back();
    }

    public function destroy(Equipo $equipo, User $usuario): RedirectResponse
    {
        Gate::authorize('update', $equipo);

        $equipo->usuarios()->detach($usuario->getKey());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('gestion.equipos.miembro_retirado')]);

        return back();
    }
}
