<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ModuloRequest;
use App\Models\Modulo;
use App\Models\Proyecto;
use App\Services\ArbolDeModulos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Árbol de módulos de un proyecto: crear, renombrar, mover y borrar.
 *
 * El orden y el cambio de padre no pasan por aquí sino por
 * `ReordenarModulosController`, que persiste el nivel completo de una vez.
 */
class ModuloController extends Controller
{
    public function index(Proyecto $proyecto): Response
    {
        Gate::authorize('viewAny', [Modulo::class, $proyecto]);

        return Inertia::render('proyectos/Modulos', [
            'proyecto' => $proyecto->only(['id', 'nombre', 'slug']),
            'arbol' => ArbolDeModulos::para($proyecto),
            'puede' => ['gestionar' => Gate::allows('create', [Modulo::class, $proyecto])],
        ]);
    }

    public function store(ModuloRequest $request, Proyecto $proyecto): RedirectResponse
    {
        Gate::authorize('create', [Modulo::class, $proyecto]);

        $datos = $request->validated();

        $proyecto->modulos()->create($datos + [
            // Al final de sus hermanos: crear en medio del árbol descoloca lo
            // que el usuario acaba de ordenar a mano.
            'orden' => (int) $proyecto->modulos()
                ->where('parent_id', $datos['parent_id'] ?? null)
                ->max('orden') + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('modulos.creado')]);

        return back();
    }

    /**
     * Renombrado en línea. Solo nombre y descripción: el padre y el orden son
     * cosa de la reordenación.
     */
    public function update(ModuloRequest $request, Modulo $modulo): RedirectResponse
    {
        Gate::authorize('update', $modulo);

        $modulo->update($request->safe()->only(['nombre', 'descripcion']));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('modulos.actualizado')]);

        return back();
    }

    /**
     * Los submódulos se van en cascada (`parent_id` es `cascadeOnDelete`); las
     * tareas **no**, quedan con `modulo_id` nulo (`nullOnDelete`).
     */
    public function destroy(Modulo $modulo): RedirectResponse
    {
        Gate::authorize('delete', $modulo);

        $impacto = ArbolDeModulos::impactoDeBorrar($modulo);

        $modulo->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('modulos.eliminado', $impacto),
        ]);

        return back();
    }
}
