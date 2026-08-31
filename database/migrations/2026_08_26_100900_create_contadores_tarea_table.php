<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contador correlativo de tareas por proyecto.
     *
     * Existe para resolver la condición de carrera del código de tarea: el
     * observer bloquea esta fila con `lockForUpdate()` en lugar de calcular
     * `max(codigo) + 1`, que con dos creaciones simultáneas devuelve el mismo
     * número a ambas. Además, al no derivar el número de las tareas existentes,
     * un borrado (incluso forzado) no puede hacer que un código se reutilice.
     */
    public function up(): void
    {
        Schema::create('contadores_tarea', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->unique()->constrained('proyectos')->cascadeOnDelete();
            $table->unsignedInteger('ultimo_numero')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contadores_tarea');
    }
};
