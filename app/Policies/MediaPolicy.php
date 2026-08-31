<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Visibilidad;
use App\Models\NotaTarea;
use App\Models\Proyecto;
use App\Models\Reunion;
use App\Models\Tarea;
use App\Models\User;
use App\Services\ResolverRolProyecto;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Todo adjunto se sirve por una ruta controlada que pasa por aquí. **Nunca por
 * una URL directa al disco**: un enlace público a `storage/` no pregunta quién
 * lo abre.
 */
class MediaPolicy
{
    /**
     * Colección de respaldos `.sql`. Vive en disco privado y solo la ve quien
     * lidera el proyecto (o el administrador, que entra por `Gate::before`).
     */
    public const COLECCION_RESPALDOS = 'respaldos_bd';

    public function view(User $usuario, Media $media): bool
    {
        $dueno = $media->model;
        $proyecto = $this->proyectoDe($dueno);

        if ($proyecto === null || ! ResolverRolProyecto::participa($usuario, $proyecto)) {
            return false;
        }

        if ($media->collection_name === self::COLECCION_RESPALDOS) {
            // Ni siquiera marcado como visible: un dump lleva la base entera.
            return ResolverRolProyecto::gestiona($usuario, $proyecto);
        }

        if (ResolverRolProyecto::veLoInterno($usuario, $proyecto)) {
            return true;
        }

        return $this->visibilidadDe($dueno) === Visibilidad::Cliente;
    }

    public function delete(User $usuario, Media $media): bool
    {
        $proyecto = $this->proyectoDe($media->model);

        return $proyecto !== null && ResolverRolProyecto::gestiona($usuario, $proyecto);
    }

    private function proyectoDe(mixed $dueno): ?Proyecto
    {
        return match (true) {
            $dueno instanceof Proyecto => $dueno,
            $dueno instanceof Tarea => $dueno->proyecto,
            $dueno instanceof Reunion => $dueno->proyecto,
            $dueno instanceof NotaTarea => $dueno->tarea->proyecto,
            default => null,
        };
    }

    /**
     * Un adjunto hereda la visibilidad de aquello a lo que está pegado. Los
     * adjuntos de un proyecto o de una tarea no tienen marca propia, así que se
     * tratan como internos.
     */
    private function visibilidadDe(mixed $dueno): Visibilidad
    {
        return match (true) {
            $dueno instanceof Reunion, $dueno instanceof NotaTarea => $dueno->visibilidad,
            default => Visibilidad::Interna,
        };
    }
}
