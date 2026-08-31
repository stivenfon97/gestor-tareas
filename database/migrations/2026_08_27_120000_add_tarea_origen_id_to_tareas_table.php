<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vincula una corrección con la entrega que la originó.
 *
 * Es el precio de haber hecho `entregada` terminal (D2.6): un fallo posterior
 * crea una tarea nueva de tipo `correccion`, y hasta ahora ese vínculo solo
 * vivía en el texto de la descripción.
 *
 * `set null` y no `cascade`: si algún día se borra la entrega original, la
 * corrección **sigue siendo trabajo hecho** que cuenta en su periodo. Perderla
 * falsearía el reporte de ese periodo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            $table->foreignId('tarea_origen_id')
                ->nullable()
                ->after('periodo_origen_id')
                ->constrained('tareas')
                ->nullOnDelete();

            $table->index('tarea_origen_id');
        });
    }

    public function down(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tarea_origen_id');
        });
    }
};
