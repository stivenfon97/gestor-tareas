<?php

declare(strict_types=1);

use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Models\Proyecto;
use App\Models\Reunion;
use App\Models\Tarea;
use App\Services\Adjuntos;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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

it('acepta una imagen dentro del límite y genera la miniatura', function () {
    Storage::fake('public');

    $tarea = Tarea::factory()->for($this->proyecto)->create();
    $imagen = UploadedFile::fake()->image('captura.jpg', 200, 200);

    $this->actingAs($this->lider)
        ->post(route('tareas.adjuntos.store', $tarea), ['coleccion' => 'imagenes', 'archivo' => $imagen])
        ->assertRedirect();

    $media = $tarea->fresh()->getMedia('imagenes')->sole();

    expect($media->hasGeneratedConversion('miniatura'))->toBeTrue();
});

it('rechaza una imagen por encima de 5 MB', function () {
    Storage::fake('public');

    $tarea = Tarea::factory()->for($this->proyecto)->create();
    $pesada = UploadedFile::fake()->create('pesada.jpg', 6000, 'image/jpeg');

    $this->actingAs($this->lider)
        ->post(route('tareas.adjuntos.store', $tarea), ['coleccion' => 'imagenes', 'archivo' => $pesada])
        ->assertInvalid(['archivo']);

    expect($tarea->fresh()->getMedia('imagenes'))->toHaveCount(0);
});

it('rechaza un documento por encima de 20 MB', function () {
    Storage::fake('public');

    $tarea = Tarea::factory()->for($this->proyecto)->create();
    $pesado = UploadedFile::fake()->create('contrato.pdf', 21000, 'application/pdf');

    $this->actingAs($this->lider)
        ->post(route('tareas.adjuntos.store', $tarea), ['coleccion' => 'documentos', 'archivo' => $pesado])
        ->assertInvalid(['archivo']);
});

it('no genera ninguna conversión para la colección documentos', function () {
    Storage::fake('public');

    $tarea = Tarea::factory()->for($this->proyecto)->create();
    // Contenido real (no `create()`, que deja el archivo vacío): Medialibrary
    // comprueba el mime real del archivo en disco, no lo que se le declare.
    $documento = UploadedFile::fake()->createWithContent('notas.txt', 'Notas de la reunión de seguimiento.');

    $this->actingAs($this->lider)
        ->post(route('tareas.adjuntos.store', $tarea), ['coleccion' => 'documentos', 'archivo' => $documento])
        ->assertRedirect();

    $media = $tarea->fresh()->getMedia('documentos')->sole();

    expect($media->getGeneratedConversions())->toBeEmpty();
});

it('rechaza un .sql cuyo contenido real es un .php (extensión falsificada)', function () {
    Storage::fake('local');

    $disfrazado = UploadedFile::fake()->create('respaldo.sql', 10, 'application/x-httpd-php');

    $this->actingAs($this->lider)
        ->post(route('proyectos.respaldos.store', $this->proyecto), ['archivo' => $disfrazado])
        ->assertInvalid(['archivo']);

    expect($this->proyecto->fresh()->getMedia('respaldos_bd'))->toHaveCount(0);
});

it('rechaza un respaldo por encima de 256 MB', function () {
    Storage::fake('local');

    $enorme = UploadedFile::fake()->create('respaldo.sql', 260 * 1024, 'application/sql');

    $this->actingAs($this->lider)
        ->post(route('proyectos.respaldos.store', $this->proyecto), ['archivo' => $enorme])
        ->assertInvalid(['archivo']);
});

it('niega la subida de un respaldo a un colaborador y a un cliente', function () {
    Storage::fake('local');

    $archivo = UploadedFile::fake()->create('respaldo.sql', 10, 'application/sql');

    $this->actingAs($this->colaborador)
        ->post(route('proyectos.respaldos.store', $this->proyecto), ['archivo' => $archivo])
        ->assertForbidden();

    $this->actingAs($this->cliente)
        ->post(route('proyectos.respaldos.store', $this->proyecto), ['archivo' => $archivo])
        ->assertForbidden();

    expect($this->proyecto->fresh()->getMedia('respaldos_bd'))->toHaveCount(0);
});

it('el líder sube un respaldo válido por la ruta controlada', function () {
    Storage::fake('local');

    $archivo = UploadedFile::fake()->createWithContent('respaldo.sql', "-- dump\nINSERT INTO tareas VALUES (1);\n");

    $this->actingAs($this->lider)
        ->post(route('proyectos.respaldos.store', $this->proyecto), ['archivo' => $archivo])
        ->assertRedirect();

    expect($this->proyecto->fresh()->getMedia('respaldos_bd'))->toHaveCount(1);
});

it('borrar una reunión borra sus adjuntos del disco, no solo las filas', function () {
    Storage::fake('public');

    $reunion = Reunion::factory()->for($this->proyecto)->create();
    $reunion->addMedia(UploadedFile::fake()->image('captura.jpg'))->toMediaCollection('imagenes');

    $media = $reunion->getMedia('imagenes')->sole();
    $ruta = $media->getPathRelativeToRoot();

    Storage::disk('public')->assertExists($ruta);

    $reunion->delete();

    expect(Media::query()->whereKey($media->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing($ruta);
});

it('Adjuntos::comoArray nunca expone una URL de disco directa', function () {
    Storage::fake('public');

    $reunion = Reunion::factory()->for($this->proyecto)->create();
    $reunion->addMedia(UploadedFile::fake()->image('captura.jpg'))->toMediaCollection('imagenes');

    $media = $reunion->getMedia('imagenes')->sole();
    $array = Adjuntos::comoArray($media);

    expect($array['url'])->toContain('/archivos/')
        ->and($array['url'])->not->toContain('/storage/');
});
