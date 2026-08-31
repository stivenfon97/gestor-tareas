<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Periodo;
use App\Models\Proyecto;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * El cliente sí ve los periodos: son el marco de su reporte. Lo que no ve es el
 * pago que cuelga de ellos.
 */
class PeriodoController extends Controller
{
    public function index(Proyecto $proyecto): Response
    {
        Gate::authorize('viewAny', [Periodo::class, $proyecto]);

        return Inertia::render('recursos/Listado', [
            'titulo' => 'recursos.periodos',
            'columnas' => ['consecutivo', 'fecha_inicio', 'estado'],
            'filas' => $proyecto->periodos()->orderBy('consecutivo')
                ->get(['id', 'consecutivo', 'fecha_inicio', 'estado']),
        ]);
    }
}
