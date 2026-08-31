<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ReordenarModulos;
use App\Exceptions\CicloEnElArbol;
use App\Http\Requests\ReordenarModulosRequest;
use App\Models\Modulo;
use App\Models\Proyecto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Persiste una reordenación del árbol de módulos.
 *
 * **Una petición por operación de arrastre.** El cliente manda los niveles
 * afectados enteros —el de origen y el de destino, con el orden completo de sus
 * hijos— y aquí entran en una sola transacción.
 */
class ReordenarModulosController extends Controller
{
    public function update(
        ReordenarModulosRequest $request,
        Proyecto $proyecto,
        ReordenarModulos $reordenar,
    ): RedirectResponse {
        Gate::authorize('create', [Modulo::class, $proyecto]);

        try {
            $reordenar($proyecto, $request->niveles());
        } catch (CicloEnElArbol $excepcion) {
            // 422 y no 500: es una entrada inválida del usuario, no un fallo.
            throw ValidationException::withMessages([
                'niveles' => __('modulos.validacion.ciclo'),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('modulos.reordenado')]);

        return back();
    }
}
