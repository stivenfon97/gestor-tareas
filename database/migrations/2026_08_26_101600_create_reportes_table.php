<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro permanente de emisión de un reporte.
     *
     * No hay columna de ruta de archivo: los .xlsx no se persisten en disco, se
     * generan bajo demanda. `snapshot` guarda las filas exactas exportadas y es
     * lo único que permite reconstruir meses después el archivo idéntico al que
     * se envió.
     */
    public function up(): void
    {
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->cascadeOnDelete();
            $table->string('tipo', 20);
            $table->string('nombre_archivo');
            $table->json('filtros');
            $table->json('resumen_estados');
            $table->json('snapshot');
            $table->integer('total_tareas');
            $table->string('hash', 40);
            $table->foreignId('generado_por')->constrained('users')->restrictOnDelete();
            $table->dateTime('generado_en');

            $table->index(['periodo_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};
