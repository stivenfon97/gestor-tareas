<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\VistaGuardada;

/**
 * Las vistas guardadas son **privadas de quien las creó**.
 *
 * No hay `Gate::before` que valga aquí más que el de `administrador`, y aun así
 * la pregunta no es «¿puede este usuario ver vistas?» sino «¿es suya?». Una
 * vista lleva los filtros con los que alguien organiza su trabajo; que otro la
 * vea no filtra datos, pero que otro la edite le cambia la pantalla sin avisar.
 */
class VistaGuardadaPolicy
{
    public function view(User $usuario, VistaGuardada $vista): bool
    {
        return $this->esSuya($usuario, $vista);
    }

    public function update(User $usuario, VistaGuardada $vista): bool
    {
        return $this->esSuya($usuario, $vista);
    }

    public function delete(User $usuario, VistaGuardada $vista): bool
    {
        return $this->esSuya($usuario, $vista);
    }

    /**
     * La propiedad se comprueba contra `usuario_id`, no contra el proyecto.
     *
     * Una vista de un proyecto sigue siendo de su autor: que dos personas
     * participen en el mismo proyecto no las hace dueñas de las vistas de la
     * otra.
     */
    private function esSuya(User $usuario, VistaGuardada $vista): bool
    {
        return $vista->usuario_id === $usuario->getKey();
    }
}
