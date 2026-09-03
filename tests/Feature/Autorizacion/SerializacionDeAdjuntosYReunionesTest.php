<?php

declare(strict_types=1);

use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Proyecto;
use App\Models\Reunion;
use App\Models\Tarea;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * La misma lección que destapó `PeriodoController` en el cierre de la fase
 * 09: ocultar algo en la interfaz no lo oculta, el JSON de Inertia viaja
 * completo y se lee abriendo las herramientas del navegador. Esta fase
 * multiplica la superficie —adjuntos, reuniones vinculadas, respaldos— así
 * que cada sitio nuevo se audita igual: leyendo la respuesta, no la pantalla.
 */
beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->proyecto = Proyecto::factory()->create();

    $this->lider = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->lider, $this->proyecto, RolProyecto::Lider);

    $this->colaborador = usuarioCon(RolGlobal::Miembro);
    asignarAProyecto($this->colaborador, $this->proyecto, RolProyecto::Colaborador);

    $this->cliente = usuarioCon(RolGlobal::Invitado);
    asignarAProyecto($this->cliente, $this->proyecto, RolProyecto::Cliente);
});

it('la clave respaldos no existe en el JSON del proyecto para colaborador ni cliente', function () {
    Storage::fake('local');
    $this->proyecto->addMedia(UploadedFile::fake()->createWithContent('respaldo.sql', '-- dump'))
        ->toMediaCollection('respaldos_bd');

    foreach ([$this->colaborador, $this->cliente] as $usuario) {
        $respuesta = $this->actingAs($usuario)
            ->get(route('proyectos.show', $this->proyecto).'?seccion=bitacora');

        $respuesta->assertOk();

        expect($respuesta->viewData('page')['props'])
            ->not->toHaveKey('respaldos')
            ->and($respuesta->viewData('page')['props']['puede']['gestionarRespaldos'])->toBeFalse();
    }

    // El líder sí la recibe.
    $conRespaldos = $this->actingAs($this->lider)
        ->get(route('proyectos.show', $this->proyecto).'?seccion=bitacora');

    expect($conRespaldos->viewData('page')['props'])->toHaveKey('respaldos')
        ->and($conRespaldos->viewData('page')['props']['respaldos'])->toHaveCount(1);
});

it('la clave adjuntos no existe en el JSON de una tarea para un cliente', function () {
    Storage::fake('public');
    $tarea = Tarea::factory()->for($this->proyecto)->create();
    $tarea->addMedia(UploadedFile::fake()->image('captura.jpg'))->toMediaCollection('imagenes');

    $comoCliente = $this->actingAs($this->cliente)->get(route('tareas.show', $tarea));
    $comoCliente->assertOk();

    expect($comoCliente->viewData('page')['props'])
        ->not->toHaveKey('adjuntos')
        ->and($comoCliente->viewData('page')['props']['puede']['verAdjuntos'])->toBeFalse();

    $comoColaborador = $this->actingAs($this->colaborador)->get(route('tareas.show', $tarea));

    expect($comoColaborador->viewData('page')['props'])->toHaveKey('adjuntos')
        ->and($comoColaborador->viewData('page')['props']['adjuntos']['imagenes'])->toHaveCount(1);
});

it('el título de una reunión interna vinculada no aparece en el JSON de la tarea para un cliente', function () {
    $tarea = Tarea::factory()->for($this->proyecto)->create();

    $interna = Reunion::factory()->for($this->proyecto)->create(['titulo' => 'Comité interno de márgenes']);
    $interna->tareas()->attach($tarea->id);

    $compartida = Reunion::factory()->for($this->proyecto)->visibleParaCliente()->create(['titulo' => 'Revisión con el cliente']);
    $compartida->tareas()->attach($tarea->id);

    $respuesta = $this->actingAs($this->cliente)->get(route('tareas.show', $tarea));
    $respuesta->assertOk();

    $titulos = collect($respuesta->viewData('page')['props']['reuniones'])->pluck('titulo')->all();

    expect($titulos)->toBe([$compartida->titulo])
        ->and($titulos)->not->toContain($interna->titulo);
});
