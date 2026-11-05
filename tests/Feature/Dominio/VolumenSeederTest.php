<?php

declare(strict_types=1);

use App\Enums\EstadoPeriodo;
use App\Enums\EstadoTarea;
use App\Enums\RolGlobal;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Database\Seeders\VolumenSeeder;

/**
 * El seeder de volumen a escala reducida: lo que importa no es cuántos datos
 * siembra sino que sean datos que el sistema real podría haber producido.
 */
function sembrarVolumenReducido(): void
{
    $seeder = new class extends VolumenSeeder
    {
        protected int $totalUsuarios = 24;

        protected int $totalClientes = 3;

        protected int $proyectosPorCliente = 1;

        protected int $periodosCerradosMaximos = 1;
    };

    $seeder->setContainer(app())->run();
}

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
});

it('siembra tareas con las fechas automáticas y un solo periodo abierto por proyecto', function () {
    sembrarVolumenReducido();

    expect(Tarea::query()
        ->whereIn('estado', [EstadoTarea::Terminada, EstadoTarea::Entregada])
        ->whereNull('fecha_fin')
        ->exists())->toBeFalse();

    Proyecto::query()->with('periodos')->get()->each(function (Proyecto $proyecto): void {
        expect($proyecto->periodos->where('estado', EstadoPeriodo::Abierto))->toHaveCount(1);
    });
});

it('deja a los administradores globales como participantes de los proyectos de volumen', function () {
    sembrarVolumenReducido();

    $administrador = User::query()->where('email', 'admin@gestor.test')->sole();
    $proyectosDeVolumen = Proyecto::query()->whereHas('cliente', fn ($consulta) => $consulta
        ->where('contacto_email', 'like', '%@'.VolumenSeeder::DOMINIO))->pluck('id');

    expect($proyectosDeVolumen)->not->toBeEmpty()
        ->and($administrador->rolGlobal())->toBe(RolGlobal::Administrador)
        ->and($administrador->proyectos()->whereIn('proyectos.id', $proyectosDeVolumen)->count())
        ->toBe($proyectosDeVolumen->count());
});

it('no vuelve a sembrar si los datos de volumen ya existen', function () {
    sembrarVolumenReducido();
    $usuarios = User::query()->count();
    $tareas = Tarea::query()->count();

    sembrarVolumenReducido();

    expect(User::query()->count())->toBe($usuarios)
        ->and(Tarea::query()->count())->toBe($tareas);
});
