<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Modulo;
use App\Models\NotaTarea;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Proyecto;
use App\Models\Reporte;
use App\Models\Reunion;
use App\Models\Tarea;
use App\Policies\ClientePolicy;
use App\Policies\EquipoPolicy;
use App\Policies\MediaPolicy;
use App\Policies\ModuloPolicy;
use App\Policies\NotaTareaPolicy;
use App\Policies\PagoPolicy;
use App\Policies\PeriodoPolicy;
use App\Policies\ProyectoPolicy;
use App\Policies\ReportePolicy;
use App\Policies\ReunionPolicy;
use App\Policies\TareaPolicy;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

it('registra las once policies del dominio', function () {
    $esperadas = [
        Cliente::class => ClientePolicy::class,
        Proyecto::class => ProyectoPolicy::class,
        Modulo::class => ModuloPolicy::class,
        Tarea::class => TareaPolicy::class,
        Periodo::class => PeriodoPolicy::class,
        Pago::class => PagoPolicy::class,
        Reunion::class => ReunionPolicy::class,
        NotaTarea::class => NotaTareaPolicy::class,
        Reporte::class => ReportePolicy::class,
        Equipo::class => EquipoPolicy::class,
        Media::class => MediaPolicy::class,
    ];

    expect($esperadas)->toHaveCount(11);

    foreach ($esperadas as $modelo => $policy) {
        expect(Gate::getPolicyFor($modelo))->toBeInstanceOf($policy);
    }
});

it('todas las policies de proyecto resuelven el rol por el mismo servicio', function () {
    $conProyecto = [
        ProyectoPolicy::class,
        ModuloPolicy::class,
        TareaPolicy::class,
        PeriodoPolicy::class,
        PagoPolicy::class,
        ReunionPolicy::class,
        NotaTareaPolicy::class,
        ReportePolicy::class,
        MediaPolicy::class,
    ];

    foreach ($conProyecto as $policy) {
        $ruta = (new ReflectionClass($policy))->getFileName();

        // Repetir la consulta al pivote en cada policy es cómo una acaba
        // desincronizada; por eso todas pasan por el mismo servicio.
        expect(str_contains(file_get_contents($ruta), 'ResolverRolProyecto'))
            ->toBeTrue("{$policy} no usa el servicio de resolución.");
    }
});

it('solo el administrador tiene un atajo por encima de las policies', function () {
    $fuente = file_get_contents(base_path('app/Providers/AppServiceProvider.php'));

    expect(substr_count($fuente, 'Gate::before'))->toBe(1)
        ->and($fuente)->toContain('esAdministrador()');
});
