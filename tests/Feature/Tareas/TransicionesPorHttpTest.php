<?php

declare(strict_types=1);

use App\Enums\EstadoTarea;
use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\HistorialEstado;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Services\CambiarEstadoTarea;
use Database\Seeders\RolesYPermisosSeeder;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);
});

/**
 * Deja una tarea del proyecto en el estado pedido.
 *
 * Se crea directamente en ese estado en lugar de recorrer la matriz: aquí lo
 * que se prueba es el endpoint, y el punto de partida es solo el escenario.
 * `atributosDeTransicion()` —de `Dominio/TransicionesDeTareaTest`— aporta el
 * motivo que cada estado exige.
 */
function tareaDelProyectoEn(Proyecto $proyecto, EstadoTarea $estado): Tarea
{
    return Tarea::factory()->for($proyecto)->create(atributosDeTransicion($estado));
}

/** Payload del motivo que el destino exige, si exige alguno. */
function motivoEnviadoPara(EstadoTarea $destino): array
{
    $atributos = atributosDeTransicion($destino);

    unset($atributos['estado']);

    return $atributos;
}

it('acepta por HTTP las once transiciones válidas de la matriz', function () {
    $aplicadas = 0;

    foreach (EstadoTarea::cases() as $desde) {
        foreach ($desde->transicionesPermitidas() as $hacia) {
            $tarea = tareaDelProyectoEn($this->proyecto, $desde);

            $this->actingAs($this->lider)
                ->put(route('tareas.estado.update', $tarea), ['estado' => $hacia->value] + motivoEnviadoPara($hacia))
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            expect($tarea->fresh()->estado)->toBe($hacia);

            $aplicadas++;
        }
    }

    // La matriz tiene once transiciones y todas pasan por el endpoint.
    expect($aplicadas)->toBe(11);
});

it('rechaza por HTTP una muestra de transiciones prohibidas', function (string $desde, string $hacia) {
    $tarea = tareaDelProyectoEn($this->proyecto, EstadoTarea::from($desde));

    $destino = EstadoTarea::from($hacia);

    $this->actingAs($this->lider)
        ->put(route('tareas.estado.update', $tarea), ['estado' => $hacia] + motivoEnviadoPara($destino))
        ->assertSessionHasErrors('estado');

    expect($tarea->fresh()->estado)->toBe(EstadoTarea::from($desde));
})->with([
    'pendiente → terminada' => ['pendiente', 'terminada'],
    'pendiente → entregada' => ['pendiente', 'entregada'],
    'en_proceso → pendiente' => ['en_proceso', 'pendiente'],
    'en_proceso → entregada' => ['en_proceso', 'entregada'],
    'en_espera → terminada' => ['en_espera', 'terminada'],
    // en_espera es falta de información, no espera de despliegue (D2.3).
    'terminada → en_espera' => ['terminada', 'en_espera'],
    'terminada → cancelada' => ['terminada', 'cancelada'],
    'terminada → pendiente' => ['terminada', 'pendiente'],
]);

it('no deja salir de entregada hacia ningún estado por HTTP', function (string $hacia) {
    $tarea = tareaDelProyectoEn($this->proyecto, EstadoTarea::Entregada);

    $this->actingAs($this->lider)
        ->put(route('tareas.estado.update', $tarea), ['estado' => $hacia] + motivoEnviadoPara(EstadoTarea::from($hacia)))
        ->assertSessionHasErrors('estado');

    expect($tarea->fresh()->estado)->toBe(EstadoTarea::Entregada);
})->with(['pendiente', 'en_proceso', 'en_espera', 'terminada', 'cancelada']);

it('no deja salir de cancelada hacia ningún estado por HTTP', function (string $hacia) {
    $tarea = tareaDelProyectoEn($this->proyecto, EstadoTarea::Cancelada);

    $this->actingAs($this->lider)
        ->put(route('tareas.estado.update', $tarea), ['estado' => $hacia] + motivoEnviadoPara(EstadoTarea::from($hacia)))
        ->assertSessionHasErrors('estado');

    expect($tarea->fresh()->estado)->toBe(EstadoTarea::Cancelada);
})->with(['pendiente', 'en_proceso', 'en_espera', 'terminada', 'entregada']);

it('exige motivo_espera al pasar a en_espera', function () {
    $tarea = tareaDelProyectoEn($this->proyecto, EstadoTarea::Pendiente);

    $this->actingAs($this->lider)
        ->put(route('tareas.estado.update', $tarea), ['estado' => 'en_espera'])
        ->assertSessionHasErrors('motivo_espera');

    expect($tarea->fresh()->estado)->toBe(EstadoTarea::Pendiente);
});

it('exige motivo_cancelacion al cancelar', function () {
    $tarea = tareaDelProyectoEn($this->proyecto, EstadoTarea::Pendiente);

    $this->actingAs($this->lider)
        ->put(route('tareas.estado.update', $tarea), ['estado' => 'cancelada'])
        ->assertSessionHasErrors('motivo_cancelacion');

    expect($tarea->fresh()->estado)->toBe(EstadoTarea::Pendiente);
});

it('no acepta motivo_cancelacion como sustituto de motivo_espera', function () {
    // Son campos distintos y no se reutilizan entre sí (D2.9).
    $tarea = tareaDelProyectoEn($this->proyecto, EstadoTarea::Pendiente);

    $this->actingAs($this->lider)
        ->put(route('tareas.estado.update', $tarea), [
            'estado' => 'en_espera',
            'motivo_cancelacion' => 'esto no vale aquí',
        ])
        ->assertSessionHasErrors('motivo_espera');
});

it('limpia el motivo al salir del estado pero lo conserva en el historial', function () {
    $tarea = tareaDelProyectoEn($this->proyecto, EstadoTarea::Pendiente);

    $this->actingAs($this->lider)->put(route('tareas.estado.update', $tarea), [
        'estado' => 'en_espera',
        'motivo_espera' => 'Falta la definición de los campos.',
    ]);

    $this->actingAs($this->lider)->put(route('tareas.estado.update', $tarea), ['estado' => 'en_proceso']);

    expect($tarea->fresh()->motivo_espera)->toBeNull();

    $registro = HistorialEstado::query()
        ->where('tarea_id', $tarea->id)
        ->where('estado_nuevo', EstadoTarea::EnEspera)
        ->sole();

    expect($registro->comentario)->toBe('Falta la definición de los campos.');
});

it('devuelve las transiciones disponibles correctas para cada estado', function (string $estado, array $esperadas) {
    $tarea = tareaDelProyectoEn($this->proyecto, EstadoTarea::from($estado));

    $respuesta = $this->actingAs($this->lider)->get(route('tareas.show', $tarea));

    $respuesta->assertOk();

    $disponibles = array_column($respuesta->viewData('page')['props']['transiciones'], 'valor');

    expect($disponibles)->toBe($esperadas);
})->with([
    'pendiente' => ['pendiente', ['en_proceso', 'en_espera', 'cancelada']],
    'en_proceso' => ['en_proceso', ['en_espera', 'terminada', 'cancelada']],
    'en_espera' => ['en_espera', ['pendiente', 'en_proceso', 'cancelada']],
    'terminada' => ['terminada', ['en_proceso', 'entregada']],
    'entregada' => ['entregada', []],
    'cancelada' => ['cancelada', []],
]);

it('marca qué transiciones exigen motivo para que la interfaz no lo adivine', function () {
    $disponibles = CambiarEstadoTarea::disponiblesPara(EstadoTarea::Pendiente);

    expect(collect($disponibles)->firstWhere('valor', 'en_espera')['requiere_motivo'])->toBe('motivo_espera')
        ->and(collect($disponibles)->firstWhere('valor', 'cancelada')['requiere_motivo'])->toBe('motivo_cancelacion')
        ->and(collect($disponibles)->firstWhere('valor', 'en_proceso')['requiere_motivo'])->toBeNull();
});

it('escribe un registro de historial por cada cambio y ninguno cuando no cambia', function () {
    $tarea = tareaDelProyectoEn($this->proyecto, EstadoTarea::Pendiente);

    $this->actingAs($this->lider)->put(route('tareas.estado.update', $tarea), ['estado' => 'en_proceso']);
    // Mismo estado: no es una transición y no se registra.
    $this->actingAs($this->lider)->put(route('tareas.estado.update', $tarea), ['estado' => 'en_proceso']);

    expect(HistorialEstado::query()->where('tarea_id', $tarea->id)->count())->toBe(1);

    $registro = HistorialEstado::query()->where('tarea_id', $tarea->id)->sole();

    expect($registro->estado_anterior)->toBe(EstadoTarea::Pendiente)
        ->and($registro->estado_nuevo)->toBe(EstadoTarea::EnProceso)
        ->and($registro->usuario_id)->toBe($this->lider->id);
});

it('niega el cambio de estado al observador y al cliente', function (string $rol) {
    $usuario = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($usuario, $this->proyecto, RolProyecto::from($rol));

    $tarea = tareaDelProyectoEn($this->proyecto, EstadoTarea::Pendiente);

    $this->actingAs($usuario)
        ->put(route('tareas.estado.update', $tarea), ['estado' => 'en_proceso'])
        ->assertForbidden();

    expect($tarea->fresh()->estado)->toBe(EstadoTarea::Pendiente);
})->with(['observador', 'cliente']);

it('el colaborador cambia el estado pero no elimina la tarea', function () {
    $colaborador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($colaborador, $this->proyecto, RolProyecto::Colaborador);

    $tarea = tareaDelProyectoEn($this->proyecto, EstadoTarea::Pendiente);

    $this->actingAs($colaborador)
        ->put(route('tareas.estado.update', $tarea), ['estado' => 'en_proceso'])
        ->assertRedirect();

    expect($tarea->fresh()->estado)->toBe(EstadoTarea::EnProceso);

    // Borrar es del líder: `TareaPolicy@delete` exige `gestiona`.
    $this->actingAs($colaborador)
        ->delete(route('tareas.destroy', $tarea))
        ->assertForbidden();

    expect(Tarea::query()->whereKey($tarea->id)->exists())->toBeTrue();
});

it('niega el cambio de estado a un invitado global aunque lidere el proyecto', function () {
    $invitado = usuarioCon(RolGlobal::Invitado);
    asignarAProyecto($invitado, $this->proyecto, RolProyecto::Lider);

    $tarea = tareaDelProyectoEn($this->proyecto, EstadoTarea::Pendiente);

    $this->actingAs($invitado)
        ->put(route('tareas.estado.update', $tarea), ['estado' => 'en_proceso'])
        ->assertForbidden();
});
