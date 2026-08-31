<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoPeriodo;
use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Tarea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Catálogo de clientes. No cuelgan de ningún proyecto, así que aquí manda solo
 * el rol global: `ClientePolicy` deja mirar a cualquier interno y reserva la
 * escritura a quien tiene `clientes.crear`.
 *
 * **Un cliente nunca se borra, se desactiva.** Tiene proyectos con historial de
 * pagos colgando y la clave foránea es `restrict`.
 */
class ClienteController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Cliente::class);

        /*
         * El listado ya sale por query-builder aunque la tabla de la fase 05
         * sea simple: la fase 07 sustituye solo la capa Vue por el componente
         * de TanStack, sin tocar el servidor.
         */
        $clientes = QueryBuilder::for(Cliente::class)
            ->allowedFilters(
                AllowedFilter::partial('nombre'),
                AllowedFilter::exact('activo'),
            )
            ->defaultSort('nombre')
            ->allowedSorts('nombre', 'created_at')
            ->withCount([
                'proyectos',
                'proyectos as proyectos_activos_count' => fn ($consulta) => $consulta->activos(),
            ])
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Cliente $cliente): array => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'contacto_nombre' => $cliente->contacto_nombre,
                'contacto_email' => $cliente->contacto_email,
                'activo' => $cliente->activo,
                'proyectos_count' => $cliente->proyectos_count,
                'proyectos_activos_count' => $cliente->proyectos_activos_count,
            ]);

        return Inertia::render('clientes/Index', [
            'clientes' => $clientes,
            'filtros' => [
                'nombre' => request()->input('filter.nombre'),
                'activo' => request()->input('filter.activo'),
            ],
            'puede' => ['crear' => Gate::allows('create', Cliente::class)],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Cliente::class);

        return Inertia::render('clientes/Crear');
    }

    public function store(ClienteRequest $request): RedirectResponse
    {
        Gate::authorize('create', Cliente::class);

        $cliente = Cliente::query()->create($request->validated() + ['activo' => true]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('clientes.creado')]);

        return to_route('clientes.show', $cliente);
    }

    public function show(Cliente $cliente): Response
    {
        Gate::authorize('view', $cliente);

        return Inertia::render('clientes/Show', [
            'cliente' => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'contacto_nombre' => $cliente->contacto_nombre,
                'contacto_email' => $cliente->contacto_email,
                'contacto_telefono' => $cliente->contacto_telefono,
                'notas' => $cliente->notas,
                'activo' => $cliente->activo,
            ],
            'proyectos' => $cliente->proyectos()
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'slug', 'prefijo_tarea', 'estado']),
            'resumen' => $this->resumen($cliente),
            'puede' => ['editar' => Gate::allows('update', $cliente)],
        ]);
    }

    public function edit(Cliente $cliente): Response
    {
        Gate::authorize('update', $cliente);

        return Inertia::render('clientes/Editar', [
            'cliente' => $cliente->only([
                'id', 'nombre', 'contacto_nombre', 'contacto_email', 'contacto_telefono', 'notas', 'activo',
            ]),
        ]);
    }

    public function update(ClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        Gate::authorize('update', $cliente);

        $cliente->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('clientes.actualizado')]);

        return to_route('clientes.show', $cliente);
    }

    /**
     * Proyectos activos, tareas abiertas y último periodo cerrado.
     *
     * @return array{proyectos_activos: int, tareas_abiertas: int, ultimo_periodo_cerrado: array{proyecto: string, consecutivo: int, fecha_fin: string|null}|null}
     */
    private function resumen(Cliente $cliente): array
    {
        $proyectos = $cliente->proyectos()->pluck('id');

        $ultimo = Periodo::query()
            ->whereIn('proyecto_id', $proyectos)
            ->where('estado', EstadoPeriodo::Cerrado)
            ->with('proyecto:id,nombre')
            ->orderByDesc('fecha_fin')
            ->orderByDesc('consecutivo')
            ->first();

        return [
            'proyectos_activos' => Proyecto::query()->whereKey($proyectos)->activos()->count(),
            'tareas_abiertas' => Tarea::query()->whereIn('proyecto_id', $proyectos)->activas()->count(),
            'ultimo_periodo_cerrado' => $ultimo === null ? null : [
                'proyecto' => $ultimo->proyecto->nombre,
                'consecutivo' => $ultimo->consecutivo,
                'fecha_fin' => $ultimo->fecha_fin?->toDateString(),
            ],
        ];
    }
}
