<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Unifica el collation de todo el esquema en `utf8mb4_0900_ai_ci`: el nativo
 * de MySQL 8 (más preciso y más rápido que `utf8mb4_unicode_ci`, el que
 * traía Laravel por defecto). Corrige de paso una inconsistencia que ya
 * existía por accidente: el *schema* completo había quedado en
 * `utf8mb4_0900_ai_ci` (el default de MySQL 8.4 al crear la base sin
 * collation explícito), mientras que las tablas —creadas siempre con
 * collation explícito desde `config/database.php`— estaban en
 * `utf8mb4_unicode_ci`. Con esto los dos quedan iguales.
 *
 * Convierte las 33 tablas existentes, dominio y las que impone Laravel o sus
 * paquetes por igual: mezclar collations entre tablas que se cruzan en un
 * JOIN (`historial_estados.tarea_id` con `tareas.id` no aplica, son enteros,
 * pero cualquier comparación de texto entre columnas de dos tablas sí) es lo
 * que MySQL rechaza con "Illegal mix of collations".
 *
 * Solo corre en MySQL: SQLite (la conexión de test) no tiene collations de
 * MySQL y su sintaxis de ALTER es otra. No hay nada que probar ahí — la
 * suite no hace ninguna aserción sobre collation.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->convertirA('utf8mb4_0900_ai_ci');
    }

    public function down(): void
    {
        $this->convertirA('utf8mb4_unicode_ci');
    }

    private function convertirA(string $collation): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $baseDeDatos = DB::getDatabaseName();

        DB::statement(
            "ALTER DATABASE `{$baseDeDatos}` CHARACTER SET utf8mb4 COLLATE `{$collation}`"
        );

        $tablas = DB::select('SHOW TABLES');
        $columna = "Tables_in_{$baseDeDatos}";

        foreach ($tablas as $fila) {
            $tabla = $fila->$columna;

            DB::statement(
                "ALTER TABLE `{$tabla}` CONVERT TO CHARACTER SET utf8mb4 COLLATE `{$collation}`"
            );
        }
    }
};
