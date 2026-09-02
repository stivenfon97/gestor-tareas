<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Enums\TipoReunion;
use App\Enums\TipoTarea;
use App\Enums\Visibilidad;
use App\Http\Requests\ReunionRequest;
use App\Models\AsistenteReunion;
use App\Models\Proyecto;
use App\Models\Reunion;
use App\Models\Tarea;
use App\Services\Adjuntos;
use App\Services\ResolverRolProyecto;
use App\Services\SanearHtmlDeReunion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ReunionController extends Controller
{
    public function index(Request $request, Proyecto $proyecto): Response
    {
        Gate::authorize('viewAny', [Reunion::class, $proyecto]);

        $reuniones = QueryBuilder::for(Reunion::class)
            ->where('proyecto_id', $proyecto->getKey())
            // Sin este scope el listado devolvería los títulos de las
            // reuniones internas aunque el detalle diera 403: el scope va
            // siempre antes que cualquier filtro de QueryBuilder.
            ->visiblesPara($request->user())
            ->allowedFilters(
                AllowedFilter::exact('tipo'),
                AllowedFilter::callback('desde', fn (Builder $query, $valor) => $query->whereDate('fecha', '>=', $valor)),
                AllowedFilter::callback('hasta', fn (Builder $query, $valor) => $query->whereDate('fecha', '<=', $valor)),
            )
            ->latest('fecha')
            ->get(['id', 'fecha', 'titulo', 'tipo', 'visibilidad']);

        return Inertia::render('reuniones/Listado', [
            'proyecto' => $proyecto->only(['id', 'nombre', 'slug', 'prefijo_tarea']),
            'reuniones' => $reuniones->map(fn (Reunion $reunion): array => [
                'id' => $reunion->id,
                'fecha' => $reunion->fecha->toDateTimeString(),
                'titulo' => $reunion->titulo,
                'tipo' => $reunion->tipo->value,
                'visibilidad' => $reunion->visibilidad->value,
            ]),
            'filtros' => $request->only(['tipo', 'desde', 'hasta']),
            'opciones' => ['tipos' => self::comoOpciones(TipoReunion::cases())],
            'puede' => ['crear' => Gate::allows('create', [Reunion::class, $proyecto])],
        ]);
    }

    public function create(Proyecto $proyecto): Response
    {
        Gate::authorize('create', [Reunion::class, $proyecto]);

        return Inertia::render('reuniones/Crear', [
            'proyecto' => $proyecto->only(['id', 'nombre', 'prefijo_tarea']),
            'opciones' => $this->opcionesDeFormulario($proyecto),
        ]);
    }

    public function store(ReunionRequest $request, Proyecto $proyecto): RedirectResponse
    {
        Gate::authorize('create', [Reunion::class, $proyecto]);

        $reunion = DB::transaction(function () use ($request, $proyecto): Reunion {
            $datos = $request->validated();

            $reunion = Reunion::query()->create([
                ...collect($datos)->except('asistentes')->all(),
                'proyecto_id' => $proyecto->getKey(),
                'contenido_html' => app(SanearHtmlDeReunion::class)($datos['contenido_html']),
                'creado_por' => $request->user()->getKey(),
            ]);

            $this->sincronizarAsistentes($reunion, $datos['asistentes']);

            return $reunion;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('reuniones.creada')]);

        return to_route('reuniones.show', $reunion);
    }

    public function show(Request $request, Reunion $reunion): Response
    {
        Gate::authorize('view', $reunion);

        $usuario = $request->user();
        $proyecto = $reunion->proyecto;

        $reunion->load(['asistentes.usuario:id,name', 'creadoPor:id,name']);

        return Inertia::render('reuniones/Detalle', [
            'proyecto' => $proyecto->only(['id', 'nombre', 'slug', 'prefijo_tarea']),
            'reunion' => $this->comoDetalle($reunion),
            'adjuntos' => [
                'imagenes' => $reunion->getMedia('imagenes')->map(Adjuntos::comoArray(...)),
                'documentos' => $reunion->getMedia('documentos')->map(Adjuntos::comoArray(...)),
            ],
            'tareasVinculadas' => $reunion->tareas()
                ->get(['tareas.id', 'codigo', 'nombre', 'estado'])
                ->map(fn (Tarea $tarea): array => [
                    'id' => $tarea->id,
                    'codigo' => $tarea->codigo,
                    'nombre' => $tarea->nombre,
                    'estado' => $tarea->estado->value,
                ]),
            // Para vincular una tarea existente: todas las del proyecto que
            // no lo estén ya. Se resuelve aquí y no en el cliente porque la
            // pertenencia al proyecto es una regla del servidor, no un filtro
            // de comodidad.
            'tareasDisponibles' => $proyecto->tareas()
                ->whereDoesntHave('reuniones', fn (Builder $query) => $query->whereKey($reunion->getKey()))
                ->orderByDesc('id')
                ->limit(200)
                ->get(['id', 'codigo', 'nombre'])
                ->map(fn (Tarea $tarea): array => [
                    'id' => $tarea->id,
                    'codigo' => $tarea->codigo,
                    'nombre' => $tarea->nombre,
                ]),
            // Para la captura rápida embebida: mismas opciones que
            // `CapturaRapidaController::create`, del lado de la tarea, no de
            // la reunión.
            'capturaRapida' => [
                'opciones' => [
                    'estados' => self::comoOpciones(EstadoTarea::cases()),
                    'tipos' => self::comoOpciones(TipoTarea::cases()),
                    'prioridades' => self::comoOpciones(PrioridadTarea::cases()),
                ],
                'periodoAbierto' => $proyecto->periodoAbierto()->value('consecutivo'),
            ],
            'puede' => [
                'editar' => $usuario->can('update', $reunion),
                'eliminar' => $usuario->can('delete', $reunion),
                'gestionarAdjuntosYTareas' => ResolverRolProyecto::escribe($usuario, $proyecto),
            ],
        ]);
    }

    public function edit(Reunion $reunion): Response
    {
        Gate::authorize('update', $reunion);

        $proyecto = $reunion->proyecto;
        $reunion->load('asistentes');

        return Inertia::render('reuniones/Editar', [
            'proyecto' => $proyecto->only(['id', 'nombre', 'prefijo_tarea']),
            'reunion' => [
                ...$this->comoDetalle($reunion),
                'contenido_json' => $reunion->contenido_json,
                'asistentes' => $reunion->asistentes->map(fn (AsistenteReunion $asistente): array => [
                    'usuario_id' => $asistente->usuario_id,
                    'nombre' => $asistente->nombre,
                    'organizacion' => $asistente->organizacion,
                ]),
            ],
            'opciones' => $this->opcionesDeFormulario($proyecto),
        ]);
    }

    public function update(ReunionRequest $request, Reunion $reunion): RedirectResponse
    {
        Gate::authorize('update', $reunion);

        DB::transaction(function () use ($request, $reunion): void {
            $datos = $request->validated();

            $reunion->update([
                ...collect($datos)->except('asistentes')->all(),
                'contenido_html' => app(SanearHtmlDeReunion::class)($datos['contenido_html']),
            ]);

            $this->sincronizarAsistentes($reunion, $datos['asistentes']);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('reuniones.actualizada')]);

        return to_route('reuniones.show', $reunion);
    }

    public function destroy(Reunion $reunion): RedirectResponse
    {
        Gate::authorize('delete', $reunion);

        $proyecto = $reunion->proyecto;

        // Borra también los adjuntos del disco, no solo las filas: Medialibrary
        // engancha su borrado al evento `deleting` del modelo dueño.
        $reunion->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('reuniones.eliminada')]);

        return to_route('proyectos.reuniones.index', $proyecto);
    }

    /**
     * @param  array<int, array{usuario_id?: int|null, nombre: string, organizacion?: string|null}>  $asistentes
     */
    private function sincronizarAsistentes(Reunion $reunion, array $asistentes): void
    {
        // Se reemplaza la lista entera: el formulario manda siempre el estado
        // completo del repetidor, no un diff. Misma idea que el orden de un
        // nivel completo de módulos en una sola petición.
        $reunion->asistentes()->delete();

        foreach ($asistentes as $asistente) {
            $reunion->asistentes()->create([
                'usuario_id' => $asistente['usuario_id'] ?? null,
                'nombre' => $asistente['nombre'],
                'organizacion' => $asistente['organizacion'] ?? null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function comoDetalle(Reunion $reunion): array
    {
        return [
            'id' => $reunion->id,
            'fecha' => $reunion->fecha->toDateTimeString(),
            'tipo' => $reunion->tipo->value,
            'titulo' => $reunion->titulo,
            'contenido_html' => $reunion->contenido_html,
            'acuerdos' => $reunion->acuerdos,
            'visibilidad' => $reunion->visibilidad->value,
            'creado_por' => $reunion->creadoPor?->name,
            'asistentes' => $reunion->asistentes->map(fn (AsistenteReunion $asistente): array => [
                'id' => $asistente->id,
                'nombre' => $asistente->nombre,
                'organizacion' => $asistente->organizacion,
                'usuario' => $asistente->usuario?->name,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function opcionesDeFormulario(Proyecto $proyecto): array
    {
        return [
            'tipos' => self::comoOpciones(TipoReunion::cases()),
            'visibilidades' => self::comoOpciones(Visibilidad::cases()),
            'miembros' => $proyecto->usuarios()->get(['users.id', 'users.name'])
                ->map(fn ($usuario): array => ['id' => $usuario->id, 'nombre' => $usuario->name]),
        ];
    }

    /**
     * @param  array<int, EstadoTarea|TipoTarea|PrioridadTarea|TipoReunion|Visibilidad>  $casos
     * @return array<int, array{valor: string, etiqueta: string}>
     */
    private static function comoOpciones(array $casos): array
    {
        return array_map(static fn ($caso): array => [
            'valor' => $caso->value,
            'etiqueta' => $caso->etiqueta(),
        ], $casos);
    }
}
