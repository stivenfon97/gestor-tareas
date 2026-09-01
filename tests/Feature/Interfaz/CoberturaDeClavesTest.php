<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\Finder\Finder;

/*
 * Cobertura de claves de traducción.
 *
 * Una clave que no resuelve se muestra como su ruta —`nav.dashboard`— y pasa
 * desapercibida en toda verificación de servidor: `npm run build` compila igual
 * de bien y ninguna prueba de feature mira el texto. Esta prueba recorre los
 * componentes, saca las claves que usan y exige que existan en `es` **y** en
 * `en`.
 *
 * Es la red del lado de Pest. Las otras dos son el modo estricto de
 * `resources/js/lib/i18n.ts` en desarrollo y `make humo` en el navegador.
 */

/** Ficheros de interfaz. Lo generado (Wayfinder) no lleva texto. */
function ficherosDeInterfaz(): Finder
{
    return Finder::create()
        ->files()
        ->in(resource_path('js'))
        ->exclude(['actions', 'routes', 'wayfinder'])
        ->name(['*.vue', '*.ts']);
}

/** Los grupos que existen: un fichero por grupo en `lang/es`. */
function gruposDeTraduccion(): array
{
    return array_map(
        static fn (string $ruta): string => basename($ruta, '.php'),
        glob(lang_path('es').'/*.php') ?: [],
    );
}

/**
 * Claves escritas enteras.
 *
 * Se buscan por dos caminos, y hacen falta los dos:
 *
 * 1. Dentro de una llamada: `$t('nav.dashboard')`, `trans('comun.nombre')`.
 * 2. Sueltas, cuando la clave viaja en un dato y otro componente la traduce
 *    —`{ title: 'nav.dashboard' }` en `AppSidebar`, que `NavMain` pasa por
 *    `$t(item.title)`—. Solo cuentan las que empiezan por un grupo que existe,
 *    para no confundir una ruta de fichero o `import.meta.env` con una clave.
 *
 * @return array<string, array<int, string>> clave => ficheros donde aparece
 */
function clavesEstaticas(): array
{
    $encontradas = [];
    $grupos = implode('|', gruposDeTraduccion());

    foreach (ficherosDeInterfaz() as $fichero) {
        $contenido = $fichero->getContents();

        preg_match_all(
            '/(?<![\w$])(?:\$t|\$tChoice|trans|transChoice)\s*\(\s*[\'"]([a-zA-Z0-9_.\-]+)[\'"]/',
            $contenido,
            $enLlamada,
        );

        // Solo comilla simple: en una plantilla Vue el valor de un atributo va
        // entre comillas dobles, y `:alt="auth.user.name"` tiene exactamente
        // la misma forma que una clave sin serlo.
        preg_match_all(
            '/\'((?:'.$grupos.')(?:\.[a-z0-9_]+)+)\'/i',
            $contenido,
            $sueltas,
        );

        foreach ([...$enLlamada[1], ...$sueltas[1]] as $clave) {
            $encontradas[$clave][] = $fichero->getRelativePathname();
        }
    }

    return $encontradas;
}

/**
 * Prefijos de las claves compuestas en ejecución:
 * `` $t(`enums.estado_tarea.${estado}`) `` deja `enums.estado_tarea.`.
 *
 * De estas no se puede comprobar la clave final —el valor sale de la base de
 * datos—, pero sí que el espacio de nombres exista. Si alguien renombra
 * `enums.estado_tarea` a `enums.estados`, esto lo caza.
 *
 * @return array<string, array<int, string>> prefijo => ficheros donde aparece
 */
function prefijosDinamicos(): array
{
    $encontrados = [];

    foreach (ficherosDeInterfaz() as $fichero) {
        preg_match_all(
            '/(?<![\w$])(?:\$t|\$tChoice|trans|transChoice)\s*\(\s*`([a-zA-Z0-9_.\-]*\.)\$\{/',
            $fichero->getContents(),
            $coincidencias,
        );

        foreach ($coincidencias[1] as $prefijo) {
            $encontrados[$prefijo][] = $fichero->getRelativePathname();
        }
    }

    return $encontrados;
}

/**
 * Todas las claves de un idioma, en notación de puntos.
 *
 * @return array<int, string>
 */
function clavesDefinidas(string $idioma): array
{
    $planas = [];

    foreach (glob(lang_path($idioma).'/*.php') ?: [] as $fichero) {
        $grupo = basename($fichero, '.php');

        $planas = [...$planas, ...array_map(
            static fn (string $clave): string => "{$grupo}.{$clave}",
            array_keys(Arr::dot(require $fichero)),
        )];
    }

    return $planas;
}

it('el extractor encuentra claves', function () {
    // Sin este umbral, un extractor roto dejaría la prueba en verde sin
    // comprobar nada: es el modo de fallo de toda prueba que recorre ficheros.
    expect(clavesEstaticas())->toHaveCount(count(clavesEstaticas()))
        ->and(count(clavesEstaticas()))->toBeGreaterThan(150)
        ->and(prefijosDinamicos())->not->toBeEmpty();
});

it('toda clave usada en la interfaz existe en español y en inglés', function () {
    $faltantes = [];

    foreach (clavesEstaticas() as $clave => $ficheros) {
        foreach (['es', 'en'] as $idioma) {
            if (! Lang::has($clave, $idioma)) {
                $faltantes[] = sprintf(
                    '%s falta en lang/%s (%s)',
                    $clave,
                    $idioma,
                    implode(', ', array_unique($ficheros)),
                );
            }
        }
    }

    expect($faltantes)->toBe([], "Claves sin traducir:\n".implode("\n", $faltantes));
});

it('todo prefijo compuesto en ejecución corresponde a un grupo que existe', function () {
    $faltantes = [];

    foreach (prefijosDinamicos() as $prefijo => $ficheros) {
        foreach (['es', 'en'] as $idioma) {
            $hayAlguna = collect(clavesDefinidas($idioma))
                ->contains(fn (string $clave): bool => str_starts_with($clave, $prefijo));

            if (! $hayAlguna) {
                $faltantes[] = sprintf(
                    '%s* no tiene ninguna clave en lang/%s (%s)',
                    $prefijo,
                    $idioma,
                    implode(', ', array_unique($ficheros)),
                );
            }
        }
    }

    expect($faltantes)->toBe([], "Prefijos sin grupo:\n".implode("\n", $faltantes));
});

it('las claves de español y de inglés son exactamente las mismas', function () {
    $es = clavesDefinidas('es');
    $en = clavesDefinidas('en');

    expect(array_values(array_diff($es, $en)))
        ->toBe([], 'Claves que están en es y no en en.')
        ->and(array_values(array_diff($en, $es)))
        ->toBe([], 'Claves que están en en y no en es.');
});

/*
 * Claves huérfanas: definidas y sin usar. **No falla**: hay claves que se
 * resuelven desde PHP (`__()` en controladores y enums) y otras que esperan a
 * una fase posterior. Se listan para que se vean.
 */
it('informa de las claves definidas que nadie usa', function () {
    $usadas = array_keys(clavesEstaticas());
    $prefijos = array_keys(prefijosDinamicos());

    $huerfanas = collect(clavesDefinidas('es'))
        ->reject(fn (string $clave): bool => in_array($clave, $usadas, true))
        // Las compuestas en ejecución cuentan como usadas.
        ->reject(fn (string $clave): bool => collect($prefijos)
            ->contains(fn (string $prefijo): bool => str_starts_with($clave, $prefijo)))
        // Las que resuelve el servidor con `__()`.
        ->reject(fn (string $clave): bool => clavesUsadasEnPhp()->contains($clave))
        ->values();

    if ($huerfanas->isNotEmpty()) {
        fwrite(STDERR, sprintf(
            "\n[i18n] %d claves definidas sin uso encontrado:\n  %s\n",
            $huerfanas->count(),
            $huerfanas->implode("\n  "),
        ));
    }

    expect(true)->toBeTrue();
});

/** @return Collection<int, string> */
function clavesUsadasEnPhp(): Collection
{
    $claves = collect();

    $ficheros = Finder::create()->files()->in(app_path())->name('*.php');

    foreach ($ficheros as $fichero) {
        preg_match_all(
            '/\b(?:__|trans|trans_choice|Lang::(?:get|has|choice))\s*\(\s*[\'"]([a-zA-Z0-9_.\-]+)[\'"]/',
            $fichero->getContents(),
            $coincidencias,
        );

        $claves = $claves->merge($coincidencias[1]);
    }

    // `EstadoTarea::etiqueta()` y compañía construyen "enums.<grupo>.<caso>".
    foreach (['estado_tarea', 'tipo_tarea', 'prioridad_tarea', 'estado_proyecto', 'estado_periodo', 'rol_proyecto', 'visibilidad', 'tipo_reunion', 'tipo_reporte', 'origen_tasa_cambio'] as $grupo) {
        $claves->push("enums.{$grupo}");
    }

    return $claves->unique();
}
