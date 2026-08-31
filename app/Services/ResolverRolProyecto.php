<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RolProyecto;
use App\Models\Proyecto;
use App\Models\User;

/**
 * Punto único donde se resuelve qué puede hacer alguien en un proyecto.
 *
 * Existe para que las once policies no repitan la consulta al pivote: repetirla
 * garantiza que algún día una de ellas quede desincronizada y abra un hueco.
 *
 * Dos capas:
 *
 * - **Rol global** (`spatie/laravel-permission`) — el techo de lo que alguien
 *   puede hacer en el sistema.
 * - **Rol de proyecto** (pivote `proyecto_usuario`) — el alcance dentro de ese
 *   proyecto concreto.
 *
 * **Gana siempre el más restrictivo.** Un `invitado` global con rol de proyecto
 * `lider` sigue siendo de solo lectura: si alguien asigna por error el rol de
 * proyecto equivocado a una persona del cliente, el rol global lo contiene.
 */
final class ResolverRolProyecto
{
    /**
     * Rol del usuario en ese proyecto, o `null` si no tiene fila en el pivote.
     *
     * Sin fila no hay acceso, ni siquiera de lectura. La única excepción es el
     * rol global `administrador`, y esa excepción vive en `Gate::before`, no
     * aquí.
     */
    public static function para(User $usuario, Proyecto $proyecto): ?RolProyecto
    {
        $rol = $proyecto->usuarios()
            ->whereKey($usuario->getKey())
            ->value('proyecto_usuario.rol');

        return $rol === null ? null : RolProyecto::from((string) $rol);
    }

    /**
     * Participa de verdad: tiene fila en el pivote **y** tiene rol global.
     *
     * Sin rol global no hay techo asignado, y el valor seguro por defecto es
     * ninguno. Si no se exigiera aquí, un usuario creado a mano sin rol —fuera
     * de la gestión de usuarios, que lo obliga— podría leer el proyecto entero
     * solo por tener la fila del pivote.
     */
    public static function participa(User $usuario, Proyecto $proyecto): bool
    {
        return $usuario->rolGlobal() !== null
            && self::para($usuario, $proyecto) !== null;
    }

    /**
     * Puede escribir en el proyecto: el rol de proyecto lo permite **y** el rol
     * global no lo prohíbe.
     */
    public static function escribe(User $usuario, Proyecto $proyecto): bool
    {
        return self::rolGlobalPermiteEscribir($usuario)
            && (self::para($usuario, $proyecto)?->escribeTrabajo() ?? false);
    }

    /**
     * Administra el proyecto en sí: módulos, periodos, pagos y cierre.
     */
    public static function gestiona(User $usuario, Proyecto $proyecto): bool
    {
        return self::rolGlobalPermiteEscribir($usuario)
            && (self::para($usuario, $proyecto)?->gestionaElProyecto() ?? false);
    }

    /**
     * Ve lo interno: notas y reuniones sin marcar, y todo lo que no sea pago.
     *
     * Las **dos** capas tienen que decir que sí. Un `invitado` global no ve lo
     * interno aunque su rol de proyecto sea `lider`: ser del cliente es una
     * condición de la persona, no del proyecto. Si esto dependiera solo del rol
     * de proyecto, un error de dedo en un desplegable sería una filtración.
     */
    public static function veLoInterno(User $usuario, Proyecto $proyecto): bool
    {
        if (! self::rolGlobalVeLoInterno($usuario)) {
            return false;
        }

        return self::para($usuario, $proyecto)?->veLoInterno() ?? false;
    }

    /**
     * Es del cliente en ese proyecto. Se pregunta en positivo donde la regla es
     * "esto no lo ve el cliente", para que la negación no se lea al revés.
     */
    public static function esCliente(User $usuario, Proyecto $proyecto): bool
    {
        return self::para($usuario, $proyecto) === RolProyecto::Cliente;
    }

    /**
     * Ids de los proyectos donde el usuario tiene fila en el pivote.
     *
     * Es la base de los scopes de listado: sin fila, ni una sola tarea del
     * proyecto puede aparecer en ningún listado.
     *
     * @return array<int, int>
     */
    public static function proyectosDelUsuario(User $usuario): array
    {
        return $usuario->proyectos()->pluck('proyectos.id')->all();
    }

    /**
     * Ids de los proyectos donde el usuario es del cliente.
     *
     * @return array<int, int>
     */
    public static function proyectosDondeEsCliente(User $usuario): array
    {
        return $usuario->proyectos()
            ->wherePivot('rol', RolProyecto::Cliente->value)
            ->pluck('proyectos.id')
            ->all();
    }

    /**
     * Ids de los proyectos cuyos listados se recortan a lo marcado
     * `Visibilidad::Cliente` para este usuario.
     *
     * **No es lo mismo que `proyectosDondeEsCliente`**, y de confundirlos salió
     * el agujero que esto cierra: un `invitado` global sin rol de proyecto
     * `cliente` en ninguna parte devolvía una lista vacía, así que ningún
     * listado se le recortaba y leía las reuniones internas de un proyecto en
     * el que figuraba como `lider`.
     *
     * Para un `invitado` global la respuesta son **todos** sus proyectos.
     *
     * @return array<int, int>
     */
    public static function proyectosSinAccesoInterno(User $usuario): array
    {
        if (! self::rolGlobalVeLoInterno($usuario)) {
            return self::proyectosDelUsuario($usuario);
        }

        return self::proyectosDondeEsCliente($usuario);
    }

    private static function rolGlobalPermiteEscribir(User $usuario): bool
    {
        return $usuario->rolGlobal()?->puedeEscribir() ?? false;
    }

    private static function rolGlobalVeLoInterno(User $usuario): bool
    {
        return $usuario->rolGlobal()?->veLoInterno() ?? false;
    }
}
