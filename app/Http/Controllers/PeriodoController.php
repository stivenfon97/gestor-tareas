<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AbrirPeriodo;
use App\Enums\EstadoTarea;
use App\Exceptions\PeriodoAbiertoExistente;
use App\Http\Requests\PeriodoRequest;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * El cliente sí ve los periodos: son el marco de su reporte. Lo que no ve es el
 * pago que cuelga de ellos (`PagoPolicy`).
 *
 * **`estado` no se toca aquí.** La única transición la hace
 * `CierrePeriodoController`, que es una operación en lote distinta de un
 * `update` de campos sueltos.
 */
class PeriodoController extends Controller
{
    public function index(Proyecto $proyecto): Response
    {
        Gate::authorize('viewAny', [Periodo::class, $proyecto]);

        $puedeVerPagos = Gate::allows('viewAny', [Pago::class, $proyecto]);

        $periodos = $proyecto->periodos()
            ->withCount('tareas')
            ->with('pago:id,periodo_id,monto_usd,monto_cop,fecha_pago')
            ->orderByDesc('consecutivo')
            ->get();

        $abierto = $proyecto->periodoAbierto()->first(['id', 'consecutivo']);

        return Inertia::render('periodos/Index', [
            'proyecto' => $proyecto->only(['id', 'nombre', 'slug', 'prefijo_tarea']),
            'periodos' => $periodos->map(fn (Periodo $periodo): array => self::comoFila($periodo, $puedeVerPagos)),
            // Separado en dos: sin permiso, el botón no se pinta; con permiso
            // pero un periodo ya abierto, se pinta deshabilitado y se explica
            // por qué en lugar de desaparecer sin más.
            'puede' => [
                'gestionar' => Gate::allows('create', [Periodo::class, $proyecto]),
                'verPagos' => $puedeVerPagos,
            ],
            'periodoAbierto' => $abierto === null ? null : [
                'id' => $abierto->id,
                'consecutivo' => $abierto->consecutivo,
            ],
        ]);
    }

    public function store(PeriodoRequest $request, Proyecto $proyecto, AbrirPeriodo $abrir): RedirectResponse
    {
        Gate::authorize('create', [Periodo::class, $proyecto]);

        try {
            $periodo = $abrir($proyecto, $request->datosApertura());
        } catch (PeriodoAbiertoExistente) {
            throw ValidationException::withMessages([
                'fecha_inicio' => __('periodos.validacion.ya_hay_abierto'),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('periodos.abierto', ['consecutivo' => (string) $periodo->consecutivo])]);

        return to_route('periodos.show', $periodo);
    }

    public function show(Periodo $periodo): Response
    {
        Gate::authorize('view', $periodo);

        $periodo->load(['proyecto:id,nombre,slug,prefijo_tarea', 'pago']);

        $tareas = $periodo->tareas()
            ->with('modulo:id,nombre')
            ->orderBy('orden')
            ->get(['id', 'codigo', 'nombre', 'estado', 'modulo_id', 'periodo_origen_id']);

        $arrastradas = $periodo->tareasArrastradas()
            ->with('periodo:id,consecutivo')
            ->get(['id', 'codigo', 'nombre', 'estado', 'periodo_id']);

        $usuario = request()->user();
        $puedeVerPagos = $usuario->can('viewAny', [Pago::class, $periodo->proyecto]);

        return Inertia::render('periodos/Show', [
            'proyecto' => $periodo->proyecto->only(['id', 'nombre', 'slug', 'prefijo_tarea']),
            'periodo' => [
                'id' => $periodo->id,
                'consecutivo' => $periodo->consecutivo,
                'fecha_inicio' => $periodo->fecha_inicio->toDateString(),
                'fecha_fin' => $periodo->fecha_fin?->toDateString(),
                'estado' => $periodo->estado->value,
                'notas' => $periodo->notas,
                'vencido' => $periodo->estaVencido(),
                'duracion_en_dias' => $periodo->duracionEnDias(),
            ],
            'tareasPorEstado' => collect(EstadoTarea::cases())->map(fn (EstadoTarea $estado): array => [
                'estado' => $estado->value,
                'etiqueta' => __($estado->etiqueta()),
                'tareas' => $tareas->where('estado', $estado)
                    ->map(fn ($tarea): array => [
                        'id' => $tarea->id,
                        'codigo' => $tarea->codigo,
                        'nombre' => $tarea->nombre,
                        'modulo' => $tarea->modulo?->nombre,
                        'arrastrada' => $tarea->periodo_origen_id !== null,
                    ])
                    ->values(),
            ])->values(),
            'arrastradasHaciaOtro' => $arrastradas->map(fn ($tarea): array => [
                'id' => $tarea->id,
                'codigo' => $tarea->codigo,
                'nombre' => $tarea->nombre,
                'estado' => $tarea->estado->value,
                'periodo_destino' => $tarea->periodo?->consecutivo,
            ]),
            // Ni el dato ni la ausencia de dato son gratis: si `pago` viajara
            // siempre que existe y solo `puede.verPagos` dijera que no se
            // muestre, el cliente lo tendría en la respuesta JSON igual,
            // vetado solo por la interfaz que elige no pintarlo.
            'pago' => (! $puedeVerPagos || $periodo->pago === null) ? null : [
                'id' => $periodo->pago->id,
                'fecha_pago' => $periodo->pago->fecha_pago->toDateString(),
                'monto_usd' => $periodo->pago->monto_usd,
                'monto_cop' => $periodo->pago->monto_cop,
                'tasa_cambio' => $periodo->pago->tasa_cambio,
                'origen_tasa' => $periodo->pago->origen_tasa->value,
                'referencia' => $periodo->pago->referencia,
            ],
            'puede' => [
                'editar' => $usuario->can('update', $periodo),
                'cerrar' => $usuario->can('cerrar', $periodo),
                'verPagos' => $puedeVerPagos,
                'registrarPago' => $puedeVerPagos && $periodo->pago === null && $usuario->can('create', [Pago::class, $periodo->proyecto]),
            ],
        ]);
    }

    public function update(PeriodoRequest $request, Periodo $periodo): RedirectResponse
    {
        Gate::authorize('update', $periodo);

        $periodo->update($request->safe()->only(['notas', 'fecha_inicio', 'fecha_fin']));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('periodos.actualizado')]);

        return to_route('periodos.show', $periodo);
    }

    /**
     * @return array<string, mixed>
     */
    private static function comoFila(Periodo $periodo, bool $puedeVerPagos): array
    {
        return [
            'id' => $periodo->id,
            'consecutivo' => $periodo->consecutivo,
            'fecha_inicio' => $periodo->fecha_inicio->toDateString(),
            'fecha_fin' => $periodo->fecha_fin?->toDateString(),
            'estado' => $periodo->estado->value,
            'vencido' => $periodo->estaVencido(),
            'tareas_count' => $periodo->tareas_count,
            // El dinero no viaja para quien no puede verlo, ni siquiera
            // vetado solo por la interfaz: `PagoPolicy` niega la ruta propia
            // del pago, y esta fila no puede ser la puerta de atrás.
            'pago' => (! $puedeVerPagos || $periodo->pago === null) ? null : [
                'monto_usd' => $periodo->pago->monto_usd,
                'monto_cop' => $periodo->pago->monto_cop,
                'fecha_pago' => $periodo->pago->fecha_pago->toDateString(),
            ],
        ];
    }
}
