<?php

declare(strict_types=1);

use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Enums\TipoTarea;
use App\Models\Modulo;
use App\Models\Proyecto;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);
});

/** Las columnas del tablero, tal y como llegan a la interfaz. */
function columnasDelTablero($respuesta): array
{
    return $respuesta->viewData('page')['props']['columnas'];
}

/** Una columna por su clave. */
function columna(array $columnas, string $clave): array
{
    return collect($columnas)->firstWhere('clave', $clave);
}

it('agrupa por estado con una columna por cada estado, aunque esté vacía', function () {
    Tarea::factory()->for($this->proyecto)->count(3)->create();
    Tarea::factory()->for($this->proyecto)->enProceso()->count(2)->create();

    $columnas = columnasDelTablero(
        $this->actingAs($this->lider)->get(route('proyectos.tablero.index', $this->proyecto)),
    );

    expect(array_column($columnas, 'clave'))
        ->toBe(array_column(EstadoTarea::cases(), 'value'));

    expect(columna($columnas, 'pendiente')['total'])->toBe(3)
        ->and(columna($columnas, 'en_proceso')['total'])->toBe(2)
        // Una columna vacía no se omite: «no hay nada entregado» es
        // información, y además hace falta como destino del arrastre.
        ->and(columna($columnas, 'entregada')['total'])->toBe(0)
        ->and(columna($columnas, 'entregada')['tareas'])->toBe([]);
});

it('agrupa por los otros tres criterios', function (string $agrupar, string $clave, int $esperadas) {
    $modulo = Modulo::factory()->for($this->proyecto)->create();

    Tarea::factory()->for($this->proyecto)->count(2)->create([
        'modulo_id' => $modulo->id,
        'tipo' => TipoTarea::Correccion,
        'prioridad' => PrioridadTarea::Critica,
    ]);

    $columnas = columnasDelTablero(
        $this->actingAs($this->lider)->get(
            route('proyectos.tablero.index', $this->proyecto).'?agrupar='.$agrupar,
        ),
    );

    $buscada = $clave === 'modulo' ? (string) $modulo->id : $clave;

    expect(columna($columnas, $buscada)['total'])->toBe($esperadas);
})->with([
    'por módulo' => ['modulo', 'modulo', 2],
    'por prioridad' => ['prioridad', 'critica', 2],
    'por tipo' => ['tipo', 'correccion', 2],
]);

it('agrupando por módulo deja una columna para las que no tienen ninguno', function () {
    Modulo::factory()->for($this->proyecto)->create();
    Tarea::factory()->for($this->proyecto)->count(3)->create(['modulo_id' => null]);

    $columnas = columnasDelTablero(
        $this->actingAs($this->lider)->get(
            route('proyectos.tablero.index', $this->proyecto).'?agrupar=modulo',
        ),
    );

    expect(columna($columnas, '')['total'])->toBe(3);
});

it('el total de una columna es el real, no el de las tareas cargadas', function () {
    Tarea::factory()->for($this->proyecto)->count(60)->create();

    $columnas = columnasDelTablero(
        $this->actingAs($this->lider)->get(route('proyectos.tablero.index', $this->proyecto)),
    );

    // Sin esto la columna diría «50» y nadie sabría que faltan diez.
    expect(columna($columnas, 'pendiente')['total'])->toBe(60)
        ->and(columna($columnas, 'pendiente')['tareas'])->toHaveCount(50);
});

it('cargar más trae la página siguiente de esa columna sin repetir ni saltarse nada', function () {
    Tarea::factory()->for($this->proyecto)->count(60)->create();

    $primeras = columna(
        columnasDelTablero(
            $this->actingAs($this->lider)->get(route('proyectos.tablero.index', $this->proyecto)),
        ),
        'pendiente',
    )['tareas'];

    $siguientes = $this->actingAs($this->lider)
        ->getJson(route('proyectos.tablero.columna', $this->proyecto).'?'.http_build_query([
            'agrupar' => 'estado',
            'clave' => 'pendiente',
            'desde' => 50,
        ]))
        ->assertOk()
        ->json('tareas');

    $ya = array_column($primeras, 'id');
    $luego = array_column($siguientes, 'id');

    expect($luego)->toHaveCount(10)
        // Ni una repetida…
        ->and(array_intersect($ya, $luego))->toBe([])
        // …ni una perdida entre las dos páginas.
        ->and(count(array_unique([...$ya, ...$luego])))->toBe(60);
});

it('las consultas del tablero no crecen con el número de columnas', function () {
    // Veinte módulos son veinte columnas. Con una consulta por columna esto
    // pasaría de seis a veintiséis y el tablero sería más lento cuanto mejor
    // organizado estuviera el proyecto.
    $modulos = Modulo::factory()->for($this->proyecto)->count(20)->create();

    foreach ($modulos as $modulo) {
        Tarea::factory()->for($this->proyecto)->count(3)->create(['modulo_id' => $modulo->id]);
    }

    // Igual que arriba: la primera petición del proceso trae los permisos.
    $this->actingAs($this->lider)->get(route('proyectos.tablero.index', $this->proyecto));

    $porEstado = consultasDe(fn () => $this->actingAs($this->lider)
        ->get(route('proyectos.tablero.index', $this->proyecto)));

    $porModulo = consultasDe(fn () => $this->actingAs($this->lider)
        ->get(route('proyectos.tablero.index', $this->proyecto).'?agrupar=modulo'));

    expect($porModulo)->toBeLessThanOrEqual($porEstado + 1);
});

it('las consultas del tablero no crecen con el número de tareas', function () {
    Tarea::factory()->for($this->proyecto)->count(50)->create();

    // Una petición en vacío antes de medir: la primera del proceso carga
    // además los permisos y la sesión, y sin descontarla la comparación mide
    // el arranque en lugar del tablero.
    $this->actingAs($this->lider)->get(route('proyectos.tablero.index', $this->proyecto));

    $conPocas = consultasDe(fn () => $this->actingAs($this->lider)
        ->get(route('proyectos.tablero.index', $this->proyecto)));

    Tarea::factory()->for($this->proyecto)->count(450)->create();

    $conMuchas = consultasDe(fn () => $this->actingAs($this->lider)
        ->get(route('proyectos.tablero.index', $this->proyecto)));

    expect($conMuchas)->toBe($conPocas);
});

it('el tablero solo trae tareas de proyectos donde el usuario participa', function () {
    $ajeno = Proyecto::factory()->create();
    Tarea::factory()->for($ajeno)->count(2)->create();
    Tarea::factory()->for($this->proyecto)->create();

    $columnas = columnasDelTablero(
        $this->actingAs($this->lider)->get(route('proyectos.tablero.index', $this->proyecto)),
    );

    expect(columna($columnas, 'pendiente')['total'])->toBe(1);
});

it('niega el tablero a quien no participa en el proyecto', function () {
    $ajeno = usuarioCon(RolGlobal::Miembro);

    $this->actingAs($ajeno)
        ->get(route('proyectos.tablero.index', $this->proyecto))
        ->assertForbidden();
});

/** Cuenta las consultas que dispara una petición. */
function consultasDe(callable $peticion): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $peticion();

    $total = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $total;
}
