<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerarReporte;
use App\Enums\EstadoTarea;
use App\Enums\TipoReporte;
use App\Exports\HojaClienteExport;
use App\Exports\ReporteCompletoExport;
use App\Http\Requests\ReporteRequest;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Reporte;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Emisión de los dos Excel de un periodo y su registro de trazabilidad.
 *
 * `store()` **solo escribe** la fila -es la parte auditable, el snapshot
 * queda fijado ahí-; `descargar()` **solo lee** del snapshot, nunca de la
 * base de datos en vivo. Por eso sirve por igual a las dos descargas que pide
 * la fase: la «actual», que es simplemente leer el snapshot recién escrito, y
 * la «histórica», que es leer el de cualquier fila anterior. Es el mismo
 * camino, no dos generaciones distintas.
 */
class ReporteController extends Controller
{
    public function index(Proyecto $proyecto): Response
    {
        Gate::authorize('viewAny', [Reporte::class, $proyecto]);

        $periodos = $proyecto->periodos()
            ->orderByDesc('consecutivo')
            ->get(['id', 'consecutivo', 'fecha_inicio', 'fecha_fin']);

        // `DB::table()`, no `Tarea::query()`: agrupar sobre modelos ya
        // hidratados aplicaría el cast de `estado` a `EstadoTarea` y
        // `pluck('total', 'estado')` no puede usar un enum como clave de
        // array. La tabla base no lleva el scope de borrado suave, así que se
        // repite aquí a mano.
        $conteosPorPeriodo = DB::table('tareas')
            ->whereIn('periodo_id', $periodos->pluck('id'))
            ->whereNull('deleted_at')
            ->selectRaw('periodo_id, estado, count(*) as total')
            ->groupBy('periodo_id', 'estado')
            ->get()
            ->groupBy('periodo_id');

        $reportes = $proyecto->reportes()
            ->with(['generadoPor:id,name', 'periodo:id,consecutivo'])
            ->orderByDesc('generado_en')
            ->get();

        return Inertia::render('reportes/Index', [
            'proyecto' => $proyecto->only(['id', 'nombre', 'slug', 'prefijo_tarea']),
            'periodos' => $periodos->map(function (Periodo $periodo) use ($conteosPorPeriodo): array {
                $conteos = $conteosPorPeriodo->get($periodo->id, collect())->pluck('total', 'estado');

                return [
                    'id' => $periodo->id,
                    'consecutivo' => $periodo->consecutivo,
                    'fecha_inicio' => $periodo->fecha_inicio->toDateString(),
                    'fecha_fin' => $periodo->fecha_fin?->toDateString(),
                    'total_tareas' => (int) $conteos->sum(),
                    'por_estado' => collect(EstadoTarea::cases())->mapWithKeys(
                        fn (EstadoTarea $estado): array => [$estado->value => (int) $conteos->get($estado->value, 0)],
                    ),
                ];
            }),
            'reportes' => $reportes->map(fn (Reporte $reporte): array => [
                'id' => $reporte->id,
                'tipo' => $reporte->tipo->value,
                'periodo_consecutivo' => $reporte->periodo->consecutivo,
                'nombre_archivo' => $reporte->nombre_archivo,
                'total_tareas' => $reporte->total_tareas,
                'resumen_estados' => $reporte->resumen_estados,
                'hash' => $reporte->hash,
                'generado_por' => $reporte->generadoPor->name,
                'generado_en' => $reporte->generado_en->toDateTimeString(),
            ])->values(),
            'puede' => [
                'generar' => Gate::allows('create', [Reporte::class, $proyecto]),
            ],
        ]);
    }

    public function store(ReporteRequest $request, Proyecto $proyecto, GenerarReporte $generar): RedirectResponse
    {
        Gate::authorize('create', [Reporte::class, $proyecto]);

        $periodo = Periodo::query()
            ->where('proyecto_id', $proyecto->id)
            ->findOrFail($request->validated('periodo_id'));

        $tipo = TipoReporte::from((string) $request->validated('tipo'));

        $reporte = $generar($periodo, $tipo, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('reportes.generado')]);
        // La interfaz usa este id para pedir la descarga en la misma acción,
        // por la ruta de descarga histórica: leer el snapshot que se acaba de
        // escribir **es** la descarga «actual» (D7.5, docs/03-dominio.md §7).
        Inertia::flash('reporte_generado_id', $reporte->id);

        return to_route('proyectos.reportes.index', $proyecto);
    }

    public function descargar(Reporte $reporte): BinaryFileResponse
    {
        Gate::authorize('view', $reporte);

        $export = $reporte->tipo === TipoReporte::Completo
            ? new ReporteCompletoExport($reporte->snapshot)
            : new HojaClienteExport($reporte->snapshot['tareas']);

        return Excel::download($export, $reporte->nombre_archivo);
    }
}
