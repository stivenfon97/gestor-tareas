<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoProyecto;
use App\Http\Requests\ProyectoRequest;
use App\Models\Cliente;
use App\Models\Modulo;
use App\Models\Pago;
use App\Models\Proyecto;
use App\Models\Reporte;
use App\Models\Reunion;
use App\Services\EstadoDeTabla;
use App\Services\EstadoEnLaUrl;
use App\Services\ResolverRolProyecto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Proyectos: alta, edición y el concentrador desde el que se trabaja.
 *
 * Archivar es cambiar el estado a `finalizado`. El borrado suave existe solo
 * para deshacer una creación equivocada, y de él cuelga todo el historial.
 */
class ProyectoController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Proyecto::class);

        $usuario = $request->user();

        $proyectos = QueryBuilder::for(Proyecto::class)
            ->allowedFilters(
                AllowedFilter::partial('nombre'),
                AllowedFilter::exact('estado'),
                AllowedFilter::exact('cliente_id'),
            )
            ->when(
                // Sin fila en el pivote no hay proyecto que ver, ni en el listado.
                ! $usuario->esAdministrador(),
                fn ($consulta) => $consulta->whereIn('id', ResolverRolProyecto::proyectosDelUsuario($usuario)),
            )
            ->defaultSort('nombre')
            // Varios campos separados por coma (`sort=-created_at,nombre`) son
            // orden multicolumna: la lista es la prioridad del desempate.
            ->allowedSorts('nombre', 'created_at')
            ->with('cliente:id,nombre')
            ->withCount(['tareas as tareas_abiertas_count' => fn ($consulta) => $consulta->activas()])
            ->paginate(EstadoDeTabla::porPagina($request))
            ->withQueryString()
            ->through(fn (Proyecto $proyecto): array => [
                'id' => $proyecto->id,
                'nombre' => $proyecto->nombre,
                'prefijo_tarea' => $proyecto->prefijo_tarea,
                'estado' => $proyecto->estado->value,
                'cliente' => $proyecto->cliente?->only(['id', 'nombre']),
                'tareas_abiertas_count' => $proyecto->tareas_abiertas_count,
                'creado' => $proyecto->created_at?->toDateString(),
            ]);

        return Inertia::render('proyectos/Index', [
            'proyectos' => $proyectos,
            'filtros' => EstadoEnLaUrl::filtros($request),
            'orden' => EstadoDeTabla::orden($request, 'nombre'),
            'columnasVisibles' => EstadoDeTabla::columnas($request),
            'estados' => $this->estadosDisponibles(),
            // Para el filtro de columna. Es un catálogo corto y no crece con
            // las filas: mandarlo entero evita un endpoint de búsqueda aparte.
            'clientes' => $this->clientesAsignables(),
            'puede' => ['crear' => Gate::allows('create', Proyecto::class)],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Proyecto::class);

        return Inertia::render('proyectos/Crear', [
            'clientes' => $this->clientesAsignables(),
            'estados' => $this->estadosDisponibles(),
        ]);
    }

    public function store(ProyectoRequest $request): RedirectResponse
    {
        Gate::authorize('create', Proyecto::class);

        $datos = $request->validated();

        // Slug vacío: lo resuelve `ProyectoObserver@creating`, que además zanja
        // la colisión con un sufijo numérico.
        $proyecto = Proyecto::query()->create($datos);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('proyectos.creado')]);

        return to_route('proyectos.show', $proyecto);
    }

    /**
     * Concentrador del proyecto.
     *
     * Solo módulos y equipo tienen contenido real en la fase 05. El resto son
     * secciones **vacías con su fase anotada**, sin datos de relleno: un
     * placeholder con datos falsos se olvida y acaba en producción.
     */
    public function show(Request $request, Proyecto $proyecto): Response
    {
        Gate::authorize('view', $proyecto);

        $usuario = $request->user();

        $proyecto->loadCount([
            'modulos',
            'tareas',
            'tareas as tareas_abiertas_count' => fn ($consulta) => $consulta->activas(),
            'usuarios as miembros_count',
        ]);

        return Inertia::render('proyectos/Show', [
            'proyecto' => [
                'id' => $proyecto->id,
                'nombre' => $proyecto->nombre,
                'slug' => $proyecto->slug,
                'prefijo_tarea' => $proyecto->prefijo_tarea,
                'descripcion' => $proyecto->descripcion,
                'estado' => $proyecto->estado->value,
                'cliente' => $proyecto->cliente->only(['id', 'nombre']),
                'modulos_count' => $proyecto->modulos_count,
                'tareas_count' => $proyecto->tareas_count,
                'tareas_abiertas_count' => $proyecto->tareas_abiertas_count,
                'miembros_count' => $proyecto->miembros_count,
            ],
            'resumen' => $this->resumen($proyecto),
            // La pestaña activa viaja en la URL, no solo en el estado de Vue:
            // así se puede compartir un enlace a la bitácora de un proyecto y
            // volver a la misma pestaña tras recargar.
            'seccion' => $this->seccionActiva($request),
            'miRol' => ResolverRolProyecto::para($usuario, $proyecto)?->value,
            'puede' => [
                'editar' => $usuario->can('update', $proyecto),
                'eliminar' => $usuario->can('delete', $proyecto),
                'gestionarMiembros' => $usuario->can('gestionarMiembros', $proyecto),
                'gestionarModulos' => $usuario->can('create', [Modulo::class, $proyecto]),
                'verPagos' => $usuario->can('viewAny', [Pago::class, $proyecto]),
                'verReuniones' => $usuario->can('viewAny', [Reunion::class, $proyecto]),
                'generarReportes' => $usuario->can('create', [Reporte::class, $proyecto]),
            ],
        ]);
    }

    public function edit(Proyecto $proyecto): Response
    {
        Gate::authorize('update', $proyecto);

        return Inertia::render('proyectos/Editar', [
            'proyecto' => $proyecto->only([
                'id', 'cliente_id', 'nombre', 'slug', 'prefijo_tarea', 'descripcion',
            ]) + [
                'estado' => $proyecto->estado->value,
                // El formulario deshabilita el campo cuando ya hay códigos
                // emitidos. No es la cerradura: la cerradura está en el
                // observer.
                'prefijo_bloqueado' => $proyecto->tieneCodigosEmitidos(),
            ],
            'clientes' => $this->clientesAsignables($proyecto->cliente_id),
            'estados' => $this->estadosDisponibles(),
        ]);
    }

    public function update(ProyectoRequest $request, Proyecto $proyecto): RedirectResponse
    {
        Gate::authorize('update', $proyecto);

        $proyecto->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('proyectos.actualizado')]);

        return to_route('proyectos.show', $proyecto);
    }

    /**
     * Borrado suave, y solo para deshacer una creación equivocada. Archivar un
     * proyecto que sí trabajó es ponerlo en `finalizado`.
     */
    public function destroy(Proyecto $proyecto): RedirectResponse
    {
        Gate::authorize('delete', $proyecto);

        $proyecto->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('proyectos.eliminado')]);

        return to_route('proyectos.index');
    }

    /**
     * Conteo por estado, periodo abierto con sus días transcurridos y última
     * reunión.
     *
     * @return array{tareas_por_estado: array<string, int>, periodo_abierto: array{id: int, consecutivo: int, fecha_inicio: string, fecha_fin: string|null, dias_transcurridos: int, vencido: bool}|null, ultima_reunion: array{id: int, titulo: string, fecha: string}|null}
     */
    private function resumen(Proyecto $proyecto): array
    {
        $conteos = $proyecto->tareas()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->all();

        $periodo = $proyecto->periodoAbierto()->first();

        /*
         * La última reunión del resumen no pasa por `visiblesPara`: quien llega
         * aquí ya pasó `ProyectoPolicy@view`, pero eso solo dice que participa,
         * no que vea lo interno. Se filtra explícitamente.
         */
        $reunion = $proyecto->reuniones()
            ->visiblesPara(request()->user())
            ->latest('fecha')
            ->first(['id', 'titulo', 'fecha']);

        return [
            'tareas_por_estado' => $conteos,
            'periodo_abierto' => $periodo === null ? null : [
                'id' => $periodo->id,
                'consecutivo' => $periodo->consecutivo,
                'fecha_inicio' => $periodo->fecha_inicio->toDateString(),
                'fecha_fin' => $periodo->fecha_fin?->toDateString(),
                'dias_transcurridos' => (int) $periodo->fecha_inicio->diffInDays(now()),
                // Pasarse de la fecha de fin es válido y frecuente: el cierre
                // es manual. La interfaz lo señala, la validación no lo impide.
                'vencido' => $periodo->fecha_fin !== null && $periodo->fecha_fin->isPast(),
            ],
            'ultima_reunion' => $reunion === null ? null : [
                'id' => $reunion->id,
                'titulo' => $reunion->titulo,
                'fecha' => $reunion->fecha->toDateString(),
            ],
        ];
    }

    /**
     * Un cliente desactivado no admite proyectos nuevos, pero el que ya tiene
     * uno sigue apareciendo al editarlo: si no, cambiar la descripción de un
     * proyecto viejo obligaría a reactivar al cliente.
     *
     * @return array<int, array{id: int, nombre: string}>
     */
    /**
     * Pestaña abierta del concentrador. Se valida contra la lista: un
     * `?seccion=` inventado abre el resumen en vez de dejar la página en blanco.
     */
    private function seccionActiva(Request $request): string
    {
        $secciones = ['resumen', 'modulos', 'equipo', 'tareas', 'periodos', 'bitacora', 'reportes'];
        $pedida = (string) $request->input('seccion', 'resumen');

        return in_array($pedida, $secciones, true) ? $pedida : 'resumen';
    }

    private function clientesAsignables(?int $incluir = null): array
    {
        return Cliente::query()
            ->where(fn ($consulta) => $consulta->where('activo', true)
                ->when($incluir !== null, fn ($interna) => $interna->orWhere('id', $incluir)))
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn (Cliente $cliente): array => ['id' => $cliente->id, 'nombre' => $cliente->nombre])
            ->all();
    }

    /**
     * @return array<int, array{valor: string, etiqueta: string}>
     */
    private function estadosDisponibles(): array
    {
        return array_map(static fn (EstadoProyecto $estado): array => [
            'valor' => $estado->value,
            'etiqueta' => $estado->etiqueta(),
        ], EstadoProyecto::cases());
    }
}
