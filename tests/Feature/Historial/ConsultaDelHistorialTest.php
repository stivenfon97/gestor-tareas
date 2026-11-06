<?php

declare(strict_types=1);

use App\Enums\PrioridadTarea;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\HistorialCambio;
use App\Models\Modulo;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->admin = usuarioCon(RolGlobal::Administrador);
    $this->proyecto = Proyecto::factory()->create();

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);

    $this->tarea = Tarea::factory()->for($this->proyecto)->create([
        'prioridad' => PrioridadTarea::Baja,
        'modulo_id' => null,
    ]);
});

describe('historial de un registro', function () {
    it('presenta los cambios legibles: la referencia resuelta y el enum como clave de traducción', function () {
        $modulo = Modulo::factory()->for($this->proyecto)->create(['nombre' => 'Facturación']);

        $this->actingAs($this->lider);
        $this->tarea->update(['prioridad' => PrioridadTarea::Alta, 'modulo_id' => $modulo->id]);

        $respuesta = $this->getJson(route('historial.registro', ['tipo' => 'tarea', 'id' => $this->tarea->id]))
            ->assertOk()
            ->assertJsonPath('entradas.0.evento', 'actualizado')
            ->assertJsonPath('entradas.0.usuario.name', $this->lider->name);

        $cambios = collect($respuesta->json('entradas.0.cambios'))->keyBy('campo');

        expect($cambios['modulo_id']['anterior'])->toBe(['tipo' => 'vacio', 'valor' => null])
            ->and($cambios['modulo_id']['nuevo'])->toBe(['tipo' => 'texto', 'valor' => 'Facturación'])
            ->and($cambios['modulo_id']['etiqueta'])->toBe('historial.campos.modulo_id')
            ->and($cambios['prioridad']['anterior'])->toBe(['tipo' => 'clave', 'valor' => 'enums.prioridad_tarea.baja'])
            ->and($cambios['prioridad']['nuevo'])->toBe(['tipo' => 'clave', 'valor' => 'enums.prioridad_tarea.alta']);
    });

    it('marca como oculto el valor de la contraseña', function () {
        $usuario = User::factory()->create();
        $usuario->update(['password' => 'otra-contrasena']);

        $respuesta = $this->actingAs($this->admin)
            ->getJson(route('historial.registro', ['tipo' => 'usuario', 'id' => $usuario->id]))
            ->assertOk();

        expect($respuesta->json('entradas.0.cambios.0'))->toMatchArray([
            'campo' => 'password',
            'anterior' => ['tipo' => 'oculto', 'valor' => null],
            'nuevo' => ['tipo' => 'oculto', 'valor' => null],
        ]);
    });

    it('pagina hacia atrás con un cursor', function () {
        foreach (range(1, 22) as $vuelta) {
            $this->tarea->update(['nombre' => "Nombre {$vuelta}"]);
        }

        $primera = $this->actingAs($this->lider)
            ->getJson(route('historial.registro', ['tipo' => 'tarea', 'id' => $this->tarea->id]))
            ->assertOk()
            ->assertJsonCount(20, 'entradas');

        $segunda = $this->getJson(route('historial.registro', [
            'tipo' => 'tarea',
            'id' => $this->tarea->id,
            'cursor' => $primera->json('siguiente'),
        ]))->assertOk();

        // 22 ediciones + la creación.
        expect($segunda->json('entradas'))->toHaveCount(3)
            ->and($segunda->json('siguiente'))->toBeNull();
    });

    it('no lo ve quien no ve lo interno del proyecto, aunque vea la tarea', function (RolGlobal $rolGlobal, RolProyecto $rolProyecto) {
        $persona = usuarioCon($rolGlobal);
        asignarAProyecto($persona, $this->proyecto, $rolProyecto);

        $this->actingAs($persona)
            ->getJson(route('historial.registro', ['tipo' => 'tarea', 'id' => $this->tarea->id]))
            ->assertForbidden();

        $this->get(route('tareas.show', $this->tarea))
            ->assertOk()
            ->assertInertia(fn (Assert $pagina) => $pagina->where('puedeVerHistorial', false));
    })->with([
        'invitado global con rol de cliente' => [RolGlobal::Invitado, RolProyecto::Cliente],
        'invitado global aunque sea líder del proyecto' => [RolGlobal::Invitado, RolProyecto::Lider],
        'miembro global con rol de proyecto cliente' => [RolGlobal::Miembro, RolProyecto::Cliente],
    ]);

    it('no lo ve quien no participa en el proyecto', function () {
        $ajeno = usuarioCon(RolGlobal::Miembro);

        $this->actingAs($ajeno)
            ->getJson(route('historial.registro', ['tipo' => 'tarea', 'id' => $this->tarea->id]))
            ->assertForbidden();
    });

    it('el historial de un usuario es solo de quien gestiona usuarios', function () {
        $this->actingAs($this->lider)
            ->getJson(route('historial.registro', ['tipo' => 'usuario', 'id' => $this->admin->id]))
            ->assertForbidden();
    });

    it('la ficha avisa a quien sí puede verlo', function () {
        $this->actingAs($this->lider)
            ->get(route('tareas.show', $this->tarea))
            ->assertInertia(fn (Assert $pagina) => $pagina->where('puedeVerHistorial', true));
    });

    it('un tipo que no está en el mapa no existe como ruta', function () {
        $this->actingAs($this->admin)
            ->getJson('/historial/vista_guardada/1')
            ->assertNotFound();
    });
});

describe('pantalla global', function () {
    it('es solo para quien tiene historial.ver', function () {
        $this->actingAs($this->lider)
            ->get(route('historial.index'))
            ->assertForbidden();
    });

    it('lista los cambios con el registro al que pertenecen', function () {
        $this->actingAs($this->admin)
            ->get(route('historial.index', ['filter' => ['tipo' => 'tarea']]))
            ->assertOk()
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->component('historial/Index')
                ->has('historial.data', 1)
                ->where('historial.data.0.registro.tipo', 'tarea')
                ->where('historial.data.0.registro.etiqueta', $this->tarea->codigo.' · '.$this->tarea->nombre)
                ->where('historial.data.0.registro.url', route('tareas.show', $this->tarea))
                ->where('historial.data.0.evento', 'creado'));
    });

    it('filtra por evento y por usuario', function () {
        $this->actingAs($this->lider);
        $this->tarea->update(['nombre' => 'Renombrada']);

        $this->actingAs($this->admin)
            ->get(route('historial.index', ['filter' => [
                'evento' => 'actualizado',
                'usuario' => (string) $this->lider->id,
            ]]))
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->has('historial.data', 1)
                ->where('historial.data.0.registro.id', $this->tarea->id));
    });

    it('busca por un valor anterior o nuevo sin distinguir mayúsculas', function () {
        $this->tarea->update(['nombre' => 'Migrar Facturación Electrónica']);

        $this->actingAs($this->admin)
            ->get(route('historial.index', ['filter' => ['buscar' => 'migrar factur']]))
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->has('historial.data', 1)
                ->where('historial.data.0.evento', 'actualizado'));
    });

    it('busca por el nombre de quien hizo el cambio', function () {
        $this->actingAs($this->lider);
        $this->tarea->update(['nombre' => 'Otra']);

        // Acotado a tareas: la fila de creación del propio usuario también
        // lleva su nombre entre los valores y también coincidiría.
        $this->actingAs($this->admin)
            ->get(route('historial.index', ['filter' => ['tipo' => 'tarea', 'buscar' => $this->lider->name]]))
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->has('historial.data', 1)
                ->where('historial.data.0.usuario.id', $this->lider->id));
    });

    it('un registro borrado sigue en el historial, sin enlace', function () {
        $this->tarea->forceDelete();

        $this->actingAs($this->admin)
            ->get(route('historial.index', ['filter' => ['tipo' => 'tarea', 'evento' => 'eliminado']]))
            ->assertInertia(fn (Assert $pagina) => $pagina
                ->has('historial.data', 1)
                ->where('historial.data.0.registro.etiqueta', null)
                ->where('historial.data.0.registro.url', null));

        expect(HistorialCambio::query()->where('registro_type', 'tarea')->where('registro_id', $this->tarea->id)->count())
            ->toBe(2);
    });
});
