<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Permiso;
use App\Enums\RolGlobal;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles globales y permisos.
 *
 * Separado del seeder de datos de desarrollo a propósito: esto hay que poder
 * ejecutarlo en cualquier entorno, incluido uno con datos reales, sin arrastrar
 * clientes y tareas de mentira.
 *
 * Es idempotente: `firstOrCreate` por nombre y `syncPermissions` en cada
 * pasada, de modo que quitar un permiso del enum también lo quita del rol.
 */
class RolesYPermisosSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permiso::cases() as $permiso) {
            Permission::findOrCreate($permiso->value, 'web');
        }

        foreach (RolGlobal::cases() as $rolGlobal) {
            $rol = Role::findOrCreate($rolGlobal->value, 'web');

            $rol->syncPermissions(
                array_map(static fn (Permiso $permiso): string => $permiso->value, $rolGlobal->permisos()),
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
