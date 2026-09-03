<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Services\Adjuntos;
use App\Services\ResolverRolProyecto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Respaldos `.sql` del proyecto. Colección `respaldos_bd` únicamente: nunca
 * `imagenes` ni `documentos`, que cuelgan de tarea y reunión, no de proyecto.
 *
 * Solo `lider` o `administrador` (`ResolverRolProyecto::gestiona`) — ni
 * siquiera un `colaborador` interno, porque un dump lleva la base entera.
 */
class RespaldoController extends Controller
{
    public function store(Request $request, Proyecto $proyecto): RedirectResponse
    {
        abort_unless(ResolverRolProyecto::gestiona($request->user(), $proyecto), 403);

        $datos = $request->validate([
            'archivo' => Adjuntos::reglasDeArchivo('respaldos_bd'),
        ]);

        $proyecto->addMedia($datos['archivo'])->toMediaCollection('respaldos_bd');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('archivos.subido')]);

        return back();
    }

    public function destroy(Request $request, Proyecto $proyecto, Media $media): RedirectResponse
    {
        abort_unless(ResolverRolProyecto::gestiona($request->user(), $proyecto), 403);
        abort_unless($media->model_type === Proyecto::class && $media->model_id === $proyecto->getKey(), 404);

        $media->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('archivos.eliminado')]);

        return back();
    }
}
