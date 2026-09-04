<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoPeriodo;
use App\Enums\EstadoTarea;
use App\Enums\Permiso;
use App\Enums\RolProyecto;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Reunion;
use App\Models\Tarea;
use App\Models\User;
use App\Services\ResolverRolProyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Punto de entrada tras iniciar sesión. Cuatro bloques, cada uno diferido por
 * separado: `Inertia::defer(fn () => …, 'grupo')` con un grupo **distinto**
 * por bloque, porque sin grupo propio Inertia agrupa todos los diferidos de
 * la página en una sola petición, y el bloque más lento retrasaría a los
 * otros tres (ver "Grouping Requests" en la documentación de props
 * diferidas de Inertia 3).
 *
 * Se abre muchas veces al día: cada bloque tiene su propia prueba de techo de
 * consultas, con el mismo patrón manual (`DB::enableQueryLog()`) que ya usan
 * `ArbolDeModulosTest` y `TableroTest` — no hay un helper compartido de Pest
 * para esto en el proyecto.
 */
class DashboardController extends Controller
{
    private const LIMITE_TAREAS = 15;

    private const LIMITE_REUNIONES = 6;

    public function index(Request $request): Response
    {
        $usuario = $request->user();
        $veIngresos = $this->veIngresos($usuario);

        return Inertia::render('Dashboard', [
            'trabajo' => Inertia::defer(fn () => $this->trabajoDeHoy($usuario), 'trabajo'),
            'periodos' => Inertia::defer(fn () => $this->periodosAbiertos($usuario), 'periodos'),
            'reuniones' => Inertia::defer(fn () => $this->ultimasReuniones($usuario), 'reuniones'),
            // Bandera barata y siempre presente: es lo que le dice a la
            // interfaz si debe pintar la tarjeta en absoluto. El dato pesado
            // —el dinero mismo— va aparte, en la clave diferida de abajo, y
            // esa sí falta del todo para quien no puede verla.
            'veIngresos' => $veIngresos,
        ] + ($veIngresos ? [
            // Ausente del todo para quien no ve pagos en ningún proyecto —no
            // vacío, no `null`—: el mismo patrón que `respaldos` en
            // `ProyectoController::show`. El cliente no ve este bloque ni en
            // pantalla ni en el JSON.
            'ingresos' => Inertia::defer(fn () => $this->ingresosPorCliente($usuario), 'ingresos'),
        ] : []));
    }

    /**
     * Tareas `en_proceso` y `en_espera` de todos los proyectos donde el
     * usuario participa, la más tiempo parada primero.
     *
     * "Tiempo parado" es desde que entró a su estado actual, no desde la
     * última edición: tocar la descripción no debería hacer que una tarea
     * parezca recién movida. `historial_estados` registra cada transición, así
     * que la fecha de entrada al estado actual es el último registro de esa
     * tarea; para una tarea sin historial (creada directo en ese estado, fuera
     * del flujo normal) se cae a su fecha de creación.
     *
     * @return array<int, array{id: int, codigo: string, nombre: string, proyecto: array{id: int, nombre: string}, estado: string, motivo_espera: string|null, dias_en_estado: int}>
     */
    private function trabajoDeHoy(User $usuario): array
    {
        return Tarea::query()
            ->visiblesPara($usuario)
            ->whereIn('estado', [EstadoTarea::EnProceso, EstadoTarea::EnEspera])
            ->with('proyecto:id,nombre')
            ->select('tareas.*')
            ->selectRaw(
                '(select max(created_at) from historial_estados where historial_estados.tarea_id = tareas.id)'
                .' as entro_al_estado_en_historial',
            )
            ->orderByRaw('coalesce(entro_al_estado_en_historial, tareas.created_at) asc')
            ->limit(self::LIMITE_TAREAS)
            ->get()
            ->map(function (Tarea $tarea): array {
                $historial = $tarea->getAttribute('entro_al_estado_en_historial');
                $entrada = $historial !== null ? Carbon::parse($historial) : $tarea->created_at;

                return [
                    'id' => $tarea->id,
                    'codigo' => $tarea->codigo,
                    'nombre' => $tarea->nombre,
                    'proyecto' => $tarea->proyecto->only(['id', 'nombre']),
                    'estado' => $tarea->estado->value,
                    'motivo_espera' => $tarea->estado === EstadoTarea::EnEspera ? $tarea->motivo_espera : null,
                    'dias_en_estado' => (int) $entrada->diffInDays(now()),
                ];
            })
            ->all();
    }

    /**
     * El periodo abierto de cada proyecto del usuario —a lo sumo uno por
     * proyecto (D4.2)— con días transcurridos, tareas sin terminar y si ya
     * pasó su fecha de fin.
     *
     * @return array<int, array{id: int, consecutivo: int, proyecto: array{id: int, nombre: string}, dias_transcurridos: int, tareas_sin_terminar: int, vencido: bool}>
     */
    private function periodosAbiertos(User $usuario): array
    {
        return Periodo::query()
            ->abiertos()
            ->when(
                ! $usuario->esAdministrador(),
                fn ($consulta) => $consulta->whereIn('proyecto_id', ResolverRolProyecto::proyectosDelUsuario($usuario)),
            )
            ->with('proyecto:id,nombre')
            ->withCount(['tareas as tareas_sin_terminar_count' => fn ($consulta) => $consulta->activas()])
            ->orderBy('fecha_inicio')
            ->get()
            ->map(fn (Periodo $periodo): array => [
                'id' => $periodo->id,
                'consecutivo' => $periodo->consecutivo,
                'proyecto' => $periodo->proyecto->only(['id', 'nombre']),
                'dias_transcurridos' => (int) $periodo->fecha_inicio->diffInDays(now()),
                'tareas_sin_terminar' => $periodo->tareas_sin_terminar_count,
                'vencido' => $periodo->estaVencido(),
            ])
            ->all();
    }

    /**
     * Las reuniones más recientes de la bitácora de los proyectos del
     * usuario. `Reunion::visiblesPara()` ya resuelve admin, participación y
     * visibilidad interna/cliente — no se repite esa lógica aquí.
     *
     * @return array<int, array{id: int, titulo: string, fecha: string, proyecto: array{id: int, nombre: string}}>
     */
    private function ultimasReuniones(User $usuario): array
    {
        return Reunion::query()
            ->visiblesPara($usuario)
            ->with('proyecto:id,nombre')
            ->latest('fecha')
            ->limit(self::LIMITE_REUNIONES)
            ->get(['id', 'titulo', 'fecha', 'proyecto_id'])
            ->map(fn (Reunion $reunion): array => [
                'id' => $reunion->id,
                'titulo' => $reunion->titulo,
                'fecha' => $reunion->fecha->toDateString(),
                'proyecto' => $reunion->proyecto->only(['id', 'nombre']),
            ])
            ->all();
    }

    /**
     * Hay al menos un proyecto donde el usuario ve pagos —misma regla que
     * `PagoPolicy::viewAny()` (`veLoInterno()` + permiso `pagos.ver`), pero
     * resuelta una sola vez y no proyecto por proyecto: llamar a
     * `ResolverRolProyecto::para()` en un bucle repetiría la consulta al
     * pivote por cada proyecto.
     */
    private function veIngresos(User $usuario): bool
    {
        if (! $usuario->can(Permiso::PagosVer->value)) {
            return false;
        }

        if ($usuario->esAdministrador()) {
            return true;
        }

        if (! ($usuario->rolGlobal()?->veLoInterno() ?? false)) {
            return false;
        }

        return $usuario->proyectos()
            ->wherePivot('rol', '!=', RolProyecto::Cliente->value)
            ->exists();
    }

    /**
     * Ingresos por periodo cerrado, agrupados por cliente, en COP —el monto
     * que se congela en `pagos.monto_cop` al registrar el pago (D… ver
     * `docs/03-dominio.md`).
     *
     * Se llama solo cuando `veIngresos()` ya dio verdadero: no hace falta
     * repetir el filtro de proyectos "sin acceso interno" aquí, pero sí el de
     * "cliente" concreto por si el usuario tiene rol `cliente` en algunos
     * proyectos y no en otros.
     *
     * @return array<int, array{cliente: string, total_cop: string, periodos_count: int}>
     */
    private function ingresosPorCliente(User $usuario): array
    {
        return Pago::query()
            ->join('periodos', 'periodos.id', '=', 'pagos.periodo_id')
            ->join('proyectos', 'proyectos.id', '=', 'periodos.proyecto_id')
            ->join('clientes', 'clientes.id', '=', 'proyectos.cliente_id')
            ->where('periodos.estado', EstadoPeriodo::Cerrado->value)
            ->when(
                ! $usuario->esAdministrador(),
                fn ($consulta) => $consulta->whereIn('proyectos.id', $usuario->proyectos()
                    ->wherePivot('rol', '!=', RolProyecto::Cliente->value)
                    ->pluck('proyectos.id')),
            )
            ->selectRaw('clientes.id as cliente_id, clientes.nombre as cliente_nombre, sum(pagos.monto_cop) as total_cop, count(*) as periodos_count')
            ->groupBy('clientes.id', 'clientes.nombre')
            ->orderByDesc('total_cop')
            ->get()
            ->map(fn ($fila): array => [
                'cliente' => $fila->cliente_nombre,
                // `sum()` no pasa por el cast `decimal:2` del modelo —es un
                // agregado de la consulta—, y MySQL y SQLite no devuelven el
                // mismo tipo para "cero filas". Se formatea a mano, igual que
                // `total_pagado_usd` en `ProyectoController::resumen()`.
                'total_cop' => number_format((float) $fila->total_cop, 2, '.', ''),
                'periodos_count' => (int) $fila->periodos_count,
            ])
            ->all();
    }
}
