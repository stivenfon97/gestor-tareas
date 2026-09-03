<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idioma activo, leído de la cookie `idioma` — mismo patrón que
 * `HandleAppearance` para el tema: preferencia persistida en el navegador, sin
 * columna en `users` ni ruta propia para cambiarla.
 *
 * El texto de la interfaz lo resuelve `laravel-vue-i18n` en el cliente
 * (`resources/js/composables/useIdioma.ts` llama a `loadLanguageAsync()` sin
 * recargar la página), así que esto no existe para pintar nada: existe para
 * los sitios que sí se resuelven en el servidor — los mensajes de validación
 * de Laravel (`lang/{es,en}/validation.php`), el `diffForHumans()` de Carbon
 * (`SecurityController`: sin esto, "hace 3 días" salía siempre en inglés,
 * fuera cual fuera el idioma de la interfaz — Carbon no sigue
 * `app()->getLocale()` solo) y el `lang` del `<html>` en la primera carga
 * (`resources/views/app.blade.php`).
 */
class HandleIdioma
{
    /** @var array<int, string> */
    private const IDIOMAS_DISPONIBLES = ['es', 'en'];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $idioma = $request->cookie('idioma');

        if (is_string($idioma) && in_array($idioma, self::IDIOMAS_DISPONIBLES, true)) {
            app()->setLocale($idioma);
        }

        Carbon::setLocale(app()->getLocale());

        return $next($request);
    }
}
