<?php

declare(strict_types=1);

use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Los roles globales y sus permisos no son un dato de ejemplo: son
 * infraestructura, tan necesaria para que el sistema funcione como
 * cualquier tabla. Sin ellos no existe el rol "administrador" al que
 * asignar a nadie, y no hay pantalla de registro (está deshabilitada a
 * propósito) para crear el primer usuario por otra vía.
 *
 * `RolesYPermisosSeeder` ya estaba separado del seeder de datos de
 * ejemplo justamente para poder correr "en cualquier entorno, incluido uno
 * con datos reales" (ver su propio docblock) — pero hasta ahora solo se
 * ejecutaba si alguien recordaba pasar `--seed` a `migrate`. Ponerlo en una
 * migración lo hace automático de verdad: cualquier forma de desplegar
 * este proyecto (`desplegar.sh`, `make migrate`, un `php artisan migrate`
 * suelto, CI) corre migraciones sí o sí, así que esto ya no depende de que
 * nadie se acuerde de sembrarlo aparte.
 *
 * Es seguro repetirlo: el propio seeder es idempotente (`findOrCreate` +
 * `syncPermissions`), así que un entorno donde ya corrió `db:seed` a mano
 * no gana permisos duplicados ni roles repetidos.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RolesYPermisosSeeder)->run();
    }

    /**
     * No se revierte a propósito: borrar roles y permisos en un
     * `migrate:rollback` dejaría sin acceso a cualquier usuario que ya
     * los tuviera asignados, en cualquier entorno donde esta migración ya
     * hubiera corrido — un efecto secundario mucho más grave que lo que
     * `rollback` normalmente implica.
     */
    public function down(): void {}
};
