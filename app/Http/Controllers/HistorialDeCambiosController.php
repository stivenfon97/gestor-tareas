<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EventoHistorial;
use App\Models\HistorialCambio;
use App\Models\User;
use App\Services\BuscarEnHistorial;
use App\Services\EstadoDeTabla;
use App\Services\EstadoEnLaUrl;
use App\Services\PresentarHistorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Pantalla global del historial de cambios: todo lo que cambió en el sistema,
 * de todos los proyectos. Solo con `historial.ver` (hoy, el administrador).
 */
class HistorialDeCambiosController extends Controller
{
    public function index(Request $request, PresentarHistorial $presentar): Response
    {
        Gate::authorize('viewAny', HistorialCambio::class);

        $historial = QueryBuilder::for(HistorialCambio::class)
            ->allowedFilters(
                AllowedFilter::exact('evento'),
                AllowedFilter::exact('tipo', 'registro_type'),
                AllowedFilter::exact('usuario', 'usuario_id'),
                AllowedFilter::custom('buscar', new BuscarEnHistorial),
            )
            // `id` desempata: varias filas del mismo guardado comparten segundo.
            ->defaultSort('-created_at', '-id')
            ->allowedSorts('created_at')
            ->with('usuario:id,name')
            ->paginate(EstadoDeTabla::porPagina($request))
            ->withQueryString();

        // La presentación va en bloque (una consulta por tipo de referencia
        // para toda la página), no fila a fila como haría `through()` solo.
        $presentadas = collect($presentar->presentar($historial->getCollection(), conRegistro: true))->keyBy('id');
        $historial = $historial->through(fn (HistorialCambio $fila): array => $presentadas[$fila->id]);

        return Inertia::render('historial/Index', [
            'historial' => $historial,
            'filtros' => EstadoEnLaUrl::filtros($request),
            'orden' => EstadoDeTabla::orden($request, '-created_at'),
            'columnasVisibles' => EstadoDeTabla::columnas($request),
            'tipos' => array_keys(HistorialCambio::TIPOS),
            'eventos' => array_map(static fn (EventoHistorial $evento): string => $evento->value, EventoHistorial::cases()),
            'usuarios' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
