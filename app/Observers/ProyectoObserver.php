<?php

declare(strict_types=1);

namespace App\Observers;

use App\Exceptions\PrefijoInmutable;
use App\Models\ContadorTarea;
use App\Models\Proyecto;

class ProyectoObserver
{
    /**
     * Cada proyecto nace con su contador de tareas en cero.
     *
     * Crearlo aquí y no la primera vez que se necesita evita que dos tareas
     * simultáneas del mismo proyecto intenten insertar la misma fila de
     * contador y una de las dos reviente contra el `unique`.
     */
    /**
     * El slug se autogenera desde el nombre si no viene uno explícito.
     *
     * Aquí y no en el controlador para que un seeder o un `tinker` tampoco
     * puedan crear un proyecto sin slug: la columna es `unique` y `NOT NULL`.
     */
    public function creating(Proyecto $proyecto): void
    {
        if (blank($proyecto->slug)) {
            $proyecto->slug = Proyecto::slugUnico((string) $proyecto->nombre);
        }
    }

    public function created(Proyecto $proyecto): void
    {
        ContadorTarea::query()->firstOrCreate(
            ['proyecto_id' => $proyecto->id],
            ['ultimo_numero' => 0],
        );
    }

    /**
     * `prefijo_tarea` es inmutable en cuanto el proyecto emite su primer
     * código.
     *
     * Va en `updating` porque tiene que poder abortar el guardado, y en el
     * observer y no en el formulario porque ninguna vía de escritura debe poder
     * saltárselo.
     *
     * La alternativa —permitir el cambio y renumerar las tareas— se descartó:
     * los códigos ya viajaron al cliente dentro de reportes emitidos, y
     * renumerarlos dejaría sin correspondencia justo los documentos que
     * justifican un cobro.
     */
    public function updating(Proyecto $proyecto): void
    {
        if (! $proyecto->isDirty('prefijo_tarea')) {
            return;
        }

        if ($proyecto->tieneCodigosEmitidos()) {
            throw PrefijoInmutable::paraProyecto($proyecto, (string) $proyecto->prefijo_tarea);
        }
    }
}
