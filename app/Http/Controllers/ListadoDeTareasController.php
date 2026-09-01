<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Enums\TipoTarea;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Models\User;
use App\Services\EstadoDeTabla;
use App\Services\ResolverRolProyecto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Todas las tareas visibles del usuario, de todos sus proyectos.
 *
 * Existe porque hasta la fase 07b no había forma de ver las tareas propias sin
 * entrar antes a un proyecto y buscar su pestaña: la pantalla que más se usa no
 * era un destino. `TareaController@index` sigue sirviendo el listado de un
 * proyecto concreto; los dos pintan `tareas/Index`, que es el mismo listado con
 * distinto alcance.
 *
 * **La autorización aquí es el scope, no una policy.** `TareaPolicy@viewAny`
 * pregunta por un proyecto y aquí no hay uno; lo que acota las filas es
 * `visiblesPara()`, que es obligatorio en todo listado de tareas.
 */
class ListadoDeTareasController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $usuario = $request->user();

        $tareas = QueryBuilder::for(Tarea::class)
            ->visiblesPara($usuario)
            ->allowedFilters(
                AllowedFilter::exact('estado'),
                AllowedFilter::exact('tipo'),
                AllowedFilter::exact('prioridad'),
                AllowedFilter::exact('proyecto_id'),
                AllowedFilter::callback('arrastradas', $this->filtroDeArrastradas(...)),
                AllowedFilter::callback('texto', $this->filtroDeTexto(...)),
            )
            // Lo último que se tocó primero: en un listado de varios proyectos
            // el código correlativo no ordena nada útil, porque cada proyecto
            // tiene su propia serie.
            ->defaultSort('-updated_at')
            ->allowedSorts(
                AllowedSort::field('codigo'),
                AllowedSort::field('nombre'),
                AllowedSort::field('estado'),
                AllowedSort::callback('prioridad', $this->ordenPorPrioridad(...)),
                AllowedSort::field('fecha_fin'),
                AllowedSort::field('updated_at'),
            )
            // Solo el nombre del módulo, no su ruta completa: resolverla exige
            // el árbol de cada proyecto, y aquí hay tantos árboles como
            // proyectos tenga el usuario.
            ->with(['proyecto:id,nombre', 'modulo:id,nombre'])
            ->paginate(EstadoDeTabla::porPagina($request))
            ->withQueryString()
            ->through(fn (Tarea $tarea): array => [
                'id' => $tarea->id,
                'codigo' => $tarea->codigo,
                'nombre' => $tarea->nombre,
                'estado' => $tarea->estado->value,
                'tipo' => $tarea->tipo->value,
                'prioridad' => $tarea->prioridad->value,
                'modulo_id' => $tarea->modulo_id,
                'modulo' => $tarea->modulo?->nombre,
                'periodo' => null,
                'proyecto' => $tarea->proyecto?->only(['id', 'nombre']),
                'arrastrada' => $tarea->estaArrastrada(),
                'fecha_fin' => $tarea->fecha_fin?->toDateString(),
            ]);

        return Inertia::render('tareas/Index', [
            // Sin proyecto: es lo que le dice a la página que está en el
            // listado global y no en el de un proyecto.
            'proyecto' => null,
            'tareas' => $tareas,
            'filtros' => EstadoDeTabla::filtros($request),
            'orden' => EstadoDeTabla::orden($request, '-updated_at'),
            'columnasVisibles' => EstadoDeTabla::columnas($request),
            'opciones' => [
                'estados' => $this->comoOpciones(EstadoTarea::cases()),
                'tipos' => $this->comoOpciones(TipoTarea::cases()),
                'prioridades' => $this->comoOpciones(PrioridadTarea::cases()),
                'modulos' => [],
                'periodos' => [],
                'proyectos' => $this->proyectosVisibles($usuario),
            ],
            'puede' => ['crear' => false],
        ]);
    }

    /**
     * Proyectos del desplegable de filtro. Es un catálogo corto y no crece con
     * las filas, así que va entero en lugar de por un endpoint de búsqueda.
     *
     * @return array<int, array{id: int, nombre: string}>
     */
    private function proyectosVisibles(User $usuario): array
    {
        return Proyecto::query()
            ->when(
                ! $usuario->esAdministrador(),
                fn (Builder $consulta) => $consulta->whereIn('id', ResolverRolProyecto::proyectosDelUsuario($usuario)),
            )
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn (Proyecto $proyecto): array => [
                'id' => $proyecto->id,
                'nombre' => $proyecto->nombre,
            ])
            ->all();
    }

    /** @param Builder<Tarea> $consulta */
    private function filtroDeArrastradas(Builder $consulta, mixed $valor): void
    {
        filter_var($valor, FILTER_VALIDATE_BOOLEAN)
            ? $consulta->whereNotNull('periodo_origen_id')
            : $consulta->whereNull('periodo_origen_id');
    }

    /** @param Builder<Tarea> $consulta */
    private function filtroDeTexto(Builder $consulta, mixed $valor): void
    {
        $termino = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $valor).'%';

        $consulta->where(function (Builder $interna) use ($termino): void {
            $interna->where('nombre', 'like', $termino)
                ->orWhere('codigo', 'like', $termino)
                ->orWhere('descripcion', 'like', $termino);
        });
    }

    /** @param Builder<Tarea> $consulta */
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
