<?php

declare(strict_types=1);

use App\Enums\EstadoTarea;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Enums\Visibilidad;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Reunion;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);
});

/** Backdatea la entrada al estado actual escribiendo directo en el historial. */
function entroAlEstadoHace(Tarea $tarea, int $dias): void
{
    DB::table('historial_estados')->insert([
        'tarea_id' => $tarea->id,
        'usuario_id' => null,
        'estado_anterior' => 'pendiente',
        'estado_nuevo' => $tarea->estado->value,
        'comentario' => null,
        'created_at' => now()->subDays($dias),
    ]);
}

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($this->lider)->get(route('dashboard'))->assertOk();
});

it('no resuelve ningún bloque diferido en la respuesta inicial', function () {
    $respuesta = $this->actingAs($this->lider)->get(route('dashboard'));

    $props = $respuesta->viewData('page')['props'];

    expect($props)->not->toHaveKey('trabajo')
        ->and($props)->not->toHaveKey('periodos')
        ->and($props)->not->toHaveKey('reuniones')
        ->and($props)->not->toHaveKey('ingresos')
        // Bandera barata: esta sí viaja en la respuesta inicial.
        ->and($props)->toHaveKey('veIngresos');
});

it('el bloque de trabajo trae solo tareas en_proceso y en_espera de proyectos donde el usuario participa, la más tiempo parada primero', function () {
    $reciente = Tarea::factory()->for($this->proyecto)->enProceso()->create();
    entroAlEstadoHace($reciente, 2);

    $antigua = Tarea::factory()->for($this->proyecto)->enEspera('Falta información del cliente.')->create();
    entroAlEstadoHace($antigua, 20);

    // Otros estados: no deberían aparecer.
    Tarea::factory()->for($this->proyecto)->create(['estado' => EstadoTarea::Pendiente]);
    Tarea::factory()->for($this->proyecto)->terminada()->create();
    Tarea::factory()->for($this->proyecto)->entregada()->create();

    // Proyecto ajeno: aunque tenga tareas en_proceso, no participa.
    $ajeno = Proyecto::factory()->create();
    Tarea::factory()->for($ajeno)->enProceso()->create();

    $respuesta = $this->actingAs($this->lider)->get(route('dashboard'));

    $respuesta->assertInertia(
        fn (Assert $pagina) => $pagina->loadDeferredProps(
            'trabajo',
            fn (Assert $diferido) => $diferido
                ->has('trabajo', 2)
                ->where('trabajo.0.id', $antigua->id)
                ->where('trabajo.0.estado', 'en_espera')
                ->where('trabajo.0.motivo_espera', 'Falta información del cliente.')
                ->where('trabajo.1.id', $reciente->id)
                ->where('trabajo.1.estado', 'en_proceso')
                ->where('trabajo.1.motivo_espera', null),
        ),
    );
});

it('el bloque de periodos trae el periodo abierto de cada proyecto del usuario, con días, pendientes y vencido', function () {
    $periodo = Periodo::factory()->for($this->proyecto)->create([
        'fecha_inicio' => now()->subDays(10),
        'fecha_fin' => null,
    ]);
    Tarea::factory()->for($this->proyecto)->for($periodo)->enProceso()->create();
    Tarea::factory()->for($this->proyecto)->for($periodo)->entregada()->create();

    // Cerrado: no debe aparecer.
    Periodo::factory()->for($this->proyecto)->cerrado()->create();

    // Proyecto ajeno: no participa.
    $ajeno = Proyecto::factory()->create();
    Periodo::factory()->for($ajeno)->create();

    $respuesta = $this->actingAs($this->lider)->get(route('dashboard'));

    $respuesta->assertInertia(
        fn (Assert $pagina) => $pagina->loadDeferredProps(
            'periodos',
            fn (Assert $diferido) => $diferido
                ->has('periodos', 1)
                ->where('periodos.0.id', $periodo->id)
                ->where('periodos.0.dias_transcurridos', 10)
                ->where('periodos.0.tareas_sin_terminar', 1)
                ->where('periodos.0.vencido', false),
        ),
    );
});

it('el bloque de periodos marca vencido cuando la fecha de fin ya pasó', function () {
    Periodo::factory()->for($this->proyecto)->vencido()->create(['fecha_inicio' => now()->subDays(40)]);

    $respuesta = $this->actingAs($this->lider)->get(route('dashboard'));

    $respuesta->assertInertia(
        fn (Assert $pagina) => $pagina->loadDeferredProps(
            'periodos',
            fn (Assert $diferido) => $diferido->where('periodos.0.vencido', true),
        ),
    );
});

it('el bloque de reuniones respeta la visibilidad interna/cliente', function () {
    $interna = Reunion::factory()->for($this->proyecto)->create([
        'titulo' => 'Comité interno de márgenes',
        'visibilidad' => Visibilidad::Interna,
        'fecha' => now()->subDay(),
    ]);
    Reunion::factory()->for($this->proyecto)->create([
        'titulo' => 'Seguimiento con el cliente',
        'visibilidad' => Visibilidad::Cliente,
        'fecha' => now(),
    ]);

    // El líder ve las dos.
    $this->actingAs($this->lider)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $pagina) => $pagina->loadDeferredProps(
            'reuniones',
            fn (Assert $diferido) => $diferido->has('reuniones', 2),
        ));

    // Alguien con rol de proyecto `cliente` solo ve la marcada `Visibilidad::Cliente`.
    $delCliente = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($delCliente, $this->proyecto, RolProyecto::Cliente);

    $this->actingAs($delCliente)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $pagina) => $pagina->loadDeferredProps(
            'reuniones',
            fn (Assert $diferido) => $diferido
                ->has('reuniones', 1)
                ->where('reuniones.0.titulo', 'Seguimiento con el cliente'),
        ));

    expect($interna)->not->toBeNull();
});

it('el bloque de ingresos suma por cliente los pagos de periodos cerrados, y solo de proyectos donde el usuario ve pagos', function () {
    // `monto_cop` no se fija a mano: `PagoObserver::saving()` lo recalcula
    // siempre desde `monto_usd * tasa_cambio`, así que el total esperado se
    // arma desde esos dos campos.
    $periodoCerrado = Periodo::factory()->for($this->proyecto)->cerrado()->create();
    Pago::factory()->for($periodoCerrado)->create(['monto_usd' => '1000.00', 'tasa_cambio' => '5000.0000']);

    // Segundo proyecto del mismo cliente: se suma al mismo total.
    $segundoProyecto = Proyecto::factory()->for($this->proyecto->cliente)->create();
    asignarAProyecto($this->lider, $segundoProyecto, RolProyecto::Lider);
    $otroPeriodoCerrado = Periodo::factory()->for($segundoProyecto)->cerrado()->create();
    Pago::factory()->for($otroPeriodoCerrado)->create(['monto_usd' => '300.00', 'tasa_cambio' => '5000.0000']);

    // Periodo abierto con pago: no debe contarse (no está cerrado).
    $periodoAbierto = Periodo::factory()->for($this->proyecto)->create();
    Pago::factory()->for($periodoAbierto)->create(['monto_usd' => '9999.00', 'tasa_cambio' => '5000.0000']);

    // Proyecto ajeno: el usuario no participa, no debe sumarse.
    $ajeno = Proyecto::factory()->create();
    $periodoAjeno = Periodo::factory()->for($ajeno)->cerrado()->create();
    Pago::factory()->for($periodoAjeno)->create(['monto_usd' => '7777.00', 'tasa_cambio' => '5000.0000']);

    $respuesta = $this->actingAs($this->lider)->get(route('dashboard'));

    $respuesta->assertInertia(
        fn (Assert $pagina) => $pagina
            ->where('veIngresos', true)
            ->loadDeferredProps(
                'ingresos',
                fn (Assert $diferido) => $diferido
                    ->has('ingresos', 1)
                    ->where('ingresos.0.cliente', $this->proyecto->cliente->nombre)
                    ->where('ingresos.0.total_cop', '6500000.00')
                    ->where('ingresos.0.periodos_count', 2),
            ),
    );
});

it('el bloque de ingresos falta del todo para quien no ve pagos en ningún proyecto, ni la bandera lo delata como falso vacío', function () {
    $periodoCerrado = Periodo::factory()->for($this->proyecto)->cerrado()->create();
    Pago::factory()->for($periodoCerrado)->create();

    $delCliente = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($delCliente, $this->proyecto, RolProyecto::Cliente);

    $respuesta = $this->actingAs($delCliente)->get(route('dashboard'));

    $props = $respuesta->viewData('page')['props'];

    expect($props['veIngresos'])->toBeFalse()
        ->and($props)->not->toHaveKey('ingresos');
});

it('el administrador ve el trabajo, los periodos y los ingresos de todos los proyectos, tenga o no fila en el pivote', function () {
    $ajeno = Proyecto::factory()->create();
    Tarea::factory()->for($ajeno)->enProceso()->create();
    Periodo::factory()->for($ajeno)->create(['fecha_inicio' => now()->subDays(3)]);
    $periodoCerradoAjeno = Periodo::factory()->for($ajeno)->cerrado()->create();
    Pago::factory()->for($periodoCerradoAjeno)->create();

    $administrador = usuarioCon(RolGlobal::Administrador);

    $respuesta = $this->actingAs($administrador)->get(route('dashboard'));

    $respuesta->assertInertia(
        fn (Assert $pagina) => $pagina
            ->where('veIngresos', true)
            ->loadDeferredProps(['trabajo', 'periodos', 'ingresos'], fn (Assert $diferido) => $diferido
                ->has('trabajo', 1)
                ->has('periodos', 1)
                ->has('ingresos', 1)),
    );
});

it('cada bloque diferido dispara un número acotado de consultas', function () {
    $periodo = Periodo::factory()->for($this->proyecto)->create(['fecha_inicio' => now()->subDays(5)]);
    Tarea::factory()->for($this->proyecto)->for($periodo)->enProceso()->count(3)->create();
    Tarea::factory()->for($this->proyecto)->for($periodo)->enEspera('Motivo.')->count(3)->create();
    Reunion::factory()->for($this->proyecto)->count(3)->create();
    $periodoCerrado = Periodo::factory()->for($this->proyecto)->cerrado()->create();
    Pago::factory()->for($periodoCerrado)->create();

    // La recarga parcial de Inertia exige que `X-Inertia-Version` coincida con
    // la del servidor o responde 409 (pide recargar la página entera); en vez
    // de reconstruir ese protocolo a mano, se reutiliza `loadDeferredProps` de
    // la respuesta ya cargada, que ya sabe negociarlo. `DB::enableQueryLog()`
    // se activa **después** de la petición inicial, así que solo cuenta las
    // consultas de cada recarga parcial, una por bloque.
    $respuesta = $this->actingAs($this->lider)->get(route('dashboard'));
    $respuesta->assertOk();

    foreach (['trabajo', 'periodos', 'reuniones', 'ingresos'] as $prop) {
        $consultas = consultasDe(fn () => $respuesta->assertInertia(
            fn (Assert $pagina) => $pagina->loadDeferredProps($prop, fn (Assert $diferido) => $diferido->has($prop)),
        ));

        // Sesión, usuario, permisos, y las consultas propias del bloque. El
        // techo es holgado a propósito, igual que en `ArbolDeModulosTest` y
        // `TableroTest`: lo que importa es que no crezca con el número de
        // filas, no clavar un número exacto.
        expect($consultas)->toBeLessThanOrEqual(12);
    }
});
