<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Enums\TipoTarea;
use App\Http\Requests\TareaRequest;
use App\Models\Modulo;
use App\Models\NotaTarea;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Services\ArbolDeModulos;
use App\Services\CambiarEstadoTarea;
use App\Services\EstadoDeTabla;
use App\Services\EstadoEnLaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Tareas: alta, edición, listado y detalle.
 *
 * El cambio de estado **no está aquí**: vive en `EstadoTareaController`, que
 * delega en `CambiarEstadoTarea`. Un `update(['estado' => …])` desde este
 * controlador se saltaría las fechas automáticas.
 */
class TareaController extends Controller
{
    public function index(Request $request, Proyecto $proyecto): Response
    {
        Gate::authorize('viewAny', [Tarea::class, $proyecto]);

        // Una consulta para todo el árbol del proyecto, reutilizada por las
        // filas y por el desplegable de filtro.
        $rutas = ArbolDeModulos::rutasDe($proyecto);

        $tareas = QueryBuilder::for(Tarea::class)
            // El scope va antes que cualquier filtro: sin él, un filtro mal
            // puesto devolvería tareas de proyectos ajenos.
            ->visiblesPara($request->user())
            ->where('proyecto_id', $proyecto->getKey())
            ->allowedFilters(
                AllowedFilter::exact('estado'),
                AllowedFilter::exact('tipo'),
                AllowedFilter::exact('prioridad'),
                AllowedFilter::exact('periodo_id'),
                AllowedFilter::callback('modulo_id', $this->filtroDeModulo(...)),
                AllowedFilter::callback('arrastradas', $this->filtroDeArrastradas(...)),
                AllowedFilter::callback('texto', $this->filtroDeTexto(...)),
            )
            ->defaultSort('codigo')
            ->allowedSorts(
                AllowedSort::field('codigo'),
                AllowedSort::field('nombre'),
                AllowedSort::field('estado'),
                AllowedSort::callback('prioridad', $this->ordenPorPrioridad(...)),
                AllowedSort::field('fecha_fin'),
                AllowedSort::field('updated_at'),
            )
            ->with('periodo:id,consecutivo')
            ->paginate(EstadoDeTabla::porPagina($request))
            ->withQueryString()
            // La ruta del módulo sale del mapa, no de la fila: `rutaCompleta()`
            // por fila subiría por `padre` con una consulta por nivel.
            ->through(fn (Tarea $tarea): array => $this->comoFila($tarea, $rutas));

        return Inertia::render('tareas/Index', [
            'proyecto' => $proyecto->only(['id', 'nombre', 'slug', 'prefijo_tarea']),
            'tareas' => $tareas,
            'filtros' => EstadoEnLaUrl::filtros($request),
            // El orden viaja ya descompuesto: la tabla necesita saber en qué
            // posición va cada campo para dibujar la prioridad de orden.
            'orden' => EstadoDeTabla::orden($request, 'codigo'),
            'columnasVisibles' => EstadoDeTabla::columnas($request),
            'opciones' => $this->opciones($proyecto, $rutas),
            'puede' => ['crear' => Gate::allows('create', [Tarea::class, $proyecto])],
        ]);
    }

    public function create(Request $request, Proyecto $proyecto): Response
    {
        Gate::authorize('create', [Tarea::class, $proyecto]);

        return Inertia::render('tareas/Crear', [
            'proyecto' => $proyecto->only(['id', 'nombre', 'prefijo_tarea']),
            'opciones' => $this->opciones($proyecto),
            'sugerencias' => [
                // El módulo del último trabajo capturado en este proyecto: al
                // dar de alta varias tareas seguidas casi siempre es el mismo.
                'modulo_id' => $proyecto->tareas()
                    ->whereNotNull('modulo_id')
                    ->latest('id')
                    ->value('modulo_id'),
                'prioridad_por_tipo' => $this->prioridadPorTipo(),
            ],
            'candidatasDeOrigen' => $this->candidatasDeOrigen($proyecto),
            'periodoAbierto' => $this->periodoAbierto($proyecto),
        ]);
    }

    public function store(TareaRequest $request, Proyecto $proyecto): RedirectResponse
    {
        Gate::authorize('create', [Tarea::class, $proyecto]);

        $tarea = Tarea::query()->create($request->validated() + [
            'proyecto_id' => $proyecto->getKey(),
            // Toda tarea nace en el periodo abierto. Sin periodo abierto nace
            // suelta, y la interfaz ya lo advirtió en el formulario.
            'periodo_id' => $request->input('periodo_id') ?: $proyecto->periodoAbierto()->value('id'),
            'creado_por' => $request->user()->getKey(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('tareas.creada', ['codigo' => $tarea->codigo])]);

        return to_route('tareas.show', $tarea);
    }

    public function show(Request $request, Tarea $tarea): Response
    {
        Gate::authorize('view', $tarea);

        $usuario = $request->user();

        $tarea->load([
            'proyecto:id,nombre,slug,prefijo_tarea',
            'modulo:id,nombre,parent_id',
            'periodo:id,consecutivo',
            'periodoOrigen:id,consecutivo',
            'origen:id,codigo,nombre,estado',
            'creadoPor:id,name',
            'historial.usuario:id,name',
        ]);

        return Inertia::render('tareas/Show', [
            'tarea' => $this->comoDetalle($tarea),
            // La interfaz nunca deduce la matriz: la recibe.
            'transiciones' => CambiarEstadoTarea::disponiblesPara($tarea->estado),
            'notas' => NotaTarea::query()
                // El scope, no la policy: la policy solo cubre el acceso por id.
                ->visiblesPara($usuario)
                ->where('tarea_id', $tarea->getKey())
                ->with('usuario:id,name')
                ->latest('id')
                ->get()
                ->map(fn (NotaTarea $nota): array => [
                    'id' => $nota->id,
                    'contenido' => $nota->contenido,
                    'visibilidad' => $nota->visibilidad->value,
                    'autor' => $nota->usuario?->name,
                    'creada' => $nota->created_at?->toDateTimeString(),
                ]),
            'historial' => $tarea->historial->map(fn ($registro): array => [
                'id' => $registro->id,
                'anterior' => $registro->estado_anterior?->value,
                'nuevo' => $registro->estado_nuevo->value,
                'usuario' => $registro->usuario?->name,
                'cuando' => $registro->created_at?->toDateTimeString(),
                'comentario' => $registro->comentario,
            ]),
            'correcciones' => $tarea->correcciones()
                ->get(['id', 'codigo', 'nombre', 'estado', 'tipo'])
                ->map(fn (Tarea $hija): array => [
                    'id' => $hija->id,
                    'codigo' => $hija->codigo,
                    'nombre' => $hija->nombre,
                    'estado' => $hija->estado->value,
                    'tipo' => $hija->tipo->value,
                ]),
            'puede' => [
                'editar' => $usuario->can('update', $tarea),
                'cambiarEstado' => $usuario->can('cambiarEstado', $tarea),
                'eliminar' => $usuario->can('delete', $tarea),
                'comentar' => $usuario->can('create', [NotaTarea::class, $tarea]),
                'verLoInterno' => $usuario->can('viewAny', [NotaTarea::class, $tarea]),
            ],
        ]);
    }

    public function edit(Tarea $tarea): Response
    {
        Gate::authorize('update', $tarea);

        $proyecto = $tarea->proyecto;

        return Inertia::render('tareas/Editar', [
            'proyecto' => $proyecto->only(['id', 'nombre', 'prefijo_tarea']),
            'tarea' => [
                'id' => $tarea->id,
                'codigo' => $tarea->codigo,
                'nombre' => $tarea->nombre,
                'descripcion' => $tarea->descripcion,
                'estado' => $tarea->estado->value,
                'tipo' => $tarea->tipo->value,
                'prioridad' => $tarea->prioridad->value,
                'modulo_id' => $tarea->modulo_id,
                'periodo_id' => $tarea->periodo_id,
                'tarea_origen_id' => $tarea->tarea_origen_id,
            ],
            'opciones' => $this->opciones($proyecto),
            'candidatasDeOrigen' => $this->candidatasDeOrigen($proyecto, $tarea),
        ]);
    }

    public function update(TareaRequest $request, Tarea $tarea): RedirectResponse
    {
        Gate::authorize('update', $tarea);

        // `estado` y `tipo` no llegan validados: el primero tiene su propio
        // endpoint y el segundo no cambia.
        $tarea->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('tareas.actualizada')]);

        return to_route('tareas.show', $tarea);
    }

    public function destroy(Tarea $tarea): RedirectResponse
    {
        Gate::authorize('delete', $tarea);

        $proyecto = $tarea->proyecto;

        // Borrado suave: el código ya emitido no se reutiliza y el historial
        // sigue en pie.
        $tarea->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('tareas.eliminada')]);

        return to_route('proyectos.tareas.index', $proyecto);
    }

    /**
     * Filtro por módulo **incluyendo sus descendientes**: quien filtra por
     * "Clientes" espera ver también las de "Clientes › Facturación".
     *
     * @param  Builder<Tarea>  $consulta
     */
    private function filtroDeModulo(Builder $consulta, mixed $valor): void
    {
        $modulo = Modulo::query()->find((int) $valor);

        if ($modulo === null) {
            $consulta->whereRaw('1 = 0');

            return;
        }

        $consulta->whereIn('modulo_id', [$modulo->getKey(), ...ArbolDeModulos::descendientes($modulo)]);
    }

    /** @param Builder<Tarea> $consulta */
    private function filtroDeArrastradas(Builder $consulta, mixed $valor): void
    {
        filter_var($valor, FILTER_VALIDATE_BOOLEAN)
            ? $consulta->whereNotNull('periodo_origen_id')
            : $consulta->whereNull('periodo_origen_id');
    }

    /**
     * Texto libre sobre nombre, código y descripción: es como se busca de
     * verdad, sin acordarse de en qué campo estaba lo que se recuerda.
     *
     * @param  Builder<Tarea>  $consulta
     */
    private function filtroDeTexto(Builder $consulta, mixed $valor): void
    {
        $termino = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $valor).'%';

        $consulta->where(function (Builder $interna) use ($termino): void {
            $interna->where('nombre', 'like', $termino)
                ->orWhere('codigo', 'like', $termino)
                ->orWhere('descripcion', 'like', $termino);
        });
    }

    /**
     * Ordenar por la columna daría alta, baja, crítica, media — alfabético y
     * sin sentido. Se ordena por el peso del enum.
     *
     * @param  Builder<Tarea>  $consulta
     */
    private function ordenPorPrioridad(Builder $consulta, bool $descendente): void
    {
        $casos = collect(PrioridadTarea::cases())
            ->map(fn (PrioridadTarea $prioridad): string => sprintf(
                "WHEN '%s' THEN %d", $prioridad->value, $prioridad->peso(),
            ))
            ->implode(' ');

        $consulta->orderByRaw("CASE prioridad {$casos} ELSE 0 END ".($descendente ? 'desc' : 'asc'));
    }

    /**
     * @param  array<int, string>  $rutas
     * @return array<string, mixed>
     */
    private function comoFila(Tarea $tarea, array $rutas): array
    {
        return [
            'id' => $tarea->id,
            'codigo' => $tarea->codigo,
            'nombre' => $tarea->nombre,
            'estado' => $tarea->estado->value,
            'tipo' => $tarea->tipo->value,
            'prioridad' => $tarea->prioridad->value,
            'modulo_id' => $tarea->modulo_id,
            // Ruta completa, no solo el nombre: "Facturación" a secas no dice
            // de qué cuelga, y en un proyecto grande hay varias.
            'modulo' => $rutas[$tarea->modulo_id] ?? null,
            'periodo' => $tarea->periodo?->consecutivo,
            'arrastrada' => $tarea->estaArrastrada(),
            'fecha_fin' => $tarea->fecha_fin?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function comoDetalle(Tarea $tarea): array
    {
        return [
            'id' => $tarea->id,
            'codigo' => $tarea->codigo,
            'nombre' => $tarea->nombre,
            'descripcion' => $tarea->descripcion,
            'estado' => $tarea->estado->value,
            'tipo' => $tarea->tipo->value,
            'prioridad' => $tarea->prioridad->value,
            'motivo_espera' => $tarea->motivo_espera,
            'motivo_cancelacion' => $tarea->motivo_cancelacion,
            'fecha_inicio' => $tarea->fecha_inicio?->toDateString(),
            'fecha_fin' => $tarea->fecha_fin?->toDateString(),
            'creada_por' => $tarea->creadoPor?->name,
            'proyecto' => $tarea->proyecto->only(['id', 'nombre', 'slug']),
            'modulo' => $tarea->modulo === null ? null : [
                'id' => $tarea->modulo->id,
                'ruta' => $tarea->modulo->rutaCompleta(),
            ],
            'periodo' => $tarea->periodo?->consecutivo,
            'periodo_origen' => $tarea->periodoOrigen?->consecutivo,
            'arrastrada' => $tarea->estaArrastrada(),
            'origen' => $tarea->origen === null ? null : [
                'id' => $tarea->origen->id,
                'codigo' => $tarea->origen->codigo,
                'nombre' => $tarea->origen->nombre,
                'estado' => $tarea->origen->estado->value,
            ],
        ];
    }

    /**
     * @param  array<int, string>|null  $rutas  Mapa ya resuelto, si quien llama lo tiene.
     * @return array<string, mixed>
     */
    private function opciones(Proyecto $proyecto, ?array $rutas = null): array
    {
        $rutas ??= ArbolDeModulos::rutasDe($proyecto);

        return [
            'estados' => $this->comoOpciones(EstadoTarea::cases()),
            'tipos' => $this->comoOpciones(TipoTarea::cases()),
            'prioridades' => $this->comoOpciones(PrioridadTarea::cases()),
            'modulos' => collect($rutas)
                ->map(fn (string $ruta, int $id): array => ['id' => $id, 'ruta' => $ruta])
                ->sortBy('ruta')
                ->values(),
            'periodos' => $proyecto->periodos()->orderByDesc('consecutivo')->get()
                ->map(fn (Periodo $periodo): array => [
                    'id' => $periodo->id,
                    'consecutivo' => $periodo->consecutivo,
                    'estado' => $periodo->estado->value,
                ]),
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

    /**
     * Tareas del proyecto que pueden ser origen de una corrección.
     *
     * Se ofrecen todas menos la propia: la validación solo exige que sean del
     * mismo proyecto, y acotarlas aquí a las `entregada` dejaría fuera el caso
     * real de corregir algo que se detectó antes de entregarlo.
     *
     * @return array<int, array{id: int, codigo: string, nombre: string}>
     */
    private function candidatasDeOrigen(Proyecto $proyecto, ?Tarea $excluir = null): array
    {
        return $proyecto->tareas()
            ->when($excluir !== null, fn ($consulta) => $consulta->whereKeyNot($excluir->getKey()))
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'codigo', 'nombre'])
            ->map(fn (Tarea $tarea): array => [
                'id' => $tarea->id,
                'codigo' => $tarea->codigo,
                'nombre' => $tarea->nombre,
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function prioridadPorTipo(): array
    {
        $mapa = [];

        foreach (TipoTarea::cases() as $tipo) {
            $mapa[$tipo->value] = $tipo->prioridadPorDefecto()->value;
        }

        return $mapa;
    }

    /**
     * @return array{id: int, consecutivo: int}|null
     */
    private function periodoAbierto(Proyecto $proyecto): ?array
    {
        $periodo = $proyecto->periodoAbierto()->first(['id', 'consecutivo']);

        return $periodo === null ? null : [
            'id' => $periodo->id,
            'consecutivo' => $periodo->consecutivo,
        ];
    }
}
