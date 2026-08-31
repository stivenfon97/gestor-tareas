<?php

declare(strict_types=1);

use App\Actions\ReordenarModulos;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Exceptions\CicloEnElArbol;
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

    $this->colaborador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->colaborador, $this->proyecto, RolProyecto::Colaborador);
});

it('crea un módulo raíz', function () {
    $this->actingAs($this->lider)
        ->post(route('proyectos.modulos.store', $this->proyecto), ['nombre' => 'Clientes'])
        ->assertRedirect();

    $this->assertDatabaseHas('modulos', [
        'proyecto_id' => $this->proyecto->id,
        'nombre' => 'Clientes',
        'parent_id' => null,
    ]);
});

it('crea un submódulo colgado de otro', function () {
    $padre = Modulo::factory()->for($this->proyecto)->create(['nombre' => 'Clientes']);

    $this->actingAs($this->lider)
        ->post(route('proyectos.modulos.store', $this->proyecto), [
            'nombre' => 'Facturación',
            'parent_id' => $padre->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('modulos', [
        'nombre' => 'Facturación',
        'parent_id' => $padre->id,
    ]);
});

it('coloca el módulo nuevo al final de sus hermanos', function () {
    Modulo::factory()->for($this->proyecto)->create(['orden' => 0]);
    Modulo::factory()->for($this->proyecto)->create(['orden' => 7]);

    $this->actingAs($this->lider)
        ->post(route('proyectos.modulos.store', $this->proyecto), ['nombre' => 'Último']);

    expect(Modulo::query()->where('nombre', 'Último')->value('orden'))->toBe(8);
});

it('rechaza colgar un módulo de un padre de otro proyecto', function () {
    $ajeno = Modulo::factory()->for(Proyecto::factory()->create())->create();

    $this->actingAs($this->lider)
        ->post(route('proyectos.modulos.store', $this->proyecto), [
            'nombre' => 'Intruso',
            'parent_id' => $ajeno->id,
        ])
        ->assertSessionHasErrors('parent_id');
});

it('renombra un módulo en línea', function () {
    $modulo = Modulo::factory()->for($this->proyecto)->create(['nombre' => 'Antes']);

    $this->actingAs($this->lider)
        ->put(route('modulos.update', $modulo), ['nombre' => 'Después'])
        ->assertRedirect();

    expect($modulo->fresh()->nombre)->toBe('Después');
});

it('reordena los hermanos de un nivel en una sola petición', function () {
    $primero = Modulo::factory()->for($this->proyecto)->create(['nombre' => 'A', 'orden' => 0]);
    $segundo = Modulo::factory()->for($this->proyecto)->create(['nombre' => 'B', 'orden' => 1]);
    $tercero = Modulo::factory()->for($this->proyecto)->create(['nombre' => 'C', 'orden' => 2]);

    $this->actingAs($this->lider)
        ->put(route('proyectos.modulos.orden', $this->proyecto), [
            'niveles' => [
                ['parent_id' => null, 'modulos' => [$tercero->id, $primero->id, $segundo->id]],
            ],
        ])
        ->assertRedirect();

    $orden = $this->proyecto->modulos()->raiz()->orderBy('orden')->pluck('nombre')->all();

    expect($orden)->toBe(['C', 'A', 'B']);
});

it('mueve un módulo a otro padre y reordena los dos niveles a la vez', function () {
    $origen = Modulo::factory()->for($this->proyecto)->create(['nombre' => 'Origen', 'orden' => 0]);
    $destino = Modulo::factory()->for($this->proyecto)->create(['nombre' => 'Destino', 'orden' => 1]);

    $viajero = Modulo::factory()->hijoDe($origen)->create(['nombre' => 'Viajero', 'orden' => 0]);
    $quedan = Modulo::factory()->hijoDe($origen)->create(['nombre' => 'Se queda', 'orden' => 1]);

    $this->actingAs($this->lider)
        ->put(route('proyectos.modulos.orden', $this->proyecto), [
            'niveles' => [
                ['parent_id' => $origen->id, 'modulos' => [$quedan->id]],
                ['parent_id' => $destino->id, 'modulos' => [$viajero->id]],
            ],
        ])
        ->assertRedirect();

    expect($viajero->fresh()->parent_id)->toBe($destino->id)
        ->and($viajero->fresh()->orden)->toBe(0)
        ->and($quedan->fresh()->parent_id)->toBe($origen->id)
        ->and($quedan->fresh()->orden)->toBe(0);
});

it('sube un submódulo a la raíz', function () {
    $padre = Modulo::factory()->for($this->proyecto)->create(['orden' => 0]);
    $hijo = Modulo::factory()->hijoDe($padre)->create(['orden' => 0]);

    $this->actingAs($this->lider)
        ->put(route('proyectos.modulos.orden', $this->proyecto), [
            'niveles' => [
                ['parent_id' => $padre->id, 'modulos' => []],
                ['parent_id' => null, 'modulos' => [$padre->id, $hijo->id]],
            ],
        ])
        ->assertRedirect();

    expect($hijo->fresh()->parent_id)->toBeNull();
});

it('rechaza mover un módulo dentro de uno de sus propios descendientes', function () {
    $abuelo = Modulo::factory()->for($this->proyecto)->create(['nombre' => 'Abuelo']);
    $padre = Modulo::factory()->hijoDe($abuelo)->create(['nombre' => 'Padre']);
    $nieto = Modulo::factory()->hijoDe($padre)->create(['nombre' => 'Nieto']);

    $this->actingAs($this->lider)
        ->put(route('proyectos.modulos.orden', $this->proyecto), [
            'niveles' => [
                ['parent_id' => $nieto->id, 'modulos' => [$abuelo->id]],
            ],
        ])
        ->assertSessionHasErrors('niveles');

    // Nada cambió: la comprobación ocurre antes de abrir la transacción.
    expect($abuelo->fresh()->parent_id)->toBeNull()
        ->and($padre->fresh()->parent_id)->toBe($abuelo->id)
        ->and($nieto->fresh()->parent_id)->toBe($padre->id);
});

it('rechaza colgar un módulo de sí mismo', function () {
    $modulo = Modulo::factory()->for($this->proyecto)->create();

    $this->actingAs($this->lider)
        ->put(route('proyectos.modulos.orden', $this->proyecto), [
            'niveles' => [
                ['parent_id' => $modulo->id, 'modulos' => [$modulo->id]],
            ],
        ])
        ->assertSessionHasErrors('niveles');

    expect($modulo->fresh()->parent_id)->toBeNull();
});

it('detecta el ciclo que solo aparece al aplicar dos niveles juntos', function () {
    // Ninguno de los dos movimientos crea un ciclo por separado; los dos a la
    // vez, sí. Por eso la acción simula el resultado antes de escribir nada.
    $uno = Modulo::factory()->for($this->proyecto)->create(['nombre' => 'Uno']);
    $dos = Modulo::factory()->for($this->proyecto)->create(['nombre' => 'Dos']);

    expect(fn () => app(ReordenarModulos::class)($this->proyecto, [
        ['parent_id' => $dos->id, 'modulos' => [$uno->id]],
        ['parent_id' => $uno->id, 'modulos' => [$dos->id]],
    ]))->toThrow(CicloEnElArbol::class);

    expect($uno->fresh()->parent_id)->toBeNull()
        ->and($dos->fresh()->parent_id)->toBeNull();
});

it('rechaza reordenar con un módulo de otro proyecto', function () {
    $mio = Modulo::factory()->for($this->proyecto)->create();
    $ajeno = Modulo::factory()->for(Proyecto::factory()->create())->create();

    $this->actingAs($this->lider)
        ->put(route('proyectos.modulos.orden', $this->proyecto), [
            'niveles' => [
                ['parent_id' => null, 'modulos' => [$mio->id, $ajeno->id]],
            ],
        ])
        ->assertSessionHasErrors('niveles.0.modulos.1');

    expect($ajeno->fresh()->proyecto_id)->not->toBe($this->proyecto->id);
});

it('borra un módulo con hijos: los hijos se van, las tareas quedan sin módulo', function () {
    $padre = Modulo::factory()->for($this->proyecto)->create();
    $hijo = Modulo::factory()->hijoDe($padre)->create();
    $nieto = Modulo::factory()->hijoDe($hijo)->create();

    $tareaDelPadre = Tarea::factory()->for($this->proyecto)->create(['modulo_id' => $padre->id]);
    $tareaDelNieto = Tarea::factory()->for($this->proyecto)->create(['modulo_id' => $nieto->id]);

    $this->actingAs($this->lider)
        ->delete(route('modulos.destroy', $padre))
        ->assertRedirect();

    expect(Modulo::query()->whereKey([$padre->id, $hijo->id, $nieto->id])->count())->toBe(0)
        ->and($tareaDelPadre->fresh())->not->toBeNull()
        ->and($tareaDelPadre->fresh()->modulo_id)->toBeNull()
        ->and($tareaDelNieto->fresh()->modulo_id)->toBeNull();
});

it('cuenta las tareas de un módulo incluyendo las de sus descendientes', function () {
    $padre = Modulo::factory()->for($this->proyecto)->create(['nombre' => 'Padre', 'orden' => 0]);
    $hijo = Modulo::factory()->hijoDe($padre)->create(['nombre' => 'Hijo', 'orden' => 0]);
    $nieto = Modulo::factory()->hijoDe($hijo)->create(['nombre' => 'Nieto', 'orden' => 0]);

    Tarea::factory()->for($this->proyecto)->create(['modulo_id' => $padre->id]);
    Tarea::factory(2)->for($this->proyecto)->create(['modulo_id' => $hijo->id]);
    Tarea::factory(3)->for($this->proyecto)->create(['modulo_id' => $nieto->id]);
    // Sin módulo: no cuenta en ninguna rama.
    Tarea::factory()->for($this->proyecto)->create(['modulo_id' => null]);

    $arbol = $this->actingAs($this->lider)
        ->get(route('proyectos.modulos.index', $this->proyecto))
        ->viewData('page')['props']['arbol'];

    $raiz = $arbol[0];

    expect($raiz['tareas_directas'])->toBe(1)
        ->and($raiz['tareas_totales'])->toBe(6)
        ->and($raiz['submodulos_totales'])->toBe(2)
        ->and($raiz['hijos'][0]['tareas_totales'])->toBe(5)
        ->and($raiz['hijos'][0]['hijos'][0]['tareas_totales'])->toBe(3);
});

it('devuelve el árbol ordenado por el campo orden', function () {
    Modulo::factory()->for($this->proyecto)->create(['nombre' => 'Segundo', 'orden' => 1]);
    Modulo::factory()->for($this->proyecto)->create(['nombre' => 'Primero', 'orden' => 0]);

    $arbol = $this->actingAs($this->lider)
        ->get(route('proyectos.modulos.index', $this->proyecto))
        ->viewData('page')['props']['arbol'];

    expect(array_column($arbol, 'nombre'))->toBe(['Primero', 'Segundo']);
});

it('carga el árbol con un número acotado de consultas, no una por nodo', function () {
    // 200 módulos en tres niveles: con una consulta por nodo esto serían más de
    // doscientas. El árbol se arma en memoria a partir de dos.
    $raices = Modulo::factory(10)->for($this->proyecto)->create();

    foreach ($raices as $raiz) {
        $hijos = Modulo::factory(4)->hijoDe($raiz)->create();

        foreach ($hijos as $hijo) {
            Modulo::factory(4)->hijoDe($hijo)->create();
        }
    }

    expect(Modulo::query()->where('proyecto_id', $this->proyecto->id)->count())->toBe(210);

    DB::enableQueryLog();

    $this->actingAs($this->lider)
        ->get(route('proyectos.modulos.index', $this->proyecto))
        ->assertOk();

    $consultas = count(DB::getQueryLog());

    DB::disableQueryLog();

    // Sesión, usuario, permisos, proyecto, autorización, módulos y tareas.
    // El techo es holgado a propósito, pero muy por debajo del número de nodos.
    expect($consultas)->toBeLessThanOrEqual(15);
});

it('niega al colaborador crear, renombrar, reordenar o borrar módulos', function () {
    $modulo = Modulo::factory()->for($this->proyecto)->create();

    // La estructura del proyecto la define quien lo lidera.
    $this->actingAs($this->colaborador)
        ->post(route('proyectos.modulos.store', $this->proyecto), ['nombre' => 'Intento'])
        ->assertForbidden();

    $this->actingAs($this->colaborador)
        ->put(route('modulos.update', $modulo), ['nombre' => 'Intento'])
        ->assertForbidden();

    $this->actingAs($this->colaborador)
        ->put(route('proyectos.modulos.orden', $this->proyecto), [
            'niveles' => [['parent_id' => null, 'modulos' => [$modulo->id]]],
        ])
        ->assertForbidden();

    $this->actingAs($this->colaborador)
        ->delete(route('modulos.destroy', $modulo))
        ->assertForbidden();

    // Pero sí lo lee.
    $this->actingAs($this->colaborador)
        ->get(route('proyectos.modulos.index', $this->proyecto))
        ->assertOk();
});

it('niega el árbol a quien no participa en el proyecto', function () {
    $ajeno = usuarioCon(RolGlobal::Miembro);

    $this->actingAs($ajeno)
        ->get(route('proyectos.modulos.index', $this->proyecto))
        ->assertForbidden();
});

it('niega escribir el árbol a un invitado global aunque sea líder del proyecto', function () {
    $invitado = usuarioCon(RolGlobal::Invitado);
    asignarAProyecto($invitado, $this->proyecto, RolProyecto::Lider);

    $this->actingAs($invitado)
        ->post(route('proyectos.modulos.store', $this->proyecto), ['nombre' => 'Intento'])
        ->assertForbidden();
});
