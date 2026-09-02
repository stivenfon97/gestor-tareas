<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Un solo periodo `abierto` por proyecto, **garantizado por la base de datos**.
 *
 * Hoy nada lo impide: ni el esquema ni la aplicación. Es una invariante del
 * dominio (D4.8) y dejarla en manos de la interfaz permite que dos pestañas
 * abran un periodo cada una y las dos ganen.
 *
 * Mismo mecanismo que la vista predeterminada de la fase 08
 * (`2026_09_02_041623_garantizar_una_vista_predeterminada_por_tipo.php`): ni
 * MySQL ni SQLite tienen índices únicos parciales, pero los dos ignoran NULL en
 * un índice único. Una columna generada que vale `proyecto_id` cuando el
 * periodo está `abierto` y NULL en cualquier otro caso hace que dos periodos
 * abiertos del mismo proyecto choquen entre sí —mismo valor— mientras que los
 * cerrados, todos en NULL, conviven sin estorbarse.
 *
 * **`VIRTUAL` y no `STORED`.** `STORED` es lo que usa la vista predeterminada,
 * pero ahí la expresión depende de una columna booleana sin llave foránea.
 * Aquí la expresión lee `proyecto_id`, que sí la tiene
 * (`periodos_proyecto_id_foreign`), y en MySQL 8.4 una columna generada
 * `STORED` que depende de una columna con clave foránea revienta el `ALTER`
 * con `Cannot add foreign key constraint` (error 1215) aunque la sentencia no
 * toque ninguna FK: es una limitación del `ALGORITHM` que usa InnoDB para
 * reconstruir la tabla, no una comprobación de integridad —persiste incluso
 * con `FOREIGN_KEY_CHECKS=0`—. `VIRTUAL` no reconstruye filas y no tropieza
 * con eso; MySQL indexa columnas virtuales sin problema desde 5.7, y SQLite
 * las soporta igual que las `STORED`.
 *
 * No hace falta limpiar datos existentes antes de poner el índice:
 * `DesarrolloSeeder` nunca deja más de un periodo abierto por proyecto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodos', function ($tabla): void {
            // `case when … end` y no `if()`: la suite corre sobre SQLite en
            // memoria y MySQL solo en el contenedor, y `if()` no existe en
            // SQLite.
            $tabla->unsignedBigInteger('proyecto_si_abierto')
                ->virtualAs("case when estado = 'abierto' then proyecto_id else null end")
                ->nullable();

            $tabla->unique('proyecto_si_abierto', 'periodos_un_abierto_por_proyecto');
        });
    }

    public function down(): void
    {
        Schema::table('periodos', function ($tabla): void {
            $tabla->dropUnique('periodos_un_abierto_por_proyecto');
            $tabla->dropColumn('proyecto_si_abierto');
        });
    }
};
