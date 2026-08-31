<?php

declare(strict_types=1);

use App\Enums\EstadoTarea;

/**
 * Estas pruebas no arrancan la aplicación, así que `base_path()` no existe aquí.
 */
function raizDelProyecto(string $ruta): string
{
    return dirname(__DIR__, 2).'/'.$ruta;
}

/**
 * La matriz de transiciones vive en tres sitios: el enum, la skill y el
 * diagrama. Esta prueba los compara en cada corrida para que no puedan
 * divergir en silencio.
 */
$estados = fn (): array => array_map(
    static fn (EstadoTarea $estado): string => $estado->value,
    EstadoTarea::cases(),
);

/**
 * Lee una tabla markdown de transiciones y devuelve `origen => [destinos]`.
 *
 * Solo mira las filas cuyo encabezado en negrita es uno de los seis estados:
 * los documentos tienen otras tablas que también empiezan por `| **`.
 *
 * @param  array<int, string>  $estados
 * @return array<string, array<int, string>>
 */
function matrizDesdeMarkdown(string $ruta, array $estados): array
{
    $matriz = [];

    foreach (file($ruta) as $linea) {
        if (preg_match('/^\|\s*\*\*([a-z_]+)\*\*\s*\|(.+)\|\s*$/u', trim($linea), $captura) !== 1) {
            continue;
        }

        $origen = $captura[1];

        if (! in_array($origen, $estados, strict: true)) {
            continue;
        }

        $celdas = array_map('trim', explode('|', $captura[2]));

        expect($celdas)->toHaveCount(count($estados), "La fila «{$origen}» de {$ruta} no tiene una celda por estado.");

        $matriz[$origen] = [];

        foreach ($celdas as $indice => $celda) {
            if ($celda === '✔') {
                $matriz[$origen][] = $estados[$indice];
            }
        }
    }

    return $matriz;
}

/**
 * Lee las aristas del `stateDiagram-v2`, ignorando las de inicio y fin.
 *
 * @return array<string, array<int, string>>
 */
function matrizDesdeMermaid(string $ruta): array
{
    $matriz = [];

    foreach (file($ruta) as $linea) {
        if (preg_match('/^\s*([a-z_]+)\s*-->\s*([a-z_]+)\s*:/u', $linea, $captura) !== 1) {
            continue;
        }

        $matriz[$captura[1]][] = $captura[2];
    }

    return $matriz;
}

/**
 * @return array<string, array<int, string>>
 */
function matrizDesdeEnum(): array
{
    $matriz = [];

    foreach (EstadoTarea::cases() as $estado) {
        $matriz[$estado->value] = array_map(
            static fn (EstadoTarea $destino): string => $destino->value,
            $estado->transicionesPermitidas(),
        );
    }

    return $matriz;
}

/**
 * @param  array<string, array<int, string>>  $matriz
 * @return array<string, array<int, string>>
 */
function normalizar(array $matriz): array
{
    ksort($matriz);

    foreach ($matriz as $origen => $destinos) {
        sort($destinos);
        $matriz[$origen] = array_values($destinos);
    }

    return $matriz;
}

it('el enum implementa exactamente once transiciones', function () {
    $total = array_sum(array_map('count', matrizDesdeEnum()));

    expect($total)->toBe(11);
});

it('la matriz de la skill coincide con el enum', function () use ($estados) {
    $skill = matrizDesdeMarkdown(raizDelProyecto('.ai/skills/estados-de-tarea/SKILL.md'), $estados());

    expect(normalizar($skill))->toBe(normalizar(matrizDesdeEnum()));
});

it('la matriz del diagrama coincide con el enum', function () use ($estados) {
    $diagrama = matrizDesdeMarkdown(raizDelProyecto('docs/diagramas/ciclo-de-vida-tarea.md'), $estados());

    expect(normalizar($diagrama))->toBe(normalizar(matrizDesdeEnum()));
});

it('las aristas del diagrama mermaid coinciden con el enum', function () {
    $aristas = matrizDesdeMermaid(raizDelProyecto('docs/diagramas/ciclo-de-vida-tarea.md'));
    $enum = array_filter(matrizDesdeEnum(), static fn (array $destinos): bool => $destinos !== []);

    expect(normalizar($aristas))->toBe(normalizar($enum));
});

it('la skill instalada en .claude es idéntica a la fuente en .ai', function () {
    expect(file_get_contents(raizDelProyecto('.claude/skills/estados-de-tarea/SKILL.md')))
        ->toBe(file_get_contents(raizDelProyecto('.ai/skills/estados-de-tarea/SKILL.md')));
});

it('entregada y cancelada son terminales y no admiten ninguna salida', function () {
    foreach ([EstadoTarea::Entregada, EstadoTarea::Cancelada] as $estado) {
        expect($estado->esTerminal())->toBeTrue()
            ->and($estado->transicionesPermitidas())->toBe([]);
    }

    foreach ([EstadoTarea::Pendiente, EstadoTarea::EnProceso, EstadoTarea::EnEspera, EstadoTarea::Terminada] as $estado) {
        expect($estado->esTerminal())->toBeFalse();
    }
});

it('no existe la transición de terminada a en_espera', function () {
    expect(EstadoTarea::Terminada->puedeTransicionarA(EstadoTarea::EnEspera))->toBeFalse();
});

it('ningún token de estado devuelve un color literal', function () {
    foreach (EstadoTarea::cases() as $estado) {
        expect($estado->token())
            ->not->toContain('#')
            ->not->toStartWith('bg-')
            ->not->toStartWith('text-');
    }
});
