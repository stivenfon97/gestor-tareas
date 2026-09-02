<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permiso;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Services\Adjuntos;
use App\Services\ResolverRolProyecto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Adjuntos de una tarea: `imagenes` y `documentos` únicamente. `respaldos_bd`
 * no cuelga de tarea, solo de proyecto (`RespaldoController`).
 */
class AdjuntoTareaController extends Controller
{
    public function store(Request $request, Tarea $tarea): RedirectResponse
    {
        $this->autorizarEscritura($request, $tarea->proyecto);

        $coleccion = $request->validate([
            'coleccion' => ['required', Rule::in(['imagenes', 'documentos'])],
        ])['coleccion'];

        $archivo = $request->validate([
            'archivo' => Adjuntos::reglasDeArchivo($coleccion),
        ])['archivo'];

        $tarea->addMedia($archivo)->toMediaCollection($coleccion);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('archivos.subido')]);

        return back();
    }

    public function destroy(Tarea $tarea, Media $media): RedirectResponse
    {
        Gate::authorize('delete', $media);
        abort_unless($media->model_type === Tarea::class && $media->model_id === $tarea->getKey(), 404);

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
