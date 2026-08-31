<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RolProyecto;
use App\Http\Requests\Gestion\AsignarUsuarioAProyectoRequest;
use App\Models\Equipo;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Quién participa en un proyecto y con qué rol. Esta tabla —y solo esta— es la
 * fuente de verdad de los permisos dentro del proyecto.
 */
class ProyectoUsuarioController extends Controller
{
    public function index(Proyecto $proyecto): Response
    {
        Gate::authorize('gestionarMiembros', $proyecto);

        return Inertia::render('proyectos/Miembros', [
            'proyecto' => $proyecto->only(['id', 'nombre', 'slug']),
            'miembros' => $proyecto->usuarios()->orderBy('name')->get(['users.id', 'name', 'email'])
                ->map(fn (User $usuario): array => [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'email' => $usuario->email,
                    'rol' => $usuario->pivot->rol->value,
                ]),
            'candidatos' => User::query()
                ->where('activo', true)
                ->whereNotIn('id', $proyecto->usuarios()->pluck('users.id'))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'equipos' => Equipo::query()->orderBy('nombre')->get(['id', 'nombre']),
            'roles' => array_map(static fn (RolProyecto $rol): array => [
                'valor' => $rol->value,
                'etiqueta' => $rol->etiqueta(),
            ], RolProyecto::cases()),
        ]);
    }

    public function store(AsignarUsuarioAProyectoRequest $request, Proyecto $proyecto): RedirectResponse
    {
        Gate::authorize('gestionarMiembros', $proyecto);

        $datos = $request->validated();

        $proyecto->usuarios()->syncWithoutDetaching([
            $datos['usuario_id'] => ['rol' => $datos['rol']],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('proyectos.miembros.asignado')]);

        return back();
    }

    public function update(Request $request, Proyecto $proyecto, User $usuario): RedirectResponse
    {
        Gate::authorize('gestionarMiembros', $proyecto);

        $datos = $request->validate([
            'rol' => ['required', Rule::enum(RolProyecto::class)],
        ]);

        $proyecto->usuarios()->updateExistingPivot($usuario->getKey(), ['rol' => $datos['rol']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('proyectos.miembros.rol_actualizado')]);

        return back();
    }

    public function destroy(Proyecto $proyecto, User $usuario): RedirectResponse
    {
        Gate::authorize('gestionarMiembros', $proyecto);

        $proyecto->usuarios()->detach($usuario->getKey());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('proyectos.miembros.retirado')]);

        return back();
    }
}
