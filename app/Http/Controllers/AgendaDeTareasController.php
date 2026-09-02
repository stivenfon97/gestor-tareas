<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Enums\TipoDeVista;
use App\Enums\TipoTarea;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Services\ArbolDeModulos;
use App\Services\EstadoEnLaUrl;
use App\Services\FiltrosDeTareas;
use App\Services\VistasGuardadas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Calendario mensual de tareas por `fecha_fin`.
 *
 * **Las tareas sin fecha van a una franja aparte y visible.** Si no estuvieran,
 * el calendario mentiría: enseñaría veinte tareas cuando el proyecto tiene
 * ochenta, y las sesenta que faltan serían justo las que nadie ha fechado
 * todavía. Un calendario que esconde lo que no encaja en su rejilla no es una
 * vista del trabajo, es una vista de la rejilla.
 *
 * Sin arrastre entre días en esta fase: abrir una tarea desde una celda es
 * suficiente, y mover fechas por arrastre es una operación con consecuencias de
 * periodo que la fase 09 todavía no ha definido.
 */
class AgendaDeTareasController extends Controller
{
    /**
     * Tope de la franja de tareas sin fecha.
     *
     * Con el tope se ve cuántas hay aunque no quepan todas; sin él, un proyecto
     * con trescientas sin fechar convierte el calendario en un listado.
     */
    private const TOPE_SIN_FECHA = 50;

    public function index(Request $request, Proyecto $proyecto): Response|RedirectResponse
    {
        Gate::authorize('viewAny', [Tarea::class, $proyecto]);

        $predeterminada = VistasGuardadas::predeterminada($request, TipoDeVista::Calendario, $proyecto);

        // Al entrar con la URL desnuda se aplica la vista predeterminada, y se
        // aplica **navegando**: así la barra de direcciones dice qué se está
        // viendo y el enlace se puede compartir. Aplicarla en silencio dejaría
        // una pantalla filtrada cuya URL no explica por qué.
        if ($predeterminada !== null) {
            return redirect()->route($request->route()->getName(), ['proyecto' => $proyecto] + $predeterminada);
        }

        $mes = $this->mes($request);
        $rutas = ArbolDeModulos::rutasDe($proyecto);

        $delMes = FiltrosDeTareas::paraProyecto($request, $proyecto)
            ->getEloquentBuilder()
            ->whereBetween('fecha_fin', [$mes->toDateString(), $mes->copy()->endOfMonth()->toDateString()])
            ->with(['modulo:id,nombre'])
            ->orderBy('fecha_fin')
            ->orderBy('orden')
            ->get();

        $sinFecha = FiltrosDeTareas::paraProyecto($request, $proyecto)
            ->getEloquentBuilder()
            ->whereNull('fecha_fin')
            ->with(['modulo:id,nombre'])
            ->orderBy('orden')
            ->orderBy('id');

        $totalSinFecha = $sinFecha->clone()->count();

        return Inertia::render('tareas/Agenda', [
            'proyecto' => $proyecto->only(['id', 'nombre', 'slug', 'prefijo_tarea']),
            'mes' => $mes->format('Y-m'),
            'dias' => $delMes
                ->groupBy(static fn (Tarea $tarea): string => $tarea->fecha_fin->toDateString())
                ->map(static fn ($tareas) => $tareas->map(self::comoTarjeta(...))->values()->all())
                ->all(),
            'sinFecha' => [
                'total' => $totalSinFecha,
                'tareas' => $sinFecha->take(self::TOPE_SIN_FECHA)->get()->map(self::comoTarjeta(...))->all(),
            ],
            'filtros' => EstadoEnLaUrl::filtros($request),
            'vistas' => VistasGuardadas::para($request->user(), TipoDeVista::Calendario, $proyecto),
            'tipoDeVista' => TipoDeVista::Calendario->value,
            'opciones' => [
                'estados' => $this->comoOpciones(EstadoTarea::cases()),
                'tipos' => $this->comoOpciones(TipoTarea::cases()),
                'prioridades' => $this->comoOpciones(PrioridadTarea::cases()),
                'modulos' => array_map(
                    static fn (string $ruta, int $id): array => ['id' => $id, 'ruta' => $ruta],
                    $rutas,
                    array_keys($rutas),
                ),
            ],
        ]);
    }

    /**
     * El mes pedido, o el actual.
     *
     * Un `mes` con basura cae en el actual en lugar de dar error: llega de la
     * barra de direcciones y de enlaces guardados, y un calendario que no abre
     * es peor que uno que abre por hoy.
     */
    private function mes(Request $request): Carbon
    {
        $pedido = (string) $request->input('mes', '');

        try {
            return Carbon::createFromFormat('Y-m', $pedido)->startOfMonth();
        } catch (\Throwable) {
            return Carbon::now()->startOfMonth();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function comoTarjeta(Tarea $tarea): array
    {
        return [
            'id' => $tarea->id,
            'codigo' => $tarea->codigo,
            'nombre' => $tarea->nombre,
            'estado' => $tarea->estado->value,
            'tipo' => $tarea->tipo->value,
            'prioridad' => $tarea->prioridad->value,
            'modulo' => $tarea->modulo?->nombre,
            'fecha_fin' => $tarea->fecha_fin?->toDateString(),
        ];
    }

    /**
     * @param  array<int, EstadoTarea|TipoTarea|PrioridadTarea>  $casos
     * @return array<int, array{valor: string, etiqueta: string}>
     */
    private function comoOpciones(array $casos): array
    {
        return array_map(static fn ($caso): array => [
            'valor' => $caso->value,
            'etiqueta' => $caso->etiqueta(),
        ], $casos);
    }
}
