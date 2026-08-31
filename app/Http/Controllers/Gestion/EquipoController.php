<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\EquipoRequest;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Los equipos agrupan personas para asignarlas de golpe a un proyecto.
 *
 * **El equipo no otorga permisos por sí mismo.** El rol efectivo se resuelve
 * siempre desde `proyecto_usuario`.
 */
class EquipoController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Equipo::class);

        $equipos = QueryBuilder::for(Equipo::class)
            ->allowedFilters(AllowedFilter::partial('nombre'))
            ->defaultSort('nombre')
            ->allowedSorts('nombre')
            ->withCount('usuarios')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('gestion/equipos/Index', ['equipos' => $equipos]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Equipo::class);

        return Inertia::render('gestion/equipos/Crear');
    }

    public function store(EquipoRequest $request): RedirectResponse
    {
        Gate::authorize('create', Equipo::class);

        Equipo::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('gestion.equipos.creado')]);

        return to_route('equipos.index');
    }

    public function edit(Equipo $equipo): Response
    {
        Gate::authorize('update', $equipo);

        return Inertia::render('gestion/equipos/Editar', [
            'equipo' => $equipo->only(['id', 'nombre', 'descripcion']),
            'miembros' => $equipo->usuarios()->orderBy('name')->get(['users.id', 'name', 'email']),
            'candidatos' => User::query()
                ->whereNotIn('id', $equipo->usuarios()->pluck('users.id'))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function update(EquipoRequest $request, Equipo $equipo): RedirectResponse
    {
        Gate::authorize('update', $equipo);

        $equipo->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('gestion.equipos.actualizado')]);

        return to_route('equipos.index');
    }

    public function destroy(Equipo $equipo): RedirectResponse
    {
        Gate::authorize('delete', $equipo);

        $equipo->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('gestion.equipos.eliminado')]);

        return to_route('equipos.index');
    }
}
