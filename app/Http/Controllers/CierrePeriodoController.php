<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CerrarPeriodo;
use App\Enums\EstadoPeriodo;
use App\Enums\EstadoTarea;
use App\Enums\OrigenTasaCambio;
use App\Exceptions\PeriodoYaCerrado;
use App\Http\Requests\CierrePeriodoRequest;
use App\Models\Pago;
use App\Models\Periodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pantalla propia del cierre de un periodo. No una acción del tablero: el
 * cierre es una operación en lote —traslado, apertura del siguiente, pago
 * opcional— y el tablero mueve una tarjeta a la vez.
 */
class CierrePeriodoController extends Controller
{
    public function create(Periodo $periodo): Response
    {
        Gate::authorize('cerrar', $periodo);

        abort_if($periodo->estado !== EstadoPeriodo::Abierto, 404);

        $pendientes = $periodo->tareas()
            ->whereNotIn('estado', [EstadoTarea::Entregada, EstadoTarea::Cancelada])
            ->with('modulo:id,nombre')
            ->orderBy('orden')
            ->get(['id', 'codigo', 'nombre', 'estado', 'modulo_id']);

        $conteos = $periodo->tareas()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return Inertia::render('periodos/Cierre', [
            'proyecto' => $periodo->proyecto->only(['id', 'nombre', 'slug', 'prefijo_tarea']),
            'periodo' => [
                'id' => $periodo->id,
                'consecutivo' => $periodo->consecutivo,
                'fecha_inicio' => $periodo->fecha_inicio->toDateString(),
            ],
            'resumen' => [
                'total' => $periodo->tareas()->count(),
                'por_estado' => collect(EstadoTarea::cases())->mapWithKeys(
                    fn (EstadoTarea $estado): array => [$estado->value => $conteos->get($estado->value, 0)],
                ),
            ],
            'pendientes' => $pendientes->map(fn ($tarea): array => [
                'id' => $tarea->id,
                'codigo' => $tarea->codigo,
                'nombre' => $tarea->nombre,
                'estado' => $tarea->estado->value,
                'modulo' => $tarea->modulo?->nombre,
            ]),
            'fechaInicioSiguientePropuesta' => Carbon::now()->addDay()->toDateString(),
            'puedeRegistrarPago' => Gate::allows('create', [Pago::class, $periodo->proyecto]),
            'origenesTasa' => array_map(static fn (OrigenTasaCambio $caso): array => [
                'valor' => $caso->value,
                'etiqueta' => __($caso->etiqueta()),
            ], OrigenTasaCambio::cases()),
        ]);
    }

    public function store(CierrePeriodoRequest $request, Periodo $periodo, CerrarPeriodo $cerrar): RedirectResponse
    {
        Gate::authorize('cerrar', $periodo);

        try {
            $resultado = $cerrar(
                $periodo,
                $request->idsTrasladar(),
                (string) $request->validated('fecha_inicio_siguiente'),
                $request->datosPago(),
            );
        } catch (PeriodoYaCerrado) {
            throw ValidationException::withMessages([
                'fecha_inicio_siguiente' => __('periodos.validacion.ya_cerrado'),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('periodos.cerrado', [
                'consecutivo' => (string) $resultado['cerrado']->consecutivo,
                'trasladadas' => (string) $resultado['trasladadas'],
            ]),
        ]);

        return to_route('periodos.show', $resultado['siguiente']);
    }
}
