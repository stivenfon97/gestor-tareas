<?php

declare(strict_types=1);

use App\Actions\CerrarPeriodo;
use App\Enums\EstadoTarea;
use App\Enums\EventoHistorial;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Exceptions\HistorialInmutable;
use App\Models\Equipo;
use App\Models\HistorialCambio;
use App\Models\Modulo;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Database\Eloquent\Model;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->admin = usuarioCon(RolGlobal::Administrador);
});

/**
 * La última fila del historial de un registro.
 */
function ultimoCambioDe(Model $registro): HistorialCambio
{
    return HistorialCambio::query()->de($registro)->latest('id')->firstOrFail();
}

it('registra el valor previo, el nuevo, los campos y el autor al cambiar correo y nombre de un usuario', function () {
    $usuario = usuarioCon(RolGlobal::Miembro);
    $usuario->update(['name' => 'Ana Ruiz', 'email' => 'ana@antes.test']);

    $this->actingAs($this->admin)
        ->put(route('usuarios.update', $usuario), [
            'name' => 'Ana María Ruiz',
            'email' => 'ana@despues.test',
            'rol_global' => RolGlobal::Miembro->value,
        ])
        ->assertRedirect(route('usuarios.index'));

    $cambio = ultimoCambioDe($usuario);

    expect($cambio->evento)->toBe(EventoHistorial::Actualizado)
        ->and($cambio->registro_type)->toBe('usuario')
        ->and($cambio->campos)->toEqualCanonicalizing(['name', 'email'])
        ->and($cambio->valores_anteriores)->toBe(['name' => 'Ana Ruiz', 'email' => 'ana@antes.test'])
        ->and($cambio->valores_nuevos)->toBe(['name' => 'Ana María Ruiz', 'email' => 'ana@despues.test'])
        ->and($cambio->usuario_id)->toBe($this->admin->id)
        ->and($cambio->created_at)->not->toBeNull();
});

it('registra que la contraseña cambió sin guardar ningún valor, ni siquiera el hash', function () {
    $usuario = User::factory()->create();

    $this->actingAs($usuario)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'otra-contrasena',
            'password_confirmation' => 'otra-contrasena',
        ])
        ->assertSessionHasNoErrors();

    $cambio = ultimoCambioDe($usuario);

    expect($cambio->campos)->toBe(['password'])
        ->and($cambio->valores_anteriores)->toBe([])
        ->and($cambio->valores_nuevos)->toBe([])
        ->and($cambio->usuario_id)->toBe($usuario->id);
});

it('no registra remember_token ni deja fila cuando solo cambia un campo excluido', function () {
    $usuario = User::factory()->create();
    $filasAntes = HistorialCambio::query()->de($usuario)->count();

    $usuario->update(['remember_token' => 'otro-token']);

    expect(HistorialCambio::query()->de($usuario)->count())->toBe($filasAntes);
});

it('no registra el orden de una tarea, que cambia en cada arrastre', function () {
    $tarea = Tarea::factory()->create(['orden' => 1]);
    $filasAntes = HistorialCambio::query()->de($tarea)->count();

    $tarea->update(['orden' => 7]);

    expect(HistorialCambio::query()->de($tarea)->count())->toBe($filasAntes);
});

it('registra la creación con los valores iniciales y el borrado con la foto de cómo quedó', function () {
    $this->actingAs($this->admin)
        ->post(route('equipos.store'), ['nombre' => 'Backend', 'descripcion' => 'APIs'])
        ->assertRedirect();

    $equipo = Equipo::query()->where('nombre', 'Backend')->sole();
    $creacion = ultimoCambioDe($equipo);

    expect($creacion->evento)->toBe(EventoHistorial::Creado)
        ->and($creacion->valores_anteriores)->toBeNull()
        ->and($creacion->valores_nuevos)->toMatchArray(['nombre' => 'Backend', 'descripcion' => 'APIs']);

    $this->actingAs($this->admin)->delete(route('equipos.destroy', $equipo))->assertRedirect();

    $borrado = HistorialCambio::query()
        ->where('registro_type', 'equipo')
        ->where('registro_id', $equipo->id)
        ->latest('id')
        ->firstOrFail();

    expect(Equipo::query()->find($equipo->id))->toBeNull()
        ->and($borrado->evento)->toBe(EventoHistorial::Eliminado)
        ->and($borrado->valores_anteriores)->toMatchArray(['nombre' => 'Backend', 'descripcion' => 'APIs'])
        ->and($borrado->valores_nuevos)->toBeNull();
});

it('registra el traslado de tareas al cerrar un periodo aunque sea un update masivo', function () {
    $proyecto = Proyecto::factory()->create();
    $periodo = Periodo::factory()->create(['proyecto_id' => $proyecto->id, 'consecutivo' => 1]);
    $tarea = Tarea::factory()->for($proyecto)->create([
        'periodo_id' => $periodo->id,
        'estado' => EstadoTarea::Pendiente,
    ]);

    $this->actingAs($this->admin);
    ['siguiente' => $siguiente] = app(CerrarPeriodo::class)($periodo, [$tarea->id], '2026-02-01');

    $cambio = ultimoCambioDe($tarea);

    expect($cambio->evento)->toBe(EventoHistorial::Actualizado)
        ->and($cambio->campos)->toBe(['periodo_id', 'periodo_origen_id'])
        ->and($cambio->valores_anteriores)->toBe(['periodo_id' => $periodo->id, 'periodo_origen_id' => null])
        ->and($cambio->valores_nuevos)->toBe(['periodo_id' => $siguiente->id, 'periodo_origen_id' => $periodo->id])
        ->and($cambio->usuario_id)->toBe($this->admin->id);
});

it('registra el cambio de padre de un módulo al reordenar, pero no un reordenamiento entre hermanos', function () {
    $proyecto = Proyecto::factory()->create();
    $origen = Modulo::factory()->for($proyecto)->create(['orden' => 0]);
    $destino = Modulo::factory()->for($proyecto)->create(['orden' => 1]);
    $viajero = Modulo::factory()->hijoDe($origen)->create(['orden' => 0]);
    $filasDelDestino = HistorialCambio::query()->de($destino)->count();

    $this->actingAs($this->admin)
        ->put(route('proyectos.modulos.orden', $proyecto), [
            'niveles' => [
                ['parent_id' => null, 'modulos' => [$destino->id, $origen->id]],
                ['parent_id' => $destino->id, 'modulos' => [$viajero->id]],
            ],
        ])
        ->assertRedirect();

    $cambio = ultimoCambioDe($viajero);

    expect($cambio->campos)->toBe(['parent_id'])
        ->and($cambio->valores_anteriores)->toBe(['parent_id' => $origen->id])
        ->and($cambio->valores_nuevos)->toBe(['parent_id' => $destino->id])
        ->and(HistorialCambio::query()->de($destino)->count())->toBe($filasDelDestino);
});

it('registra en el proyecto el alta, el cambio de rol y la baja de un miembro', function () {
    $proyecto = Proyecto::factory()->create();
    $persona = usuarioCon(RolGlobal::Miembro);

    $this->actingAs($this->admin);

    asignarAProyecto($persona, $proyecto, RolProyecto::Colaborador);
    $this->put(route('proyectos.miembros.update', [$proyecto, $persona]), ['rol' => RolProyecto::Lider->value])
        ->assertRedirect();
    $this->delete(route('proyectos.miembros.destroy', [$proyecto, $persona]))->assertRedirect();

    $filas = HistorialCambio::query()->de($proyecto)
        ->whereIn('evento', [
            EventoHistorial::MiembroAgregado,
            EventoHistorial::MiembroActualizado,
            EventoHistorial::MiembroRetirado,
        ])
        ->orderBy('id')
        ->get();

    expect($filas->pluck('evento')->all())->toBe([
        EventoHistorial::MiembroAgregado,
        EventoHistorial::MiembroActualizado,
        EventoHistorial::MiembroRetirado,
    ])
        ->and($filas[0]->valores_nuevos)->toBe(['usuario_id' => $persona->id, 'rol' => 'colaborador'])
        ->and($filas[1]->valores_anteriores)->toBe(['usuario_id' => $persona->id, 'rol' => 'colaborador'])
        ->and($filas[1]->valores_nuevos)->toBe(['usuario_id' => $persona->id, 'rol' => 'lider'])
        ->and($filas[2]->valores_anteriores)->toBe(['usuario_id' => $persona->id, 'rol' => 'lider']);
});

it('no deja modificar ni borrar una fila del historial', function () {
    $cambio = ultimoCambioDe(Equipo::factory()->create());

    expect(fn () => $cambio->update(['ip' => '10.0.0.1']))->toThrow(HistorialInmutable::class)
        ->and(fn () => $cambio->delete())->toThrow(HistorialInmutable::class);
});
