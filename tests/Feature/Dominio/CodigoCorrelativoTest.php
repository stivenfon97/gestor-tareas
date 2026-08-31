<?php

declare(strict_types=1);

use App\Models\ContadorTarea;
use App\Models\Proyecto;
use App\Models\Tarea;
use Illuminate\Database\UniqueConstraintViolationException;

it('numera las tareas con el prefijo del proyecto y cuatro dígitos', function () {
    $proyecto = Proyecto::factory()->create(['prefijo_tarea' => 'MAQ']);

    $tarea = Tarea::factory()->for($proyecto)->create();

    expect($tarea->codigo)->toBe('MAQ-0001');
});

it('crea veinte tareas del mismo proyecto sin códigos repetidos ni huecos', function () {
    $proyecto = Proyecto::factory()->create(['prefijo_tarea' => 'SEC']);

    Tarea::factory()->count(20)->for($proyecto)->create();

    $codigos = $proyecto->tareas()->orderBy('id')->pluck('codigo')->all();

    expect($codigos)->toHaveCount(20)
        ->and(array_unique($codigos))->toHaveCount(20);

    foreach ($codigos as $indice => $codigo) {
        expect($codigo)->toBe(sprintf('SEC-%04d', $indice + 1));
    }
});

it('numera cada proyecto por separado', function () {
    $uno = Proyecto::factory()->create(['prefijo_tarea' => 'AAA']);
    $otro = Proyecto::factory()->create(['prefijo_tarea' => 'BBB']);

    Tarea::factory()->count(3)->for($uno)->create();
    $tarea = Tarea::factory()->for($otro)->create();

    expect($tarea->codigo)->toBe('BBB-0001');
});

it('no reutiliza el código de una tarea borrada por completo', function () {
    $proyecto = Proyecto::factory()->create(['prefijo_tarea' => 'DEL']);

    $primera = Tarea::factory()->for($proyecto)->create();
    expect($primera->codigo)->toBe('DEL-0001');

    // Un `max(codigo) + 1` volvería a entregar DEL-0001 después de esto.
    $primera->forceDelete();

    expect(Tarea::factory()->for($proyecto)->create()->codigo)->toBe('DEL-0002');
});

it('el correlativo no se reinicia al cambiar de periodo', function () {
    $proyecto = Proyecto::factory()->create(['prefijo_tarea' => 'CON']);

    Tarea::factory()->count(2)->for($proyecto)->create();

    $contador = ContadorTarea::query()->where('proyecto_id', $proyecto->id)->sole();

    expect($contador->ultimo_numero)->toBe(2);

    expect(Tarea::factory()->for($proyecto)->create()->codigo)->toBe('CON-0003');
});

it('cada proyecto nace con su contador en cero', function () {
    $proyecto = Proyecto::factory()->create();

    expect(ContadorTarea::query()->where('proyecto_id', $proyecto->id)->sole()->ultimo_numero)->toBe(0);
});

it('el código es único a nivel de base de datos', function () {
    $proyecto = Proyecto::factory()->create(['prefijo_tarea' => 'UNI']);
    $tarea = Tarea::factory()->for($proyecto)->create();

    expect(fn () => Tarea::factory()->for($proyecto)->create(['codigo' => $tarea->codigo]))
        ->toThrow(UniqueConstraintViolationException::class);
});
