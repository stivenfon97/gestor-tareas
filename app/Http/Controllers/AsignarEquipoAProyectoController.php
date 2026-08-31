<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AsignarEquipoAProyecto;
use App\Enums\RolProyecto;
use App\Http\Requests\Gestion\AsignarEquipoAProyectoRequest;
use App\Models\Equipo;
use App\Models\Proyecto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AsignarEquipoAProyectoController extends Controller
{
    public function store(
        AsignarEquipoAProyectoRequest $request,
        Proyecto $proyecto,
        AsignarEquipoAProyecto $asignar,
    ): RedirectResponse {
        Gate::authorize('gestionarMiembros', $proyecto);

        $datos = $request->validated();

        $resumen = $asignar(
            Equipo::query()->findOrFail($datos['equipo_id']),
            $proyecto,
            RolProyecto::from($datos['rol']),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('proyectos.miembros.equipo_asignado', [
                'agregados' => count($resumen['agregados']),
                'omitidos' => count($resumen['omitidos']),
            ]),
        ]);

        return back()->with('resumenAsignacion', $resumen);
    }
}
