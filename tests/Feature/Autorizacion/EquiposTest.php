<?php

declare(strict_types=1);

use App\Actions\AsignarEquipoAProyecto;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Equipo;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\ResolverRolProyecto;
use Database\Seeders\RolesYPermisosSeeder;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->administrador = usuarioCon(RolGlobal::Administrador);
    $this->proyecto = Proyecto::factory()->create();
    $this->equipo = Equipo::factory()->create();

    $this->integrantes = User::factory()->count(3)->create(['activo' => true]);
    $this->equipo->usuarios()->attach($this->integrantes->pluck('id'));
});

it('asignar un equipo crea una fila de pivote por integrante', function () {
    $resumen = app(AsignarEquipoAProyecto::class)(
        $this->equipo,
        $this->proyecto,
        RolProyecto::Colaborador,
    );

    expect($resumen['agregados'])->toHaveCount(3)
        ->and($resumen['omitidos'])->toBeEmpty()
        ->and($this->proyecto->usuarios()->count())->toBe(3);

    foreach ($this->integrantes as $integrante) {
        expect(ResolverRolProyecto::para($integrante, $this->proyecto))->toBe(RolProyecto::Colaborador);
    }
});

it('asignarlo dos veces no duplica filas', function () {
    $accion = app(AsignarEquipoAProyecto::class);

    $accion($this->equipo, $this->proyecto, RolProyecto::Colaborador);
    $segundo = $accion($this->equipo, $this->proyecto, RolProyecto::Colaborador);

    expect($segundo['agregados'])->toBeEmpty()
        ->and($segundo['omitidos'])->toHaveCount(3)
        ->and($this->proyecto->usuarios()->count())->toBe(3);
});

it('no sobrescribe el rol de quien ya participaba', function () {
    $ascendido = $this->integrantes->first();
    asignarAProyecto($ascendido, $this->proyecto, RolProyecto::Lider);

    $resumen = app(AsignarEquipoAProyecto::class)(
        $this->equipo,
        $this->proyecto,
        RolProyecto::Colaborador,
    );

    expect(ResolverRolProyecto::para($ascendido, $this->proyecto))->toBe(RolProyecto::Lider)
        ->and($resumen['agregados'])->toHaveCount(2)
        ->and($resumen['omitidos'])->toHaveCount(1)
        ->and($resumen['omitidos'][0]['rol'])->toBe(RolProyecto::Lider->value);
});

it('quitar a alguien del equipo no lo saca del proyecto', function () {
    $accion = app(AsignarEquipoAProyecto::class);
    $accion($this->equipo, $this->proyecto, RolProyecto::Colaborador);

    $integrante = $this->integrantes->first();
    $this->equipo->usuarios()->detach($integrante->getKey());

    expect($this->equipo->usuarios()->count())->toBe(2)
        ->and(ResolverRolProyecto::para($integrante, $this->proyecto))->toBe(RolProyecto::Colaborador);
});

it('expone la acción por HTTP solo a quien gestiona los miembros', function () {
    $colaborador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($colaborador, $this->proyecto, RolProyecto::Colaborador);

    $this->actingAs($colaborador)
        ->post(route('proyectos.equipos.store', $this->proyecto), [
            'equipo_id' => $this->equipo->id,
            'rol' => RolProyecto::Colaborador->value,
        ])
        ->assertForbidden();

    $this->actingAs($this->administrador)
        ->post(route('proyectos.equipos.store', $this->proyecto), [
            'equipo_id' => $this->equipo->id,
            'rol' => RolProyecto::Colaborador->value,
        ])
        ->assertRedirect();

    expect($this->proyecto->usuarios()->count())->toBe(4);
});

it('un miembro no puede administrar equipos', function () {
    $miembro = usuarioCon(RolGlobal::Miembro);

    $this->actingAs($miembro)->get(route('equipos.index'))->assertForbidden();
    $this->actingAs($miembro)->get(route('equipos.create'))->assertForbidden();
    $this->actingAs($miembro)
        ->post(route('equipos.store'), ['nombre' => 'Nuevo'])
        ->assertForbidden();
});

it('el administrador administra equipos y sus miembros', function () {
    $this->actingAs($this->administrador)->get(route('equipos.index'))->assertOk();

    $this->actingAs($this->administrador)
        ->post(route('equipos.store'), ['nombre' => 'Infraestructura'])
        ->assertRedirect(route('equipos.index'));

    expect(Equipo::query()->where('nombre', 'Infraestructura')->exists())->toBeTrue();
});

/* -------------------------------------------- listado servido a TablaDatos */

it('el listado de equipos deja llegar a los que quedan en la página siguiente', function () {
    // Sin factory: la suya sortea entre cinco nombres únicos y se agota antes.
    foreach (range(1, 15) as $numero) {
        Equipo::query()->create(['nombre' => "Equipo de prueba {$numero}"]);
    }

    $paginador = $this->actingAs($this->administrador)
        ->get(route('equipos.index', ['por_pagina' => 15, 'page' => 2]))
        ->assertOk()
        ->viewData('page')['props']['equipos'];

    expect($paginador['per_page'])->toBe(15)
        ->and($paginador['last_page'])->toBe(2)
        ->and($paginador['total'])->toBe(16)
        ->and($paginador['data'])->toHaveCount(1);
});

it('el listado de equipos filtra por nombre y devuelve el filtro a la tabla', function () {
    Equipo::factory()->create(['nombre' => 'Zeta Soporte']);

    $props = $this->actingAs($this->administrador)
        ->get(route('equipos.index', ['filter' => ['nombre' => 'zeta sop']]))
        ->assertOk()
        ->viewData('page')['props'];

    expect(array_column($props['equipos']['data'], 'nombre'))->toBe(['Zeta Soporte'])
        ->and($props['filtros'])->toBe(['nombre' => 'zeta sop']);
});

it('el listado de equipos ordena y devuelve el estado de la URL a la tabla', function () {
    $this->equipo->update(['nombre' => 'Alfa']);
    Equipo::factory()->create(['nombre' => 'Zeta']);

    $props = $this->actingAs($this->administrador)
        ->get(route('equipos.index', ['sort' => '-nombre', 'columnas' => 'nombre']))
        ->assertOk()
        ->viewData('page')['props'];

    expect(array_column($props['equipos']['data'], 'nombre'))->toBe(['Zeta', 'Alfa'])
        ->and($props['orden'])->toBe([['campo' => 'nombre', 'direccion' => 'desc']])
        ->and($props['columnasVisibles'])->toBe(['nombre']);
});

it('la búsqueda del listado de equipos recorre también la descripción', function () {
    $this->equipo->update(['nombre' => 'Alfa', 'descripcion' => 'Atiende incidencias de producción']);
    Equipo::factory()->create(['nombre' => 'Zeta', 'descripcion' => 'Diseña pantallas']);

    $props = $this->actingAs($this->administrador)
        ->get(route('equipos.index', ['filter' => ['buscar' => 'incidencias']]))
        ->assertOk()
        ->viewData('page')['props'];

    expect(array_column($props['equipos']['data'], 'nombre'))->toBe(['Alfa']);
});
