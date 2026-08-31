<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Proyecto;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * El dinero no se le muestra al cliente. `PagoPolicy` lo niega para cualquier
 * rol de proyecto `cliente`, así que esta ruta responde 403 aunque escriba el
 * id a mano en la barra de direcciones.
 */
class PagoController extends Controller
{
    public function index(Proyecto $proyecto): Response
    {
        Gate::authorize('viewAny', [Pago::class, $proyecto]);

        $pagos = Pago::query()
            ->whereIn('periodo_id', $proyecto->periodos()->select('id'))
            ->with('periodo:id,consecutivo')
            ->get(['id', 'periodo_id', 'fecha_pago', 'monto_usd', 'tasa_cambio', 'monto_cop']);

        return Inertia::render('recursos/Listado', [
            'titulo' => 'recursos.pagos',
            'columnas' => ['fecha_pago', 'monto_usd', 'monto_cop'],
            'filas' => $pagos,
        ]);
    }

    public function show(Pago $pago): Response
    {
        Gate::authorize('view', $pago);

        return Inertia::render('recursos/Detalle', [
            'titulo' => 'recursos.pago',
            'campos' => [
                ['clave' => 'fecha_pago', 'valor' => $pago->fecha_pago->toDateString()],
                ['clave' => 'monto_usd', 'valor' => $pago->monto_usd],
                ['clave' => 'tasa_cambio', 'valor' => $pago->tasa_cambio],
                ['clave' => 'monto_cop', 'valor' => $pago->monto_cop],
            ],
        ]);
    }
}
