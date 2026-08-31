<?php

declare(strict_types=1);

use App\Enums\EstadoTarea;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Cliente;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->administrador = usuarioCon(RolGlobal::Administrador);
    $this->miembro = usuarioCon(RolGlobal::Miembro);
    $this->invitado = usuarioCon(RolGlobal::Invitado);
});

it('lista los clientes a un miembro', function () {
    Cliente::factory()->create(['nombre' => 'Distribuidora El Roble S.A.S.']);

    $this->actingAs($this->miembro)
        ->get(route('clientes.index'))
        ->assertOk();
});

it('niega el catálogo de clientes al invitado global', function () {
    $this->actingAs($this->invitado)
        ->get(route('clientes.index'))
        ->assertForbidden();
});

it('filtra el listado por nombre a través de query-builder', function () {
    Cliente::factory()->create(['nombre' => 'Textiles Bonanza Ltda.']);
    Cliente::factory()->create(['nombre' => 'Clínica Santa Marta S.A.']);

    $respuesta = $this->actingAs($this->miembro)
        ->get(route('clientes.index', ['filter' => ['nombre' => 'Textiles']]));

    $respuesta->assertOk();

    $nombres = collect($respuesta->viewData('page')['props']['clientes']['data'])->pluck('nombre')->all();

    expect($nombres)->toBe(['Textiles Bonanza Ltda.']);
});

it('filtra el listado por activo', function () {
    Cliente::factory()->create(['nombre' => 'Vigente S.A.S.']);
    Cliente::factory()->inactivo()->create(['nombre' => 'Retirado Ltda.']);

    $respuesta = $this->actingAs($this->miembro)
        ->get(route('clientes.index', ['filter' => ['activo' => '0']]));

    $nombres = collect($respuesta->viewData('page')['props']['clientes']['data'])->pluck('nombre')->all();

    expect($nombres)->toBe(['Retirado Ltda.']);
});

it('deja al administrador crear un cliente', function () {
    $this->actingAs($this->administrador)
        ->post(route('clientes.store'), [
            'nombre' => 'Agrícola La Esperanza S.A.S.',
            'contacto_nombre' => 'Ana Ruiz',
            'contacto_email' => 'ana@esperanza.co',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('clientes', [
        'nombre' => 'Agrícola La Esperanza S.A.S.',
        'activo' => true,
    ]);
});

it('niega a un miembro crear o editar clientes: solo los ve', function () {
    $cliente = Cliente::factory()->create();

    $this->actingAs($this->miembro)
        ->post(route('clientes.store'), ['nombre' => 'Intento Ltda.'])
        ->assertForbidden();

    $this->actingAs($this->miembro)
        ->get(route('clientes.edit', $cliente))
        ->assertForbidden();

    $this->actingAs($this->miembro)
        ->put(route('clientes.update', $cliente), ['nombre' => 'Renombrado'])
        ->assertForbidden();

    // Pero el detalle sí lo alcanza.
    $this->actingAs($this->miembro)
        ->get(route('clientes.show', $cliente))
        ->assertOk();
});

it('exige nombre al crear un cliente', function () {
    $this->actingAs($this->administrador)
        ->post(route('clientes.store'), ['nombre' => ''])
        ->assertSessionHasErrors('nombre');
});

it('actualiza un cliente', function () {
    $cliente = Cliente::factory()->create(['nombre' => 'Antes S.A.S.']);

    $this->actingAs($this->administrador)
        ->put(route('clientes.update', $cliente), [
            'nombre' => 'Después S.A.S.',
            'contacto_email' => 'contacto@despues.co',
        ])
        ->assertRedirect(route('clientes.show', $cliente));

    expect($cliente->fresh()->nombre)->toBe('Después S.A.S.');
});

it('desactiva y reactiva un cliente en lugar de borrarlo', function () {
    $cliente = Cliente::factory()->create();

    $this->actingAs($this->administrador)
        ->put(route('clientes.estado.update', $cliente))
        ->assertRedirect();

    expect($cliente->fresh()->activo)->toBeFalse();

    $this->actingAs($this->administrador)
        ->put(route('clientes.estado.update', $cliente));

    expect($cliente->fresh()->activo)->toBeTrue()
        // Nunca desaparece: sus proyectos y su historial cuelgan de él.
        ->and(Cliente::query()->whereKey($cliente->id)->exists())->toBeTrue();
});

it('no ofrece un cliente inactivo al crear un proyecto', function () {
    $activo = Cliente::factory()->create(['nombre' => 'Vigente S.A.S.']);
    $inactivo = Cliente::factory()->inactivo()->create(['nombre' => 'Retirado Ltda.']);

    $respuesta = $this->actingAs($this->administrador)->get(route('proyectos.create'));

    $respuesta->assertOk();

    $ids = collect($respuesta->viewData('page')['props']['clientes'])->pluck('id')->all();

    expect($ids)->toContain($activo->id)
        ->and($ids)->not->toContain($inactivo->id);
});

it('rechaza crear un proyecto para un cliente desactivado aunque se envíe su id', function () {
    $inactivo = Cliente::factory()->inactivo()->create();

    $this->actingAs($this->administrador)
        ->post(route('proyectos.store'), [
            'cliente_id' => $inactivo->id,
            'nombre' => 'Portal por la puerta de atrás',
            'prefijo_tarea' => 'PUE',
            'estado' => 'activo',
        ])
        ->assertSessionHasErrors('cliente_id');

    $this->assertDatabaseMissing('proyectos', ['prefijo_tarea' => 'PUE']);
});

it('resume el cliente con proyectos activos, tareas abiertas y último periodo cerrado', function () {
    $cliente = Cliente::factory()->create();

    $activo = Proyecto::factory()->for($cliente)->create();
    Proyecto::factory()->for($cliente)->finalizado()->create();

    Tarea::factory()->for($activo)->create(['estado' => EstadoTarea::Pendiente]);
    Tarea::factory()->for($activo)->create(['estado' => EstadoTarea::EnProceso]);
    // Terminal: no cuenta como abierta.
    Tarea::factory()->for($activo)->create(['estado' => EstadoTarea::Entregada]);

    $periodo = Periodo::factory()->for($activo)->cerrado()->create([
        'consecutivo' => 3,
        'fecha_fin' => now()->subMonth()->toDateString(),
    ]);
    Pago::factory()->for($periodo)->create();

    $respuesta = $this->actingAs($this->administrador)->get(route('clientes.show', $cliente));

    $resumen = $respuesta->viewData('page')['props']['resumen'];

    expect($resumen['proyectos_activos'])->toBe(1)
        ->and($resumen['tareas_abiertas'])->toBe(2)
        ->and($resumen['ultimo_periodo_cerrado']['consecutivo'])->toBe(3);
});

it('no reporta periodo cerrado cuando el cliente no tiene ninguno', function () {
    $cliente = Cliente::factory()->create();
    Proyecto::factory()->for($cliente)->create();

    $respuesta = $this->actingAs($this->administrador)->get(route('clientes.show', $cliente));

    expect($respuesta->viewData('page')['props']['resumen']['ultimo_periodo_cerrado'])->toBeNull();
});

it('deja fuera del listado de proyectos del miembro los que no son suyos', function () {
    // El catálogo de clientes es global, pero los proyectos no: el listado se
    // acota al pivote.
    $mio = Proyecto::factory()->create();
    $ajeno = Proyecto::factory()->create();

    asignarAProyecto($this->miembro, $mio, RolProyecto::Colaborador);

    $respuesta = $this->actingAs($this->miembro)->get(route('proyectos.index'));

    $ids = collect($respuesta->viewData('page')['props']['proyectos']['data'])->pluck('id')->all();

    expect($ids)->toBe([$mio->id])
        ->and($ids)->not->toContain($ajeno->id);
});
