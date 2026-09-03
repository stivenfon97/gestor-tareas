<?php

declare(strict_types=1);

use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Enums\TipoReunion;
use App\Enums\Visibilidad;
use App\Models\Proyecto;
use App\Models\Reunion;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);

    $this->colaborador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->colaborador, $this->proyecto, RolProyecto::Colaborador);

    $this->observador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->observador, $this->proyecto, RolProyecto::Observador);

    $this->cliente = usuarioCon(RolGlobal::Invitado);
    asignarAProyecto($this->cliente, $this->proyecto, RolProyecto::Cliente);
});

it('un líder crea una reunión con sus asistentes', function () {
    $datos = datosDeReunionValidos([
        'asistentes' => [
            ['usuario_id' => $this->colaborador->id, 'nombre' => $this->colaborador->name, 'organizacion' => null],
            ['usuario_id' => null, 'nombre' => 'Contacto del cliente', 'organizacion' => 'El Roble S.A.S.'],
        ],
    ]);

    $respuesta = $this->actingAs($this->lider)
        ->post(route('proyectos.reuniones.store', $this->proyecto), $datos);

    $reunion = Reunion::query()->sole();
    $respuesta->assertRedirect(route('reuniones.show', $reunion));

    expect($reunion->titulo)->toBe('Reunión de prueba')
        ->and($reunion->proyecto_id)->toBe($this->proyecto->id)
        ->and($reunion->creado_por)->toBe($this->lider->id)
        ->and($reunion->asistentes)->toHaveCount(2);
});

it('un colaborador (escribe) puede crear reuniones; un observador (solo lectura) no', function () {
    $this->actingAs($this->colaborador)
        ->post(route('proyectos.reuniones.store', $this->proyecto), datosDeReunionValidos())
        ->assertRedirect();

    expect(Reunion::query()->count())->toBe(1);

    $this->actingAs($this->observador)
        ->post(route('proyectos.reuniones.store', $this->proyecto), datosDeReunionValidos(['titulo' => 'Otra']))
        ->assertForbidden();

    $this->actingAs($this->cliente)
        ->get(route('proyectos.reuniones.create', $this->proyecto))
        ->assertForbidden();

    expect(Reunion::query()->count())->toBe(1);
});

it('rechaza una reunión sin ningún asistente', function () {
    $this->actingAs($this->lider)
        ->post(route('proyectos.reuniones.store', $this->proyecto), datosDeReunionValidos(['asistentes' => []]))
        ->assertInvalid(['asistentes']);
});

it('rechaza un usuario_id de asistente que no participa en el proyecto', function () {
    $ajeno = usuarioCon(RolGlobal::Miembro);

    $this->actingAs($this->lider)
        ->post(
            route('proyectos.reuniones.store', $this->proyecto),
            datosDeReunionValidos([
                'asistentes' => [['usuario_id' => $ajeno->id, 'nombre' => $ajeno->name, 'organizacion' => null]],
            ]),
        )
        ->assertInvalid(['asistentes.0.usuario_id']);
});

it('edit trae el contenido_json completo para reabrir el editor', function () {
    $reunion = Reunion::factory()->for($this->proyecto)->create();

    $this->actingAs($this->lider)
        ->get(route('reuniones.edit', $reunion))
        ->assertOk()
        ->assertInertia(fn (Assert $pagina) => $pagina
            ->component('reuniones/Editar')
            ->where('reunion.id', $reunion->id)
            ->has('reunion.contenido_json'));
});

it('actualiza una reunión y reemplaza la lista de asistentes', function () {
    $reunion = Reunion::factory()->for($this->proyecto)->create();
    $reunion->asistentes()->create(['nombre' => 'Asistente original']);

    $datos = datosDeReunionValidos([
        'titulo' => 'Título actualizado',
        'asistentes' => [['usuario_id' => null, 'nombre' => 'Asistente nuevo', 'organizacion' => null]],
    ]);

    $this->actingAs($this->lider)
        ->put(route('reuniones.update', $reunion), $datos)
        ->assertRedirect(route('reuniones.show', $reunion));

    $reunion->refresh();

    expect($reunion->titulo)->toBe('Título actualizado')
        ->and($reunion->asistentes)->toHaveCount(1)
        ->and($reunion->asistentes->first()->nombre)->toBe('Asistente nuevo');
});

it('solo quien gestiona el proyecto (líder) puede eliminar una reunión, no un colaborador', function () {
    $reunion = Reunion::factory()->for($this->proyecto)->create();

    $this->actingAs($this->colaborador)
        ->delete(route('reuniones.destroy', $reunion))
        ->assertForbidden();

    $this->actingAs($this->lider)
        ->delete(route('reuniones.destroy', $reunion))
        ->assertRedirect(route('proyectos.reuniones.index', $this->proyecto));

    expect(Reunion::query()->find($reunion->id))->toBeNull();
});

it('el asistente conserva su nombre cuando se borra el usuario asociado', function () {
    $reunion = Reunion::factory()->for($this->proyecto)->create();
    $usuario = User::factory()->create(['name' => 'Marcela Ríos']);

    $asistente = $reunion->asistentes()->create([
        'usuario_id' => $usuario->id,
        'nombre' => 'Marcela Ríos',
    ]);

    $usuario->delete();

    expect($asistente->fresh())
        ->usuario_id->toBeNull()
        ->nombre->toBe('Marcela Ríos');
});

it('filtra el listado por tipo y por rango de fechas', function () {
    Reunion::factory()->for($this->proyecto)->create(['tipo' => TipoReunion::Llamada, 'fecha' => '2026-06-10 10:00:00', 'titulo' => 'Llamada de junio']);
    Reunion::factory()->for($this->proyecto)->create(['tipo' => TipoReunion::Reunion, 'fecha' => '2026-07-10 10:00:00', 'titulo' => 'Reunión de julio']);

    $respuesta = $this->actingAs($this->lider)->get(
        route('proyectos.reuniones.index', $this->proyecto).'?filter[tipo]=llamada',
    );

    $titulos = collect($respuesta->viewData('page')['props']['reuniones'])->pluck('titulo')->all();

    expect($titulos)->toBe(['Llamada de junio']);

    $porFecha = $this->actingAs($this->lider)->get(
        route('proyectos.reuniones.index', $this->proyecto).'?filter[desde]=2026-07-01&filter[hasta]=2026-07-31',
    );

    $titulosPorFecha = collect($porFecha->viewData('page')['props']['reuniones'])->pluck('titulo')->all();

    expect($titulosPorFecha)->toBe(['Reunión de julio']);
});

it('la reunión creada nace visibilidad interna por defecto en el formulario, y solo el cliente marcado la ve', function () {
    $interna = Reunion::factory()->for($this->proyecto)->create(['visibilidad' => Visibilidad::Interna]);
    $compartida = Reunion::factory()->for($this->proyecto)->create(['visibilidad' => Visibilidad::Cliente]);

    $this->actingAs($this->cliente)->get(route('reuniones.show', $interna))->assertForbidden();
    $this->actingAs($this->cliente)->get(route('reuniones.show', $compartida))->assertOk();
});
