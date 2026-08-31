<?php

declare(strict_types=1);

use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Enums\TipoTarea;
use App\Exceptions\VinculoDeOrigenInvalido;
use App\Models\Proyecto;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);

    $this->entregada = Tarea::factory()->for($this->proyecto)->entregada()->create();
});

/** Payload mínimo del formulario de tarea. */
function datosDeTarea(array $extra = []): array
{
    return [
        'nombre' => 'Corregir el cálculo del descuento por línea',
        'tipo' => TipoTarea::Correccion->value,
        'prioridad' => 'alta',
        ...$extra,
    ];
}

it('deja que una corrección apunte a una tarea entregada del mismo proyecto', function () {
    $this->actingAs($this->lider)
        ->post(route('proyectos.tareas.store', $this->proyecto), datosDeTarea([
            'tarea_origen_id' => $this->entregada->id,
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $correccion = Tarea::query()->where('tarea_origen_id', $this->entregada->id)->sole();

    expect($correccion->tipo)->toBe(TipoTarea::Correccion)
        ->and($correccion->origen->is($this->entregada))->toBeTrue()
        ->and($this->entregada->correcciones()->count())->toBe(1);
});

it('deja que un soporte también apunte a su origen', function () {
    $this->actingAs($this->lider)
        ->post(route('proyectos.tareas.store', $this->proyecto), datosDeTarea([
            'tipo' => TipoTarea::Soporte->value,
            'tarea_origen_id' => $this->entregada->id,
        ]))
        ->assertSessionHasNoErrors();

    expect($this->entregada->correcciones()->count())->toBe(1);
});

it('rechaza apuntar a una tarea de otro proyecto', function () {
    $ajena = Tarea::factory()->for(Proyecto::factory()->create())->entregada()->create();

    $this->actingAs($this->lider)
        ->post(route('proyectos.tareas.store', $this->proyecto), datosDeTarea([
            'tarea_origen_id' => $ajena->id,
        ]))
        ->assertSessionHasErrors('tarea_origen_id');

    expect(Tarea::query()->where('tarea_origen_id', $ajena->id)->exists())->toBeFalse();
});

it('rechaza que una tarea se apunte a sí misma', function () {
    $correccion = Tarea::factory()->for($this->proyecto)->deCorreccion()->create();

    $this->actingAs($this->lider)
        ->put(route('tareas.update', $correccion), datosDeTarea([
            'tarea_origen_id' => $correccion->id,
        ]))
        ->assertSessionHasErrors('tarea_origen_id');

    expect($correccion->fresh()->tarea_origen_id)->toBeNull();
});

it('rechaza un tipo desarrollo con origen informado', function () {
    $this->actingAs($this->lider)
        ->post(route('proyectos.tareas.store', $this->proyecto), datosDeTarea([
            'tipo' => TipoTarea::Desarrollo->value,
            'tarea_origen_id' => $this->entregada->id,
        ]))
        ->assertSessionHasErrors('tarea_origen_id');

    expect($this->entregada->correcciones()->count())->toBe(0);
});

it('rechaza en el modelo lo que el formulario ya rechaza: tipo que no admite origen', function () {
    // La cerradura vive en el observer, no en el FormRequest: un seeder o un
    // comando tampoco pueden torcer el vínculo.
    expect(fn () => Tarea::factory()->for($this->proyecto)->create([
        'tipo' => TipoTarea::Mejora,
        'tarea_origen_id' => $this->entregada->id,
    ]))->toThrow(VinculoDeOrigenInvalido::class);
});

it('rechaza en el modelo un origen de otro proyecto', function () {
    $ajena = Tarea::factory()->for(Proyecto::factory()->create())->create();

    expect(fn () => Tarea::factory()->for($this->proyecto)->deCorreccion()->create([
        'tarea_origen_id' => $ajena->id,
    ]))->toThrow(VinculoDeOrigenInvalido::class);
});

it('rechaza en el modelo apuntarse a sí misma', function () {
    $correccion = Tarea::factory()->for($this->proyecto)->deCorreccion()->create();

    expect(fn () => $correccion->update(['tarea_origen_id' => $correccion->id]))
        ->toThrow(VinculoDeOrigenInvalido::class);
});

it('rechaza una cadena circular de orígenes', function () {
    // A → B ya existe; cerrar B → A dejaría la cadena colgada.
    $a = Tarea::factory()->for($this->proyecto)->deCorreccion()->create();
    $b = Tarea::factory()->for($this->proyecto)->deCorreccion()->create(['tarea_origen_id' => $a->id]);

    expect(fn () => $a->update(['tarea_origen_id' => $b->id]))
        ->toThrow(VinculoDeOrigenInvalido::class);

    expect($a->fresh()->tarea_origen_id)->toBeNull();
});

it('rechaza una cadena circular de tres eslabones', function () {
    $a = Tarea::factory()->for($this->proyecto)->deCorreccion()->create();
    $b = Tarea::factory()->for($this->proyecto)->deCorreccion()->create(['tarea_origen_id' => $a->id]);
    $c = Tarea::factory()->for($this->proyecto)->deCorreccion()->create(['tarea_origen_id' => $b->id]);

    expect(fn () => $a->update(['tarea_origen_id' => $c->id]))
        ->toThrow(VinculoDeOrigenInvalido::class);
});

it('admite una cadena lineal de correcciones', function () {
    $a = Tarea::factory()->for($this->proyecto)->deCorreccion()->create();
    $b = Tarea::factory()->for($this->proyecto)->deCorreccion()->create(['tarea_origen_id' => $a->id]);
    $c = Tarea::factory()->for($this->proyecto)->deCorreccion()->create(['tarea_origen_id' => $b->id]);

    expect($c->origen->is($b))->toBeTrue()
        ->and($b->origen->is($a))->toBeTrue()
        ->and($a->correcciones()->count())->toBe(1);
});

it('muestra en el detalle de la entrega las correcciones que generó', function () {
    $primera = Tarea::factory()->for($this->proyecto)->deCorreccion()
        ->create(['tarea_origen_id' => $this->entregada->id, 'nombre' => 'El PDF sale sin logo']);
    Tarea::factory()->for($this->proyecto)->deSoporte()
        ->create(['tarea_origen_id' => $this->entregada->id]);

    $respuesta = $this->actingAs($this->lider)->get(route('tareas.show', $this->entregada));

    $respuesta->assertOk();

    $correcciones = $respuesta->viewData('page')['props']['correcciones'];

    expect($correcciones)->toHaveCount(2)
        ->and(collect($correcciones)->pluck('codigo'))->toContain($primera->codigo);
});

it('muestra en el detalle de la corrección un enlace a su origen', function () {
    $correccion = Tarea::factory()->for($this->proyecto)->deCorreccion()
        ->create(['tarea_origen_id' => $this->entregada->id]);

    $respuesta = $this->actingAs($this->lider)->get(route('tareas.show', $correccion));

    $origen = $respuesta->viewData('page')['props']['tarea']['origen'];

    expect($origen['id'])->toBe($this->entregada->id)
        ->and($origen['codigo'])->toBe($this->entregada->codigo);
});

it('deja la corrección en pie si se borra a la fuerza la entrega original', function () {
    $correccion = Tarea::factory()->for($this->proyecto)->deCorreccion()
        ->create(['tarea_origen_id' => $this->entregada->id]);

    $this->entregada->forceDelete();

    // `set null`, no `cascade`: la corrección es trabajo hecho que cuenta en su
    // periodo, y perderla falsearía ese reporte.
    expect($correccion->fresh())->not->toBeNull()
        ->and($correccion->fresh()->tarea_origen_id)->toBeNull();
});
