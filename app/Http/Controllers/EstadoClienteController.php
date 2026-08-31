<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Activa o desactiva un cliente. **Nunca lo borra.**
 *
 * `proyectos.cliente_id` es `restrict` y de esos proyectos cuelgan periodos y
 * pagos: borrar el cliente sería borrar el historial que justifica cobros ya
 * hechos. Un cliente inactivo desaparece del desplegable al crear proyectos,
 * pero todo lo suyo sigue intacto y consultable.
 */
class EstadoClienteController extends Controller
{
    public function update(Cliente $cliente): RedirectResponse
    {
        Gate::authorize('update', $cliente);

        $cliente->update(['activo' => ! $cliente->activo]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $cliente->activo
                ? __('clientes.activado')
                : __('clientes.desactivado'),
        ]);

        return back();
    }
}
