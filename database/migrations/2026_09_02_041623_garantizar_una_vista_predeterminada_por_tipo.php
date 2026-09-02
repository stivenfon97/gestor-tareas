<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Una sola vista predeterminada por usuario y tipo, **garantizado por la base**.
 *
 * Dejarlo en manos de la interfaz o de un observer significa que dos pestañas
 * abiertas, un comando de consola o un seeder pueden dejar dos vistas marcadas
 * a la vez, y entonces «la predeterminada» pasa a ser la que devuelva primero
 * el motor: un valor que cambia solo.
 *
 * Ni MySQL ni SQLite tienen índices únicos parciales (`WHERE es_predeterminada
 * = 1`), así que se consigue con una columna generada que vale 1 cuando la
 * vista es la predeterminada y **NULL** cuando no lo es. Un índice único
 * ignora los NULL en los dos motores, así que las no predeterminadas conviven
 * sin estorbarse y las predeterminadas chocan entre sí.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         * Antes de poner la cerradura, quitar lo que ya no cabría dentro: sin
         * esto la migración falla en cuanto un entorno tenga dos marcadas.
         *
         * Los ids que se conservan se leen antes y se pasan como lista. MySQL
         * no deja actualizar una tabla leyendo de ella misma en una
         * subconsulta, y la alternativa —una tabla derivada— es más código del
         * que merece una limpieza que corre una vez.
         */
        $sobrevivientes = DB::table('vistas_guardadas')
            ->where('es_predeterminada', true)
            ->groupBy('usuario_id', 'tipo')
            ->pluck(DB::raw('max(id)'))
            ->all();

        DB::table('vistas_guardadas')
            ->where('es_predeterminada', true)
            ->when($sobrevivientes !== [], fn ($consulta) => $consulta->whereNotIn('id', $sobrevivientes))
            ->update(['es_predeterminada' => false]);

        Schema::table('vistas_guardadas', function ($tabla): void {
            /*
             * `case when … end` y no `if()`: la suite corre sobre SQLite en
             * memoria y MySQL solo en el contenedor, y `if()` no existe en
             * SQLite. Una migración que solo funciona en un motor deja de
             * probarse en el otro justo cuando más falta hace.
             */
            $tabla->boolean('marca_predeterminada')
                ->storedAs('case when es_predeterminada then 1 else null end')
                ->nullable();

            $tabla->unique(
                ['usuario_id', 'tipo', 'marca_predeterminada'],
                'vistas_guardadas_una_predeterminada_por_tipo',
            );
        });
    }

    public function down(): void
    {
        Schema::table('vistas_guardadas', function ($tabla): void {
            $tabla->dropUnique('vistas_guardadas_una_predeterminada_por_tipo');
            $tabla->dropColumn('marca_predeterminada');
        });
    }
};
