<?php

declare(strict_types=1);

use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Enums\TipoDeVista;
use App\Models\Modulo;
use App\Models\Proyecto;
use App\Models\VistaGuardada;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);
});

it('guarda una vista con su configuración', function () {
    $this->actingAs($this->lider)
        ->post(route('vistas.store'), [
            'nombre' => 'En espera del cliente',
            'tipo' => TipoDeVista::Kanban->value,
            'proyecto_id' => $this->proyecto->id,
            'configuracion' => ['filter' => ['estado' => 'en_espera'], 'agrupar' => 'modulo'],
        ])
        ->assertRedirect();

    $vista = VistaGuardada::query()->firstOrFail();

    expect($vista->usuario_id)->toBe($this->lider->id)
        ->and($vista->tipo)->toBe(TipoDeVista::Kanban)
        ->and($vista->configuracion['agrupar'])->toBe('modulo');
});

it('no acepta usuario_id: la vista es siempre de quien la guarda', function () {
    $otro = usuarioCon(RolGlobal::Miembro);

    $this->actingAs($this->lider)
        ->post(route('vistas.store'), [
            'nombre' => 'Ajena',
            'tipo' => TipoDeVista::Tabla->value,
            'configuracion' => [],
            'usuario_id' => $otro->id,
        ])
        ->assertRedirect();

    expect(VistaGuardada::query()->firstOrFail()->usuario_id)->toBe($this->lider->id);
});

it('renombra, duplica y borra', function () {
    $vista = VistaGuardada::factory()->for($this->lider, 'usuario')->create(['nombre' => 'Original']);

    $this->actingAs($this->lider)
        ->put(route('vistas.update', $vista), [
            'nombre' => 'Renombrada',
            'configuracion' => $vista->configuracion,
        ])
        ->assertRedirect();

    expect($vista->fresh()->nombre)->toBe('Renombrada');

    $this->actingAs($this->lider)
        ->post(route('vistas.duplicar', $vista))
        ->assertRedirect();

    expect(VistaGuardada::query()->count())->toBe(2)
        ->and(VistaGuardada::query()->where('nombre', 'like', '%copia%')->exists())->toBeTrue();

    $this->actingAs($this->lider)
        ->delete(route('vistas.destroy', $vista))
        ->assertRedirect();

    expect(VistaGuardada::query()->whereKey($vista->id)->exists())->toBeFalse();
});

it('duplicar nunca hereda la marca de predeterminada', function () {
    $vista = VistaGuardada::factory()->for($this->lider, 'usuario')->predeterminada()->create();

    $this->actingAs($this->lider)->post(route('vistas.duplicar', $vista))->assertRedirect();

    // Copiar una vista es querer una variante, no reemplazar la de referencia.
    expect(VistaGuardada::query()->where('es_predeterminada', true)->count())->toBe(1)
        ->and(VistaGuardada::query()->where('es_predeterminada', true)->first()->id)->toBe($vista->id);
});

it('la base de datos impide dos predeterminadas del mismo tipo y usuario', function () {
    VistaGuardada::factory()->for($this->lider, 'usuario')
        ->deTipo(TipoDeVista::Kanban)->predeterminada()->create();

    // La cerradura es el índice único, no la interfaz ni un observer: con dos
    // pestañas abiertas «la predeterminada» pasaría a ser la que devolviera
    // primero el motor, que es un valor que cambia solo.
    expect(fn () => VistaGuardada::factory()->for($this->lider, 'usuario')
        ->deTipo(TipoDeVista::Kanban)->predeterminada()->create())
        ->toThrow(QueryException::class);
});

it('deja convivir predeterminadas de tipos distintos y de usuarios distintos', function () {
    $otro = usuarioCon(RolGlobal::Miembro);

    VistaGuardada::factory()->for($this->lider, 'usuario')->deTipo(TipoDeVista::Kanban)->predeterminada()->create();
    VistaGuardada::factory()->for($this->lider, 'usuario')->deTipo(TipoDeVista::Tabla)->predeterminada()->create();
    VistaGuardada::factory()->for($otro, 'usuario')->deTipo(TipoDeVista::Kanban)->predeterminada()->create();

    expect(VistaGuardada::query()->where('es_predeterminada', true)->count())->toBe(3);
});

it('marcar una vista como predeterminada desmarca la anterior', function () {
    $antigua = VistaGuardada::factory()->for($this->lider, 'usuario')
        ->deTipo(TipoDeVista::Tabla)->predeterminada()->create();

    $this->actingAs($this->lider)
        ->post(route('vistas.store'), [
            'nombre' => 'La nueva de siempre',
            'tipo' => TipoDeVista::Tabla->value,
            'configuracion' => [],
            'es_predeterminada' => true,
        ])
        ->assertRedirect();

    expect($antigua->fresh()->es_predeterminada)->toBeFalse()
        ->and(VistaGuardada::query()->where('es_predeterminada', true)->count())->toBe(1);
});

it('las vistas de un usuario no las alcanza otro, ni siquiera un administrador', function (string $rol) {
    $vista = VistaGuardada::factory()->for($this->lider, 'usuario')->create(['nombre' => 'Mía']);

    $intruso = usuarioCon(RolGlobal::from($rol));

    // 404 y no 403: confirmar que existe ya diría que otra persona tiene una
    // vista con ese id. `Gate::before` deja pasar al administrador por encima
    // de las policies, y para una preferencia personal eso no vale.
    $this->actingAs($intruso)
        ->put(route('vistas.update', $vista), ['nombre' => 'Tuya', 'configuracion' => []])
        ->assertNotFound();

    $this->actingAs($intruso)->delete(route('vistas.destroy', $vista))->assertNotFound();

    expect($vista->fresh()->nombre)->toBe('Mía');
})->with(['miembro', 'administrador']);

it('el listado solo ofrece las vistas propias y las de su alcance', function () {
    $otroProyecto = Proyecto::factory()->create();
    $otroUsuario = usuarioCon(RolGlobal::Miembro);

    $global = VistaGuardada::factory()->for($this->lider, 'usuario')->create(['nombre' => 'Global']);
    $deEste = VistaGuardada::factory()->for($this->lider, 'usuario')->create([
        'nombre' => 'De este', 'proyecto_id' => $this->proyecto->id,
    ]);
    VistaGuardada::factory()->for($this->lider, 'usuario')->create([
        'nombre' => 'De otro', 'proyecto_id' => $otroProyecto->id,
    ]);
    VistaGuardada::factory()->for($otroUsuario, 'usuario')->create(['nombre' => 'De otra persona']);

    $respuesta = $this->actingAs($this->lider)
        ->get(route('proyectos.tareas.index', $this->proyecto));

    $nombres = array_column($respuesta->viewData('page')['props']['vistas'], 'nombre');

    expect($nombres)->toEqualCanonicalizing([$global->nombre, $deEste->nombre]);
});

it('aplica la vista predeterminada al entrar sin parámetros, y no si ya hay filtros', function () {
    VistaGuardada::factory()->for($this->lider, 'usuario')->predeterminada()->create([
        'proyecto_id' => $this->proyecto->id,
        'configuracion' => ['filter' => ['estado' => 'en_proceso']],
    ]);

    $this->actingAs($this->lider)
        ->get(route('proyectos.tareas.index', $this->proyecto))
        ->assertRedirect(route('proyectos.tareas.index', $this->proyecto).'?filter%5Bestado%5D=en_proceso');

    // Con filtros en la URL la predeterminada no pisa nada: aplicar algo que
    // nadie pidió encima de algo que sí se pidió se percibe como un fallo.
    $this->actingAs($this->lider)
        ->get(route('proyectos.tareas.index', $this->proyecto).'?filter[estado]=pendiente')
        ->assertOk();
});

it('aplicar una vista cuyo filtro apunta a un módulo borrado no falla: filtra lo que puede y avisa', function () {
    $modulo = Modulo::factory()->for($this->proyecto)->create();

    $vista = VistaGuardada::factory()->for($this->lider, 'usuario')->create([
        'proyecto_id' => $this->proyecto->id,
        'configuracion' => ['filter' => ['modulo_id' => (string) $modulo->id, 'estado' => 'pendiente']],
    ]);

    $modulo->delete();

    $respuesta = $this->actingAs($this->lider)
        ->get(route('proyectos.tareas.index', $this->proyecto).'?filter[estado]=pendiente');

    $ofrecida = collect($respuesta->viewData('page')['props']['vistas'])->firstWhere('id', $vista->id);

    expect($ofrecida['configuracion']['filter'])->toBe(['estado' => 'pendiente'])
        // Se avisa una vez de que se aplicó en parte, en lugar de devolver cero
        // filas fingiendo que no hay trabajo.
        ->and($ofrecida['parcial'])->toBeTrue();
});

it('no deja cambiar el tipo ni el proyecto de una vista ya guardada', function () {
    $vista = VistaGuardada::factory()->for($this->lider, 'usuario')->create();

    $this->actingAs($this->lider)
        ->put(route('vistas.update', $vista), [
            'nombre' => 'Otra',
            'configuracion' => [],
            'tipo' => TipoDeVista::Kanban->value,
        ])
        ->assertSessionHasErrors('tipo');

    expect($vista->fresh()->tipo)->toBe(TipoDeVista::Tabla);
});
