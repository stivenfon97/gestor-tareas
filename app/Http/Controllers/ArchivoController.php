<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Única puerta de salida de los adjuntos.
 *
 * **Ningún archivo se sirve por URL directa al disco.** Un enlace público a
 * `storage/` no pregunta quién lo abre, y los respaldos `.sql` viven en disco
 * privado justamente para que no exista esa URL.
 */
class ArchivoController extends Controller
{
    public function show(Request $request, Media $media): StreamedResponse
    {
        Gate::authorize('view', $media);

        // La miniatura sigue pasando por esta misma puerta: sin ella harían
        // falta dos rutas para el mismo permiso. Solo `imagenes` la lleva
        // (D8.3), así que fuera de ahí no hay nada que servir con ese nombre.
        $conversion = (string) $request->query('conversion', '');

        if ($conversion !== '' && ($conversion !== 'miniatura' || ! $media->hasGeneratedConversion('miniatura'))) {
            abort(404);
        }

        return $media->toResponse($request, $conversion);
    }
}
