<?php

declare(strict_types=1);

use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Modulo;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Reunion;
use App\Models\Tarea;
use App\Models\User;
use App\Services\ResolverRolProyecto;
use Database\Seeders\RolesYPermisosSeeder;

/**
 * Gana siempre el rol más restrictivo entre el global y el de proyecto.
 *
 * Es la regla que evita el agujero: si alguien asigna por error el rol de
 * proyecto equivocado a una persona del cliente, el rol global la contiene.
 */
beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();
});

it('un invitado global con rol de proyecto lider no puede escribir', function () {
    $invitado = usuarioCon(RolGlobal::Invitado);
    asignarAProyecto($invitado, $this->proyecto, RolProyecto::Lider);

    $tarea = Tarea::factory()->for($this->proyecto)->create();
    $modulo = Modulo::factory()->for($this->proyecto)->create();
    $periodo = Periodo::factory()->for($this->proyecto)->cerrado()->create();
    $pago = Pago::factory()->for($periodo)->create();
    $reunion = Reunion::factory()->for($this->proyecto)->create();

    expect($invitado->can('update', $this->proyecto))->toBeFalse()
        ->and($invitado->can('create', [Tarea::class, $this->proyecto]))->toBeFalse()
        ->and($invitado->can('update', $tarea))->toBeFalse()
        ->and($invitado->can('cambiarEstado', $tarea))->toBeFalse()
        ->and($invitado->can('update', $modulo))->toBeFalse()
        ->and($invitado->can('cerrar', $periodo))->toBeFalse()
        ->and($invitado->can('update', $pago))->toBeFalse()
        ->and($invitado->can('update', $reunion))->toBeFalse()
        ->and($invitado->can('gestionarMiembros', $this->proyecto))->toBeFalse();
});

it('el invitado global con rol lider entra al proyecto pero no ve lo interno', function () {
    $invitado = usuarioCon(RolGlobal::Invitado);
    asignarAProyecto($invitado, $this->proyecto, RolProyecto::Lider);

    // Participa: el proyecto y su tablero de tareas los ve.
    expect($invitado->can('view', $this->proyecto))->toBeTrue()
        ->and($invitado->can('viewAny', [Tarea::class, $this->proyecto]))->toBeTrue();

    // Lo interno, no. Ser del cliente es condición de la persona, no del
    // proyecto: el rol global manda por encima del rol de proyecto.
    expect(ResolverRolProyecto::veLoInterno($invitado, $this->proyecto))->toBeFalse();

    $this->actingAs($invitado)
        ->get(route('proyectos.show', $this->proyecto))
        ->assertOk();
});

it('el resolver niega la escritura al invitado y la concede al miembro con el mismo rol de proyecto', function () {
    $invitado = usuarioCon(RolGlobal::Invitado);
    $miembro = usuarioCon(RolGlobal::Miembro);

    asignarAProyecto($invitado, $this->proyecto, RolProyecto::Lider);
    asignarAProyecto($miembro, $this->proyecto, RolProyecto::Lider);

    expect(ResolverRolProyecto::para($invitado, $this->proyecto))->toBe(RolProyecto::Lider)
        ->and(ResolverRolProyecto::escribe($invitado, $this->proyecto))->toBeFalse()
        ->and(ResolverRolProyecto::gestiona($invitado, $this->proyecto))->toBeFalse()
        ->and(ResolverRolProyecto::escribe($miembro, $this->proyecto))->toBeTrue()
        ->and(ResolverRolProyecto::gestiona($miembro, $this->proyecto))->toBeTrue();
});

it('un miembro global con rol de proyecto cliente tampoco ve los pagos', function () {
    // La restricción también funciona al revés: el techo global es amplio, pero
    // el rol de proyecto lo recorta.
    $miembro = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($miembro, $this->proyecto, RolProyecto::Cliente);

    $this->actingAs($miembro)
        ->get(route('proyectos.pagos.index', $this->proyecto))
        ->assertForbidden();
});

it('un usuario sin rol global no puede nada, aunque tenga rol de proyecto', function () {
    $sinRol = User::factory()->create(['activo' => true]);
    asignarAProyecto($sinRol, $this->proyecto, RolProyecto::Lider);

    expect($sinRol->can('viewAny', Proyecto::class))->toBeFalse()
        ->and($sinRol->can('create', [Tarea::class, $this->proyecto]))->toBeFalse()
        // Tampoco leer: sin rol global no hay techo asignado, y el valor
        // seguro por defecto es ninguno.
        ->and($sinRol->can('view', $this->proyecto))->toBeFalse()
        ->and($sinRol->can('viewAny', [Tarea::class, $this->proyecto]))->toBeFalse();

    $this->actingAs($sinRol)
        ->get(route('proyectos.show', $this->proyecto))
        ->assertForbidden();
});

it('el administrador pasa por encima de las policies', function () {
    $administrador = usuarioCon(RolGlobal::Administrador);
    $tarea = Tarea::factory()->for($this->proyecto)->create();

    expect($administrador->can('update', $tarea))->toBeTrue()
        ->and($administrador->can('update', $this->proyecto))->toBeTrue();
});
