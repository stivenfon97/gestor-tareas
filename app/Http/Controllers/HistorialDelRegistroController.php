<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HistorialCambio;
use App\Services\PresentarHistorial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * El historial de un registro concreto, en JSON, para el panel lateral de su
 * ficha. Va aparte de la página y no como prop de Inertia: el panel se abre a
 * demanda y pagina con «cargar más», y así ninguna ficha paga la consulta si
 * nadie lo abre.
 */
class HistorialDelRegistroController extends Controller
{
    private const POR_PAGINA = 20;

    public function __invoke(string $tipo, int $id, PresentarHistorial $presentar): JsonResponse
    {
        $clase = HistorialCambio::claseDe($tipo) ?? abort(404);

        /** @var Builder<Model> $consulta */
        $consulta = $clase::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($clase), true)) {
            $consulta->withoutGlobalScope(SoftDeletingScope::class);
        }

        $registro = $consulta->findOrFail($id);

        Gate::authorize('verDe', [HistorialCambio::class, $registro]);

        $pagina = HistorialCambio::query()
            ->de($registro)
            ->with('usuario:id,name')
            ->orderByDesc('id')
            ->cursorPaginate(self::POR_PAGINA);

        return response()->json([
            'entradas' => $presentar->presentar($pagina->items()),
            'siguiente' => $pagina->nextCursor()?->encode(),
        ]);
    }
}
