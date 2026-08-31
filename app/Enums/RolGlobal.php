<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Rol de un usuario en el sistema entero, respaldado por
 * `spatie/laravel-permission`.
 *
 * Es el **techo** de lo que alguien puede hacer. El alcance concreto dentro de
 * un proyecto lo fija `RolProyecto`, y entre los dos **gana siempre el más
 * restrictivo**.
 */
enum RolGlobal: string
{
    case Administrador = 'administrador';
    case Miembro = 'miembro';
    case Invitado = 'invitado';

    public function etiqueta(): string
    {
        return 'enums.rol_global.'.$this->value;
    }

    public function token(): string
    {
        return 'rol-global-'.str_replace('_', '-', $this->value);
    }

    /**
     * `invitado` es de solo lectura en todo el sistema, tenga el rol de
     * proyecto que tenga.
     */
    public function puedeEscribir(): bool
    {
        return $this !== self::Invitado;
    }

    /**
     * Quién puede ver contenido interno —notas y reuniones sin marcar, pagos,
     * reportes—.
     *
     * `invitado` es el rol de la gente del cliente: **nunca** ve lo interno,
     * sea cual sea su rol de proyecto. Ser del cliente es una condición de la
     * persona, no del proyecto; si dependiera solo del rol de proyecto, elegir
     * mal en un desplegable sería una filtración.
     */
    public function veLoInterno(): bool
    {
        return $this !== self::Invitado;
    }

    /**
     * Permisos que el seeder asigna a este rol.
     *
     * @return array<int, Permiso>
     */
    public function permisos(): array
    {
        return match ($this) {
            self::Administrador => Permiso::cases(),
            self::Miembro => [
                Permiso::ProyectosEditar,
                Permiso::TareasCrear,
                Permiso::TareasEditar,
                Permiso::TareasMover,
                Permiso::PeriodosCerrar,
                Permiso::PagosVer,
                Permiso::PagosRegistrar,
                Permiso::ReunionesCrear,
                Permiso::ArchivosSubir,
                Permiso::ReportesGenerar,
            ],
            self::Invitado => [],
        };
    }
}
