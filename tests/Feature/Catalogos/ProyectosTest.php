<?php

declare(strict_types=1);

use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Exceptions\PrefijoInmutable;
use App\Models\Cliente;
use App\Models\Proyecto;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->administrador = usuarioCon(RolGlobal::Administrador);
    $this->cliente = Cliente::factory()->create();
});

function datosDeProyecto(Cliente $cliente, array $extra = []): array
{
    return [
        'cliente_id' => $cliente->id,
        'nombre' => 'Portal de Clientes',
        'prefijo_tarea' => 'POR',
        'descripcion' => 'Portal de autogestión.',
        'estado' => 'activo',
        ...$extra,
    ];
}

it('crea un proyecto y le autogenera el slug desde el nombre', function () {
    $this->actingAs($this->administrador)
        ->post(route('proyectos.store'), datosDeProyecto($this->cliente))
        ->assertRedirect();

    $proyecto = Proyecto::query()->where('prefijo_tarea', 'POR')->sole();

    expect($proyecto->slug)->toBe('portal-de-clientes');
});

it('resuelve la colisión de slug con un sufijo numérico', function () {
    $otro = Cliente::factory()->create();

    $this->actingAs($this->administrador)
        ->post(route('proyectos.store'), datosDeProyecto($this->cliente));

    $this->actingAs($this->administrador)
        ->post(route('proyectos.store'), datosDeProyecto($otro, ['prefijo_tarea' => 'PO2']));

    $slugs = Proyecto::query()->whereIn('prefijo_tarea', ['POR', 'PO2'])->orderBy('id')->pluck('slug')->all();

    expect($slugs)->toBe(['portal-de-clientes', 'portal-de-clientes-2']);
});

it('respeta el slug escrito a mano', function () {
    $this->actingAs($this->administrador)
        ->post(route('proyectos.store'), datosDeProyecto($this->cliente, ['slug' => 'portal-a-medida']));

    expect(Proyecto::query()->where('prefijo_tarea', 'POR')->value('slug'))->toBe('portal-a-medida');
});

it('rechaza un slug ya tomado', function () {
    Proyecto::factory()->create(['slug' => 'portal-ocupado']);

    $this->actingAs($this->administrador)
        ->post(route('proyectos.store'), datosDeProyecto($this->cliente, ['slug' => 'portal-ocupado']))
        ->assertSessionHasErrors('slug');
});

it('rechaza un slug con mayúsculas o espacios', function () {
    $this->actingAs($this->administrador)
        ->post(route('proyectos.store'), datosDeProyecto($this->cliente, ['slug' => 'Portal De Clientes']))
        ->assertSessionHasErrors('slug');
});

it('guarda el prefijo en mayúsculas y sin espacios', function () {
    $this->actingAs($this->administrador)
        ->post(route('proyectos.store'), datosDeProyecto($this->cliente, ['prefijo_tarea' => ' maq 01 ']));

    expect(Proyecto::query()->where('nombre', 'Portal de Clientes')->value('prefijo_tarea'))->toBe('MAQ01');
});

it('normaliza el prefijo también cuando se asigna directamente en el modelo', function () {
    // El mutador vive en el modelo, no en el formulario: un seeder o un
    // `tinker` no pueden dejar un prefijo en minúsculas.
    $proyecto = Proyecto::factory()->create(['prefijo_tarea' => 'ab cd']);

    expect($proyecto->prefijo_tarea)->toBe('ABCD');
});

it('rechaza un prefijo con formato inválido', function (string $prefijo) {
    $this->actingAs($this->administrador)
        ->post(route('proyectos.store'), datosDeProyecto($this->cliente, ['prefijo_tarea' => $prefijo]))
        ->assertSessionHasErrors('prefijo_tarea');
})->with([
    'un solo carácter' => 'A',
    'más de diez' => 'ABCDEFGHIJK',
    'con guion' => 'MAQ-1',
    'con símbolo' => 'MAQ_1',
    'vacío' => '',
]);

it('rechaza un prefijo ya tomado por otro proyecto', function () {
    Proyecto::factory()->create(['prefijo_tarea' => 'DUP']);

    $this->actingAs($this->administrador)
        ->post(route('proyectos.store'), datosDeProyecto($this->cliente, ['prefijo_tarea' => 'DUP']))
        ->assertSessionHasErrors('prefijo_tarea');
});

it('deja cambiar el prefijo mientras el proyecto no haya emitido ningún código', function () {
    $proyecto = Proyecto::factory()->create(['prefijo_tarea' => 'ANT']);

    $this->actingAs($this->administrador)
        ->put(route('proyectos.update', $proyecto), datosDeProyecto($this->cliente, [
            'nombre' => $proyecto->nombre,
            'slug' => $proyecto->slug,
            'prefijo_tarea' => 'NUE',
        ]))
        ->assertRedirect();

    expect($proyecto->fresh()->prefijo_tarea)->toBe('NUE');
});

it('rechaza por HTTP cambiar el prefijo cuando ya existen tareas', function () {
    $proyecto = Proyecto::factory()->create(['prefijo_tarea' => 'FIJ']);
    Tarea::factory()->for($proyecto)->create();

    $this->actingAs($this->administrador)
        ->put(route('proyectos.update', $proyecto), datosDeProyecto($this->cliente, [
            'nombre' => $proyecto->nombre,
            'slug' => $proyecto->slug,
            'prefijo_tarea' => 'OTR',
        ]))
        ->assertSessionHasErrors('prefijo_tarea');

    expect($proyecto->fresh()->prefijo_tarea)->toBe('FIJ');
});

it('rechaza cambiar el prefijo llamando al modelo directamente', function () {
    // La cerradura está en el observer, no en el formulario: ninguna vía de
    // escritura puede saltársela.
    $proyecto = Proyecto::factory()->create(['prefijo_tarea' => 'FIJ']);
    Tarea::factory()->for($proyecto)->create();

    expect(fn () => $proyecto->update(['prefijo_tarea' => 'OTR']))
        ->toThrow(PrefijoInmutable::class);

    expect($proyecto->fresh()->prefijo_tarea)->toBe('FIJ');
});

it('sigue bloqueando el prefijo aunque se borren las tareas: el código ya viajó al cliente', function () {
    $proyecto = Proyecto::factory()->create(['prefijo_tarea' => 'FIJ']);
    $tarea = Tarea::factory()->for($proyecto)->create();

    $tarea->forceDelete();

    // `contadores_tarea` recuerda que se emitió un código, aunque la tarea ya
    // no exista.
    expect($proyecto->fresh()->tieneCodigosEmitidos())->toBeTrue()
        ->and(fn () => $proyecto->update(['prefijo_tarea' => 'OTR']))
        ->toThrow(PrefijoInmutable::class);
});

it('deja guardar el resto de campos reenviando el mismo prefijo bloqueado', function () {
    $proyecto = Proyecto::factory()->create(['prefijo_tarea' => 'FIJ']);
    Tarea::factory()->for($proyecto)->create();

    $this->actingAs($this->administrador)
        ->put(route('proyectos.update', $proyecto), datosDeProyecto($this->cliente, [
            'nombre' => 'Nombre nuevo',
            'slug' => $proyecto->slug,
            'prefijo_tarea' => 'FIJ',
        ]))
        ->assertRedirect();

    expect($proyecto->fresh()->nombre)->toBe('Nombre nuevo');
});

it('marca el prefijo como bloqueado en el formulario de edición', function () {
    $proyecto = Proyecto::factory()->create();
    Tarea::factory()->for($proyecto)->create();

    $respuesta = $this->actingAs($this->administrador)->get(route('proyectos.edit', $proyecto));

    expect($respuesta->viewData('page')['props']['proyecto']['prefijo_bloqueado'])->toBeTrue();
});

it('niega crear proyectos a quien no es administrador', function () {
    $miembro = usuarioCon(RolGlobal::Miembro);

    $this->actingAs($miembro)
        ->get(route('proyectos.create'))
        ->assertForbidden();

    $this->actingAs($miembro)
        ->post(route('proyectos.store'), datosDeProyecto($this->cliente))
        ->assertForbidden();
});

it('deja editar el proyecto a su líder y se lo niega al colaborador', function () {
    $proyecto = Proyecto::factory()->for($this->cliente)->create(['prefijo_tarea' => 'EDI']);

    $lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($lider, $proyecto, RolProyecto::Lider);

    $colaborador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($colaborador, $proyecto, RolProyecto::Colaborador);

    $datos = datosDeProyecto($this->cliente, [
        'nombre' => 'Editado por el líder',
        'slug' => $proyecto->slug,
        'prefijo_tarea' => 'EDI',
    ]);

    $this->actingAs($colaborador)
        ->put(route('proyectos.update', $proyecto), $datos)
        ->assertForbidden();

    $this->actingAs($lider)
        ->put(route('proyectos.update', $proyecto), $datos)
        ->assertRedirect();

    expect($proyecto->fresh()->nombre)->toBe('Editado por el líder');
});

it('niega el borrado a un líder y se lo permite al administrador, en suave', function () {
    $proyecto = Proyecto::factory()->create();

    $lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($lider, $proyecto, RolProyecto::Lider);

    $this->actingAs($lider)
        ->delete(route('proyectos.destroy', $proyecto))
        ->assertForbidden();

    $this->actingAs($this->administrador)
        ->delete(route('proyectos.destroy', $proyecto))
        ->assertRedirect(route('proyectos.index'));

    expect(Proyecto::query()->whereKey($proyecto->id)->exists())->toBeFalse()
        ->and(Proyecto::withTrashed()->whereKey($proyecto->id)->exists())->toBeTrue();
});

it('devuelve el concentrador del proyecto con su resumen y sin datos de relleno', function () {
    $proyecto = Proyecto::factory()->for($this->cliente)->create();

    $lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($lider, $proyecto, RolProyecto::Lider);

    $respuesta = $this->actingAs($lider)->get(route('proyectos.show', $proyecto));

    $respuesta->assertOk();

    $props = $respuesta->viewData('page')['props'];

    expect($props['resumen']['tareas_por_estado'])->toBe([])
        ->and($props['resumen']['periodo_abierto'])->toBeNull()
        ->and($props['resumen']['ultima_reunion'])->toBeNull()
        ->and($props['proyecto']['modulos_count'])->toBe(0);
});
