<?php

use App\Enums\RolGlobal;
use App\Enums\RolProyecto;
use App\Enums\Visibilidad;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Crea un usuario con su rol global sembrado.
 *
 * Los roles y permisos deben existir: llama antes a
 * `seed(RolesYPermisosSeeder::class)` en el `beforeEach` de la prueba.
 */
function usuarioCon(RolGlobal $rolGlobal): User
{
    $usuario = User::factory()->create(['activo' => true]);
    $usuario->syncRoles([$rolGlobal->value]);

    return $usuario->fresh();
}

/**
 * Da de alta al usuario en el proyecto con un rol. El permiso efectivo se
 * resuelve siempre desde este pivote.
 */
function asignarAProyecto(
    User $usuario,
    Proyecto $proyecto,
    RolProyecto $rol,
): User {
    $proyecto->usuarios()->syncWithoutDetaching([
        $usuario->getKey() => ['rol' => $rol->value],
    ]);

    return $usuario;
}

/**
 * Documento TipTap mínimo pero válido: un párrafo con texto. Usado por las
 * pruebas de la bitácora (fase 10) para probar `ReunionRequest` sin acoplarse
 * a `ReunionFactory::documentoTipTap()`, que vive en `database/factories`.
 *
 * @return array<string, mixed>
 */
function documentoTipTapValido(): array
{
    return [
        'type' => 'doc',
        'content' => [[
            'type' => 'paragraph',
            'content' => [['type' => 'text', 'text' => 'Contenido de prueba.']],
        ]],
    ];
}

/**
 * Payload válido para `proyectos.reuniones.store` / `reuniones.update`.
 *
 * @param  array<string, mixed>  $sobrescribir
 * @return array<string, mixed>
 */
function datosDeReunionValidos(array $sobrescribir = []): array
{
    return array_merge([
        'fecha' => now()->toDateTimeString(),
        'tipo' => 'reunion',
        'titulo' => 'Reunión de prueba',
        'contenido_json' => documentoTipTapValido(),
        'contenido_html' => '<p>Contenido de prueba.</p>',
        'acuerdos' => null,
        'visibilidad' => Visibilidad::Interna->value,
        'asistentes' => [['usuario_id' => null, 'nombre' => 'Alguien', 'organizacion' => null]],
    ], $sobrescribir);
}

/**
 * Carga en memoria el `.xlsx` de una respuesta de descarga (`Excel::download()`).
 *
 * `BinaryFileResponse::getContent()` devuelve `false` -el contenido se sirve
 * por `readfile()`, no como cadena en memoria-, así que la única forma de
 * comprobar la estructura real del archivo es leer el fichero temporal desde
 * disco. En una petición de prueba nadie llama a `send()`, así que
 * `deleteFileAfterSend(true)` no se dispara y el temporal sigue ahí para
 * leerlo.
 */
function leerExcelDescargado(TestResponse $respuesta): Spreadsheet
{
    $respuesta->assertOk();

    /** @var BinaryFileResponse $base */
    $base = $respuesta->baseResponse;
    $ruta = $base->getFile()->getPathname();

    $libro = IOFactory::load($ruta);

    @unlink($ruta);

    return $libro;
}

/**
 * Cuenta las consultas que dispara una petición.
 *
 * Compartida desde la fase 12: hasta entonces cada prueba de techo de
 * consultas (`ArbolDeModulosTest`, `TableroTest`) declaraba su propia copia
 * como función global de archivo, y con la segunda `DashboardTest` chocaron
 * -PHP no aísla funciones sueltas por archivo dentro del mismo proceso-, así
 * que a partir de dos consumidores vale más centralizarla aquí.
 */
function consultasDe(callable $peticion): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $peticion();

    $total = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $total;
}
