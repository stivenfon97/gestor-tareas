<?php

declare(strict_types=1);

use App\Enums\EstadoPeriodo;
use App\Enums\EstadoProyecto;
use App\Enums\EstadoTarea;
use App\Enums\OrigenTasaCambio;
use App\Enums\PrioridadTarea;
use App\Enums\RolProyecto;
use App\Enums\TipoReporte;
use App\Enums\TipoReunion;
use App\Enums\TipoTarea;
use App\Enums\Visibilidad;
use Illuminate\Support\Facades\Lang;

/** @return array<int, class-string<BackedEnum>> */
function enumsDeDominio(): array
{
    return [
        EstadoTarea::class,
        TipoTarea::class,
        PrioridadTarea::class,
        EstadoProyecto::class,
        EstadoPeriodo::class,
        RolProyecto::class,
        Visibilidad::class,
        TipoReunion::class,
        TipoReporte::class,
        OrigenTasaCambio::class,
    ];
}

it('define los diez enums del dominio', function () {
    expect(enumsDeDominio())->toHaveCount(10);
});

it('etiqueta() devuelve una clave de traducción, nunca texto literal', function () {
    foreach (enumsDeDominio() as $enum) {
        foreach ($enum::cases() as $caso) {
            expect($caso->etiqueta())
                ->toStartWith('enums.')
                ->toEndWith('.'.$caso->value);
        }
    }
});

it('cada clave de etiqueta existe en español y en inglés', function () {
    foreach (enumsDeDominio() as $enum) {
        foreach ($enum::cases() as $caso) {
            $clave = $caso->etiqueta();

            expect(Lang::has($clave, 'es'))->toBeTrue("Falta {$clave} en lang/es.")
                ->and(Lang::has($clave, 'en'))->toBeTrue("Falta {$clave} en lang/en.");
        }
    }
});

it('token() nunca devuelve un hex ni una utilidad de Tailwind', function () {
    foreach (enumsDeDominio() as $enum) {
        foreach ($enum::cases() as $caso) {
            expect($caso->token())
                ->not->toContain('#')
                ->not->toStartWith('bg-')
                ->not->toStartWith('text-')
                ->not->toStartWith('border-')
                ->toMatch('/^[a-z0-9-]+$/');
        }
    }
});

it('precarga la prioridad alta solo en corrección y soporte', function () {
    expect(TipoTarea::Correccion->prioridadPorDefecto())->toBe(PrioridadTarea::Alta)
        ->and(TipoTarea::Soporte->prioridadPorDefecto())->toBe(PrioridadTarea::Alta)
        ->and(TipoTarea::Desarrollo->prioridadPorDefecto())->toBe(PrioridadTarea::Media)
        ->and(TipoTarea::Mejora->prioridadPorDefecto())->toBe(PrioridadTarea::Media)
        ->and(TipoTarea::Investigacion->prioridadPorDefecto())->toBe(PrioridadTarea::Media);
});

it('los dos reportes llevan sufijos distintos', function () {
    expect(TipoReporte::Completo->sufijoArchivo())
        ->not->toBe(TipoReporte::Cliente->sufijoArchivo());
});
