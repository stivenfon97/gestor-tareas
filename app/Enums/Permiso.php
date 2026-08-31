<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Permisos del sistema, con nomenclatura `recurso.accion`.
 *
 * Se siembran como filas de `permissions` y se asignan a los roles globales.
 * Son el techo: tener `pagos.registrar` no basta si en ese proyecto tu
 * `RolProyecto` no lo permite.
 */
enum Permiso: string
{
    case ClientesCrear = 'clientes.crear';
    case ProyectosEditar = 'proyectos.editar';
    case TareasCrear = 'tareas.crear';
    case TareasEditar = 'tareas.editar';
    case TareasMover = 'tareas.mover';
    case PeriodosCerrar = 'periodos.cerrar';
    /** Separado de `pagos.registrar`: un observador interno puede necesitar verlos sin crearlos. */
    case PagosVer = 'pagos.ver';
    case PagosRegistrar = 'pagos.registrar';
    case ReunionesCrear = 'reuniones.crear';
    case ArchivosSubir = 'archivos.subir';
    case ReportesGenerar = 'reportes.generar';
    case UsuariosGestionar = 'usuarios.gestionar';

    public function etiqueta(): string
    {
        return 'enums.permiso.'.str_replace('.', '_', $this->value);
    }

    public function token(): string
    {
        return 'permiso-'.str_replace('.', '-', $this->value);
    }
}
