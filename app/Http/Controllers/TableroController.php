<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AgrupacionTablero;
use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Enums\TipoDeVista;
use App\Enums\TipoTarea;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Services\ArbolDeModulos;
use App\Services\ArmarTablero;
use App\Services\EstadoEnLaUrl;
use App\Services\FiltrosDeTareas;
use App\Services\VistasGuardadas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * El tablero de un proyecto: las mismas tareas del listado, **agrupadas**.
 *
 * Endpoint propio y no un parámetro más sobre `TareaController@index`: un
 * paginador plano y un conjunto de columnas con su total son formas de datos
 * distintas, y forzarlas en la misma respuesta las hace peores a las dos —el
 * listado acabaría devolviendo columnas vacías y el tablero, un `last_page` que
 * no significa nada—.
 *
 * Los filtros sí son los mismos, y por eso los declara `FiltrosDeTareas` en un
 * solo sitio: un filtro que existe en el listado y no en el tablero convierte
 * cambiar de vista en perder lo que habías buscado.
 */
class TableroController extends Controller
{
    public function index(Request $request, Proyecto $proyecto, ArmarTablero $armar): Response|RedirectResponse
    {
        Gate::authorize('viewAny', [Tarea::class, $proyecto]);

        $predeterminada = VistasGuardadas::predeterminada($request, TipoDeVista::Kanban, $proyecto);

        // Al entrar con la URL desnuda se aplica la vista predeterminada, y se
        // aplica **navegando**: así la barra de direcciones dice qué se está
        // viendo y el enlace se puede compartir. Aplicarla en silencio dejaría
        // una pantalla filtrada cuya URL no explica por qué.
        if ($predeterminada !== null) {
            return redirect()->route($request->route()->getName(), ['proyecto' => $proyecto] + $predeterminada);
        }

        $agrupacion = AgrupacionTablero::from(
            EstadoEnLaUrl::agrupacion($request, AgrupacionTablero::valores(AgrupacionTablero::delTablero())),
        );

        $rutas = ArbolDeModulos::rutasDe($proyecto);

        $columnas = $armar(
            FiltrosDeTareas::paraProyecto($request, $proyecto)->getEloquentBuilder(),
            $agrupacion,
            $this->columnasDe($agrupacion, $rutas),
        );

        return Inertia::render('tareas/Tablero', [
            'proyecto' => $proyecto->only(['id', 'nombre', 'slug', 'prefijo_tarea']),
            'agrupacion' => $agrupacion->value,
            'agrupaciones' => $this->agrupaciones(),
            'columnas' => $columnas,
            'filtros' => EstadoEnLaUrl::filtros($request),
            'vistas' => VistasGuardadas::para($request->user(), TipoDeVista::Kanban, $proyecto),
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
            'puede' => [
                'crear' => Gate::allows('create', [Tarea::class, $proyecto]),
                'mover' => Gate::allows('moverEnTablero', [Tarea::class, $proyecto]),
            ],
        ]);
    }

    /**
     * La página siguiente de **una** columna.
     *
     * Va en JSON y no como recarga parcial de Inertia porque una recarga
     * parcial vuelve a armar el tablero entero: pedir veinte tarjetas más de
     * «entregada» recalcularía las seis columnas y sus seis totales.
     */
    public function columna(Request $request, Proyecto $proyecto, ArmarTablero $armar): JsonResponse
    {
        Gate::authorize('viewAny', [Tarea::class, $proyecto]);

        $datos = $request->validate([
            'agrupar' => ['required', 'string', 'in:'.implode(',', AgrupacionTablero::valores(AgrupacionTablero::delTablero()))],
            // Cadena vacía es una clave válida: es la columna «sin módulo».
            'clave' => ['present', 'string'],
            'desde' => ['required', 'integer', 'min:0'],
        ]);

        return response()->json([
            'tareas' => $armar->pagina(
                FiltrosDeTareas::paraProyecto($request, $proyecto)->getEloquentBuilder(),
                AgrupacionTablero::from($datos['agrupar']),
                $datos['clave'],
                (int) $datos['desde'],
            ),
        ]);
    }

    /**
     * Las columnas que se pintan, en su orden y **aunque estén vacías**.
     *
     * Una columna vacía no se omite: el tablero dice «no hay nada en proceso»,
     * que es información, y además hace falta como destino del arrastre.
     *
     * @param  array<int, string>  $rutas
     * @return array<int, array{clave: string, etiqueta: string}>
     */
    private function columnasDe(AgrupacionTablero $agrupacion, array $rutas): array
    {
        $columnas = match ($agrupacion) {
            AgrupacionTablero::Estado => array_map(static fn (EstadoTarea $caso): array => [
                'clave' => $caso->value,
                'etiqueta' => __($caso->etiqueta()),
            ], EstadoTarea::cases()),

            AgrupacionTablero::Prioridad => array_map(static fn (PrioridadTarea $caso): array => [
                'clave' => $caso->value,
                'etiqueta' => __($caso->etiqueta()),
            ], PrioridadTarea::cases()),

            AgrupacionTablero::Tipo => array_map(static fn (TipoTarea $caso): array => [
                'clave' => $caso->value,
                'etiqueta' => __($caso->etiqueta()),
            ], TipoTarea::cases()),

            AgrupacionTablero::Modulo => array_map(
                static fn (string $ruta, int $id): array => [
                    'clave' => (string) $id,
                    'etiqueta' => $ruta,
                ],
                $rutas,
                array_keys($rutas),
            ),

            // El tablero no agrupa por periodo: daría una columna por cada
            // periodo cerrado del proyecto, que no es un tablero sino un
            // archivo. La lista agrupada sí lo hace.
            AgrupacionTablero::Periodo => [],
        };

        // «Sin módulo» es una columna de verdad: si no estuviera, las tareas
        // que aún no se han clasificado desaparecerían del tablero.
        if ($agrupacion === AgrupacionTablero::Modulo) {
            $columnas[] = ['clave' => '', 'etiqueta' => __('tareas.sin_modulo')];
        }

        return array_values($columnas);
    }

    /**
     * @return array<int, array{valor: string, etiqueta: string}>
     */
    private function agrupaciones(): array
    {
        return array_map(static fn (AgrupacionTablero $caso): array => [
            'valor' => $caso->value,
            'etiqueta' => __($caso->etiqueta()),
        ], AgrupacionTablero::delTablero());
    }

    /**
     * @param  array<int, EstadoTarea|TipoTarea|PrioridadTarea>  $casos
     * @return array<int, array{valor: string, etiqueta: string}>
     */
    private function comoOpciones(array $casos): array
    {
        return array_map(static fn ($caso): array => [
            'valor' => $caso->value,
            'etiqueta' => __($caso->etiqueta()),
        ], $casos);
    }
}
