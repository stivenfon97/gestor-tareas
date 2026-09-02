<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permiso;
use App\Models\Proyecto;
use App\Models\Reunion;
use App\Services\Adjuntos;
use App\Services\ResolverRolProyecto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Adjuntos de una reunión: `imagenes` y `documentos` únicamente.
 */
class AdjuntoReunionController extends Controller
{
    public function store(Request $request, Reunion $reunion): RedirectResponse
    {
        $this->autorizarEscritura($request, $reunion->proyecto);

        $coleccion = $request->validate([
            'coleccion' => ['required', Rule::in(['imagenes', 'documentos'])],
        ])['coleccion'];

        $archivo = $request->validate([
            'archivo' => Adjuntos::reglasDeArchivo($coleccion),
        ])['archivo'];

        $reunion->addMedia($archivo)->toMediaCollection($coleccion);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('archivos.subido')]);

        return back();
    }

    public function destroy(Reunion $reunion, Media $media): RedirectResponse
    {
        Gate::authorize('delete', $media);
        abort_unless($media->model_type === Reunion::class && $media->model_id === $reunion->getKey(), 404);

        $media->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('archivos.eliminado')]);

        return back();
    }

    private function autorizarEscritura(Request $request, Proyecto $proyecto): void
    {
        $usuario = $request->user();

        abort_unless(
            ResolverRolProyecto::escribe($usuario, $proyecto) && $usuario->can(Permiso::ArchivosSubir->value),
            403,
        );
    }
}
