<?php

declare(strict_types=1);

use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\NotaTarea;
use App\Models\Proyecto;
use App\Models\Reunion;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;

/**
 * El rol global `invitado` implica siempre `veLoInterno() === false`, sea cual
 * sea su rol de proyecto.
 *
 * Antes de la fase 05, la contención dependía solo del rol de proyecto: un
 * `invitado` con rol de proyecto `lider` leía las reuniones y las notas
 * internas. Que no viera los pagos ocurría de rebote, porque `invitado` carece
 * del permiso `pagos.ver` — y una cerradura que funciona por casualidad no es
 * una cerradura.
 */
beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();
});

it('no muestra al invitado global con rol lider las reuniones internas, ni en listado ni en detalle', function () {
    $invitado = usuarioCon(RolGlobal::Invitado);
    asignarAProyecto($invitado, $this->proyecto, RolProyecto::Lider);

    $interna = Reunion::factory()->for($this->proyecto)->create(['titulo' => 'Comité interno de márgenes']);
    $compartida = Reunion::factory()->for($this->proyecto)->visibleParaCliente()->create(['titulo' => 'Revisión con el cliente']);

    $respuesta = $this->actingAs($invitado)
        ->get(route('proyectos.reuniones.index', $this->proyecto));

    $respuesta->assertOk();

    $titulos = collect($respuesta->viewData('page')['props']['reuniones'])->pluck('titulo')->all();

    expect($titulos)->toBe([$compartida->titulo])
        ->and($titulos)->not->toContain($interna->titulo);

    // Y por id tampoco: el título ya es información filtrada, pero el detalle
    // lleva además el contenido y los acuerdos.
    $this->actingAs($invitado)
        ->get(route('reuniones.show', $interna))
        ->assertForbidden();

    $this->actingAs($invitado)
        ->get(route('reuniones.show', $compartida))
        ->assertOk();
});

it('no muestra al invitado global con rol colaborador las notas internas', function () {
    $invitado = usuarioCon(RolGlobal::Invitado);
    asignarAProyecto($invitado, $this->proyecto, RolProyecto::Colaborador);

    $tarea = Tarea::factory()->for($this->proyecto)->create();

    $interna = NotaTarea::factory()->for($tarea)->create(['contenido' => 'Margen real del periodo']);
    $compartida = NotaTarea::factory()->for($tarea)->visibleParaCliente()->create(['contenido' => 'Desplegado en pruebas']);

    $respuesta = $this->actingAs($invitado)->get(route('tareas.show', $tarea));

    $respuesta->assertOk();

    $contenidos = collect($respuesta->viewData('page')['props']['notas'])->pluck('contenido')->all();

    expect($contenidos)->toBe([$compartida->contenido])
        ->and($contenidos)->not->toContain($interna->contenido);
});

it('sí muestra lo interno a un miembro global con rol observador: es interno, solo que de lectura', function () {
    $observador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($observador, $this->proyecto, RolProyecto::Observador);

    $reunion = Reunion::factory()->for($this->proyecto)->create(['titulo' => 'Comité interno de márgenes']);

    $tarea = Tarea::factory()->for($this->proyecto)->create();
    $nota = NotaTarea::factory()->for($tarea)->create(['contenido' => 'Margen real del periodo']);

    $reuniones = $this->actingAs($observador)
        ->get(route('proyectos.reuniones.index', $this->proyecto));

    $reuniones->assertOk();

    expect(collect($reuniones->viewData('page')['props']['reuniones'])->pluck('titulo')->all())
        ->toContain($reunion->titulo);

    $notas = $this->actingAs($observador)->get(route('tareas.show', $tarea));

    $notas->assertOk();

    expect(collect($notas->viewData('page')['props']['notas'])->pluck('contenido')->all())
        ->toContain($nota->contenido);

    $this->actingAs($observador)
        ->get(route('reuniones.show', $reunion))
        ->assertOk();
});

it('un invitado global no ve lo interno de ningún proyecto suyo, tenga el rol de proyecto que tenga', function () {
    $invitado = usuarioCon(RolGlobal::Invitado);

    $otro = Proyecto::factory()->create();

    asignarAProyecto($invitado, $this->proyecto, RolProyecto::Lider);
    asignarAProyecto($invitado, $otro, RolProyecto::Observador);

    $aqui = Reunion::factory()->for($this->proyecto)->create();
    $alla = Reunion::factory()->for($otro)->create();

    $visibles = Reunion::query()->visiblesPara($invitado->fresh())->pluck('id')->all();

    expect($visibles)->not->toContain($aqui->id)
        ->and($visibles)->not->toContain($alla->id);
});
