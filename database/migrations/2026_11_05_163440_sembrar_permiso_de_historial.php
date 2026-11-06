<?php

declare(strict_types=1);

use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Siembra `historial.ver`, el permiso de la pantalla global de historial de
 * cambios. Se vuelve a correr el seeder entero porque es idempotente y es el
 * único que sabe qué permisos lleva cada rol (`RolGlobal::permisos()`).
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RolesYPermisosSeeder)->run();
    }

    /**
     * No se revierte: igual que `sembrar_roles_y_permisos`, quitar permisos ya
     * asignados es un efecto secundario más grave que el rollback.
     */
    public function down(): void {}
};
