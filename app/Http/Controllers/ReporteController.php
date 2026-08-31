<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Reporte;
use Illuminate\Support\Facades\Gate;

/**
 * La emisión de los dos Excel es de la fase 11. Lo que existe hoy es la guarda:
 * quien tiene rol de proyecto `cliente` recibe 403 antes de llegar a nada.
 */
class ReporteController extends Controller
{
    public function store(Proyecto $proyecto): never
    {
        Gate::authorize('create', [Reporte::class, $proyecto]);

        // 501 y no un stub silencioso: quien está autorizado debe saber que la
        // generación todavía no existe, no recibir un archivo vacío.
        abort(501, __('reportes.pendiente_fase_11'));
    }
}
