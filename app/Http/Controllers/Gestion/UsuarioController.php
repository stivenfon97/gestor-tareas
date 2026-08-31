<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gestion;

use App\Enums\RolGlobal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\UsuarioStoreRequest;
use App\Http\Requests\Gestion\UsuarioUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Alta y edición de usuarios por el administrador.
 *
 * No hay registro público: en una herramienta con datos de clientes, el
 * registro abierto solo sirve para que cualquiera que alcance el puerto se cree
 * una cuenta.
 */
class UsuarioController extends Controller
{
    public function index(): Response
    {
        $usuarios = QueryBuilder::for(User::class)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('email'),
                AllowedFilter::exact('activo'))
            ->defaultSort('name')
            ->allowedSorts('name', 'email', 'created_at')
            ->with('roles:id,name')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $usuario): array => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'activo' => $usuario->activo,
                'rol_global' => $usuario->rolGlobal()?->value,
            ]);

        return Inertia::render('gestion/usuarios/Index', [
            'usuarios' => $usuarios,
            'roles' => $this->rolesDisponibles(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('gestion/usuarios/Crear', [
            'roles' => $this->rolesDisponibles(),
        ]);
    }

    public function store(UsuarioStoreRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $usuario = User::query()->create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => Hash::make($datos['password']),
            'activo' => $datos['activo'] ?? true,
        ]);

        $usuario->syncRoles([$datos['rol_global']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('gestion.usuarios.creado')]);

        return to_route('usuarios.index');
    }

    public function edit(User $usuario): Response
    {
        return Inertia::render('gestion/usuarios/Editar', [
            'usuario' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'activo' => $usuario->activo,
                'rol_global' => $usuario->rolGlobal()?->value,
            ],
            'roles' => $this->rolesDisponibles(),
        ]);
    }

    public function update(UsuarioUpdateRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->validated();

        $usuario->update([
            'name' => $datos['name'],
            'email' => $datos['email'],
        ]);

        $usuario->syncRoles([$datos['rol_global']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('gestion.usuarios.actualizado')]);

        return to_route('usuarios.index');
    }

    /**
     * @return array<int, array{valor: string, etiqueta: string}>
     */
    private function rolesDisponibles(): array
    {
        return array_map(static fn (RolGlobal $rol): array => [
            'valor' => $rol->value,
            'etiqueta' => $rol->etiqueta(),
        ], RolGlobal::cases());
    }
}
