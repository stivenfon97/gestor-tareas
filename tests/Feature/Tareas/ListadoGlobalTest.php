<?php

declare(strict_types=1);

use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Proyecto;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;

/**
 * Listado global de tareas (`tareas.index`).
 *
 * Es la pantalla que más se usa y hasta la fase 07b no existía como destino:
 * había que entrar a un proyecto y buscar su pestaña. Lo acota
 * `Tarea::visiblesPara()`, **no** una policy: `viewAny` pregunta por un
 * proyecto y aquí no hay uno solo.
 */
beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->mio = Proyecto::factory()->create(['nombre' => 'Proyecto propio']);
    $this->ajeno = Proyecto::factory()->create(['nombre' => 'Proyecto ajeno']);

    $this->miembro = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->miembro, $this->mio, RolProyecto::Colaborador);
});

/** @return array<int, string> */
function codigosDelListadoGlobal($respuesta): array
{
    return array_column($respuesta->viewData('page')['props']['tareas']['data'], 'codigo');
}

it('solo muestra tareas de proyectos donde el usuario participa', function () {
    $propia = Tarea::factory()->for($this->mio, 'proyecto')->create();
    $ajena = Tarea::factory()->for($this->ajeno, 'proyecto')->create();

    $respuesta = $this->actingAs($this->miembro)->get(route('tareas.index'));

    $respuesta->assertOk();

    expect(codigosDelListadoGlobal($respuesta))
        ->toContain($propia->codigo)
        ->not->toContain($ajena->codigo);
});

it('el administrador ve las tareas de todos los proyectos', function () {
    $propia = Tarea::factory()->for($this->mio, 'proyecto')->create();
    $ajena = Tarea::factory()->for($this->ajeno, 'proyecto')->create();

    $respuesta = $this->actingAs(usuarioCon(RolGlobal::Administrador))
        ->get(route('tareas.index'));

    expect(codigosDelListadoGlobal($respuesta))
        ->toContain($propia->codigo)
        ->toContain($ajena->codigo);
});

it('ordena por lo último tocado, no por código', function () {
    $antigua = Tarea::factory()->for($this->mio, 'proyecto')->create();
    $reciente = Tarea::factory()->for($this->mio, 'proyecto')->create();

    // `updated_at` es lo que ordena, y hay que fijarlo a mano: las dos se
    // crean en el mismo segundo.
    $antigua->forceFill(['updated_at' => now()->subDay()])->saveQuietly();
    $reciente->forceFill(['updated_at' => now()])->saveQuietly();

    $respuesta = $this->actingAs($this->miembro)->get(route('tareas.index'));

    expect(codigosDelListadoGlobal($respuesta))
        ->toBe([$reciente->codigo, $antigua->codigo]);

    // Y el estado que recibe la tabla lo dice, para que la cabecera pinte la
    // flecha correcta: sin esto, la columna se vería sin ordenar.
    expect($respuesta->viewData('page')['props']['orden'])
        ->toBe([['campo' => 'updated_at', 'direccion' => 'desc']]);
});

it('filtra por proyecto', function () {
    $otroMio = Proyecto::factory()->create();
    asignarAProyecto($this->miembro, $otroMio, RolProyecto::Colaborador);

    $aqui = Tarea::factory()->for($this->mio, 'proyecto')->create();
    $alla = Tarea::factory()->for($otroMio, 'proyecto')->create();

    $respuesta = $this->actingAs($this->miembro)
        ->get(route('tareas.index', ['filter' => ['proyecto_id' => $this->mio->getKey()]]));

    expect(codigosDelListadoGlobal($respuesta))
        ->toBe([$aqui->codigo])
        ->not->toContain($alla->codigo);
});

it('un filtro por un proyecto ajeno no saca ni una tarea de él', function () {
    Tarea::factory()->for($this->ajeno, 'proyecto')->create();

    $respuesta = $this->actingAs($this->miembro)
        ->get(route('tareas.index', ['filter' => ['proyecto_id' => $this->ajeno->getKey()]]));

    // El scope va antes que el filtro: pedir explícitamente un proyecto ajeno
    // devuelve vacío, no sus tareas.
    expect(codigosDelListadoGlobal($respuesta))->toBe([]);
});

it('el desplegable de proyectos solo ofrece los del usuario', function () {
    $respuesta = $this->actingAs($this->miembro)->get(route('tareas.index'));

    expect(array_column($respuesta->viewData('page')['props']['opciones']['proyectos'], 'nombre'))
        ->toBe(['Proyecto propio']);
});

it('exige sesión', function () {
    $this->get(route('tareas.index'))->assertRedirect(route('login'));
});
