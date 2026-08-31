<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Reunion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReunionController extends Controller
{
    public function index(Request $request, Proyecto $proyecto): Response
    {
        Gate::authorize('viewAny', [Reunion::class, $proyecto]);

        return Inertia::render('recursos/Listado', [
            'titulo' => 'recursos.reuniones',
            'columnas' => ['fecha', 'titulo', 'tipo'],
            // Sin este scope el listado devolvería los títulos de las reuniones
            // internas aunque el detalle diera 403, y el título ya es
            // información filtrada.
            'filas' => Reunion::query()
                ->visiblesPara($request->user())
                ->where('proyecto_id', $proyecto->getKey())
                ->latest('fecha')
                ->get(['id', 'fecha', 'titulo', 'tipo']),
        ]);
    }

    public function show(Reunion $reunion): Response
    {
        Gate::authorize('view', $reunion);

        return Inertia::render('recursos/Detalle', [
            'titulo' => 'recursos.reunion',
            'campos' => [
                ['clave' => 'titulo', 'valor' => $reunion->titulo],
                ['clave' => 'fecha', 'valor' => $reunion->fecha->toDateTimeString()],
                ['clave' => 'tipo', 'valor' => $reunion->tipo->value],
                ['clave' => 'visibilidad', 'valor' => $reunion->visibilidad->value],
            ],
        ]);
    }
}
