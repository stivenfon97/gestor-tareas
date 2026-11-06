<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrigenTasaCambio;
use App\Http\Requests\PagoRequest;
use App\Models\HistorialCambio;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Services\BuscarEnColumnas;
use App\Services\ConsultarTrm;
use App\Services\EstadoDeTabla;
use App\Services\EstadoEnLaUrl;
use App\Services\PresentarCabeceraDeProyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * El dinero no se le muestra al cliente. `PagoPolicy` lo niega para cualquier
 * rol de proyecto `cliente`, así que estas rutas responden 403 aunque escriba
 * el id a mano en la barra de direcciones.
 */
class PagoController extends Controller
{
    public function index(Request $request, Proyecto $proyecto): Response
    {
        Gate::authorize('viewAny', [Pago::class, $proyecto]);

        $pagos = QueryBuilder::for(Pago::query()->whereIn('periodo_id', $proyecto->periodos()->select('id')))
            ->with('periodo:id,consecutivo')
            ->allowedFilters(
                AllowedFilter::exact('origen_tasa'),
                AllowedFilter::custom('buscar', new BuscarEnColumnas(['referencia', 'notas', 'periodo.consecutivo'])),
            )
            ->defaultSort('-fecha_pago')
            ->allowedSorts('fecha_pago', 'monto_usd', 'monto_cop', 'tasa_cambio')
            ->paginate(EstadoDeTabla::porPagina($request))
            ->withQueryString()
            ->through(fn (Pago $pago): array => [
                'id' => $pago->id,
                'periodo_consecutivo' => $pago->periodo->consecutivo,
                'fecha_pago' => $pago->fecha_pago->toDateString(),
                'monto_usd' => $pago->monto_usd,
                'tasa_cambio' => $pago->tasa_cambio,
                'monto_cop' => $pago->monto_cop,
                'origen_tasa' => $pago->origen_tasa->value,
                'referencia' => $pago->referencia,
            ]);

        return Inertia::render('pagos/Index', [
            'cabeceraDeProyecto' => PresentarCabeceraDeProyecto::para($proyecto, $request->user()),
            'proyecto' => $proyecto->only(['id', 'nombre', 'slug', 'prefijo_tarea']),
            'pagos' => $pagos,
            'filtros' => EstadoEnLaUrl::filtros($request),
            'orden' => EstadoDeTabla::orden($request, '-fecha_pago'),
            'columnasVisibles' => EstadoDeTabla::columnas($request),
            'origenes' => collect(OrigenTasaCambio::cases())->map(fn (OrigenTasaCambio $origen): array => [
                'valor' => $origen->value,
                'etiqueta' => 'enums.origen_tasa_cambio.'.$origen->value,
            ])->all(),
            'puede' => ['registrar' => Gate::allows('create', [Pago::class, $proyecto])],
        ]);
    }

    public function create(Request $request, Proyecto $proyecto): Response
    {
        Gate::authorize('create', [Pago::class, $proyecto]);

        $periodosSinPago = $proyecto->periodos()
            ->whereDoesntHave('pago')
            ->orderByDesc('consecutivo')
            ->get(['id', 'consecutivo', 'estado']);

        $preseleccionado = (int) $request->input('periodo_id', 0);

        return Inertia::render('pagos/Crear', [
            'proyecto' => $proyecto->only(['id', 'nombre', 'slug', 'prefijo_tarea']),
            'periodos' => $periodosSinPago->map(fn (Periodo $periodo): array => [
                'id' => $periodo->id,
                'consecutivo' => $periodo->consecutivo,
                'estado' => $periodo->estado->value,
            ]),
            'periodoPreseleccionado' => $periodosSinPago->contains('id', $preseleccionado) ? $preseleccionado : null,
            'origenesTasa' => self::origenesTasa(),
        ]);
    }

    public function store(PagoRequest $request, Proyecto $proyecto): RedirectResponse
    {
        Gate::authorize('create', [Pago::class, $proyecto]);

        $pago = Pago::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pagos.registrado')]);

        return to_route('periodos.show', $pago->periodo_id);
    }

    public function show(Pago $pago): Response
    {
        Gate::authorize('view', $pago);

        $pago->load('periodo:id,consecutivo,proyecto_id');

        return Inertia::render('pagos/Show', [
            // Solo esconde el botón: la cerradura es `HistorialCambioPolicy@verDe`.
            'puedeVerHistorial' => Gate::allows('verDe', [HistorialCambio::class, $pago]),
            'proyecto' => $pago->periodo->proyecto->only(['id', 'nombre', 'slug', 'prefijo_tarea']),
            'pago' => self::comoDetalle($pago),
            'puede' => ['editar' => request()->user()->can('update', $pago)],
        ]);
    }

    public function edit(Pago $pago): Response
    {
        Gate::authorize('update', $pago);

        $pago->load('periodo:id,consecutivo,proyecto_id');

        return Inertia::render('pagos/Editar', [
            'proyecto' => $pago->periodo->proyecto->only(['id', 'nombre', 'slug', 'prefijo_tarea']),
            'pago' => self::comoDetalle($pago),
            'origenesTasa' => self::origenesTasa(),
        ]);
    }

    public function update(PagoRequest $request, Pago $pago): RedirectResponse
    {
        Gate::authorize('update', $pago);

        $pago->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pagos.actualizado')]);

        return to_route('pagos.show', $pago);
    }

    /**
     * TRM a una fecha. JSON puro, no una página Inertia: es una consulta
     * corta que el formulario de pago dispara al elegir la fecha, no una
     * navegación.
     */
    public function trm(Request $request, ConsultarTrm $consultar): JsonResponse
    {
        try {
            $fecha = Carbon::createFromFormat('Y-m-d', (string) $request->query('fecha'));
        } catch (\Throwable) {
            $fecha = false;
        }

        if (! $fecha instanceof Carbon) {
            return response()->json(['tasa_cambio' => null]);
        }

        return response()->json(['tasa_cambio' => $consultar($fecha->startOfDay())]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function comoDetalle(Pago $pago): array
    {
        return [
            'id' => $pago->id,
            'periodo_id' => $pago->periodo_id,
            'periodo_consecutivo' => $pago->periodo->consecutivo,
            'fecha_pago' => $pago->fecha_pago->toDateString(),
            'monto_usd' => $pago->monto_usd,
            'monto_cop' => $pago->monto_cop,
            'tasa_cambio' => $pago->tasa_cambio,
            'origen_tasa' => $pago->origen_tasa->value,
            'referencia' => $pago->referencia,
            'notas' => $pago->notas,
        ];
    }

    /**
     * @return array<int, array{valor: string, etiqueta: string}>
     */
    private static function origenesTasa(): array
    {
        return array_map(static fn (OrigenTasaCambio $caso): array => [
            'valor' => $caso->value,
            'etiqueta' => __($caso->etiqueta()),
        ], OrigenTasaCambio::cases());
    }
}
