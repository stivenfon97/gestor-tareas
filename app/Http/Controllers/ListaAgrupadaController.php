<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AgrupacionTablero;
use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Enums\TipoDeVista;
use App\Enums\TipoTarea;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Services\ArbolDeModulos;
use App\Services\ArmarTablero;
use App\Services\EstadoEnLaUrl;
use App\Services\FiltrosDeTareas;
use App\Services\VistasGuardadas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Las mismas tareas en lista vertical, agrupadas y con las secciones plegables.
 *
 * Es la vista para **repasar un proyecto entero de arriba abajo** antes de una
 * reunión: el tablero enseña en qué estado está cada cosa, y esto enseña qué
 * hay en cada módulo sin tener que desplazarse a lo ancho.
 *
 * Agrupa por módulo, periodo o estado. No por prioridad ni por tipo: en una
 * lista larga esos dos se leen mejor como marcas dentro de la fila que como
 * cuatro secciones que hay que recorrer enteras.
 *
 * Reutiliza `ArmarTablero` tal cual. Son la misma pregunta —«pártelo por este
 * campo y dime el total real de cada trozo»— con distinta forma de pintarlo.
 */
class ListaAgrupadaController extends Controller
{
    public function index(Request $request, Proyecto $proyecto, ArmarTablero $armar): Response|RedirectResponse
    {
        Gate::authorize('viewAny', [Tarea::class, $proyecto]);

        $predeterminada = VistasGuardadas::predeterminada($request, TipoDeVista::Lista, $proyecto);

        // Al entrar con la URL desnuda se aplica la vista predeterminada, y se
        // aplica **navegando**: así la barra de direcciones dice qué se está
        // viendo y el enlace se puede compartir. Aplicarla en silencio dejaría
        // una pantalla filtrada cuya URL no explica por qué.
        if ($predeterminada !== null) {
            return redirect()->route($request->route()->getName(), ['proyecto' => $proyecto] + $predeterminada);
        }

        $agrupacion = AgrupacionTablero::from(
            EstadoEnLaUrl::agrupacion($request, AgrupacionTablero::valores(AgrupacionTablero::deLaLista())),
        );

        $rutas = ArbolDeModulos::rutasDe($proyecto);

        return Inertia::render('tareas/Lista', [
            'proyecto' => $proyecto->only(['id', 'nombre', 'slug', 'prefijo_tarea']),
            'agrupacion' => $agrupacion->value,
            'agrupaciones' => array_map(static fn (AgrupacionTablero $caso): array => [
                'valor' => $caso->value,
                'etiqueta' => __($caso->etiqueta()),
            ], AgrupacionTablero::deLaLista()),
            'grupos' => $armar(
                FiltrosDeTareas::paraProyecto($request, $proyecto)->getEloquentBuilder(),
                $agrupacion,
                $this->gruposDe($agrupacion, $proyecto, $rutas),
            ),
            'limite' => ArmarTablero::LIMITE,
            'filtros' => EstadoEnLaUrl::filtros($request),
            'vistas' => VistasGuardadas::para($request->user(), TipoDeVista::Lista, $proyecto),
            'tipoDeVista' => TipoDeVista::Lista->value,
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
     * @param  array<int, string>  $rutas
     * @return array<int, array{clave: string, etiqueta: string}>
     */
    private function gruposDe(AgrupacionTablero $agrupacion, Proyecto $proyecto, array $rutas): array
    {
        $grupos = match ($agrupacion) {
            AgrupacionTablero::Estado => array_map(static fn (EstadoTarea $caso): array => [
                'clave' => $caso->value,
                'etiqueta' => __($caso->etiqueta()),
            ], EstadoTarea::cases()),

            AgrupacionTablero::Modulo => array_map(
                static fn (string $ruta, int $id): array => ['clave' => (string) $id, 'etiqueta' => $ruta],
                $rutas,
                array_keys($rutas),
            ),

            // Del más reciente al más antiguo: repasar un proyecto empieza por
            // lo que está abierto ahora, no por el primer periodo de su vida.
            AgrupacionTablero::Periodo => $proyecto->periodos()
                ->orderByDesc('consecutivo')
                ->get(['id', 'consecutivo'])
                ->map(static fn (Periodo $periodo): array => [
                    'clave' => (string) $periodo->id,
                    'etiqueta' => __('tareas.periodo_numero', ['consecutivo' => (string) $periodo->consecutivo]),
                ])
                ->all(),

            default => [],
        };

        // El grupo «sin» es un grupo de verdad: sin él, las tareas todavía sin
        // clasificar desaparecerían de la lista sin que nadie lo notara.
        $grupos[] = match ($agrupacion) {
            AgrupacionTablero::Modulo => ['clave' => '', 'etiqueta' => __('tareas.sin_modulo')],
            AgrupacionTablero::Periodo => ['clave' => '', 'etiqueta' => __('tareas.sin_periodo')],
            default => null,
        } ?? [];

        return array_values(array_filter($grupos));
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
