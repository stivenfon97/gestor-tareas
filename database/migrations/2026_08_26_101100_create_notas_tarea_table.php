<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_tarea', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained('tareas')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->text('contenido');
            // Todo nace interno: exponer al cliente es una decisión explícita.
            $table->string('visibilidad', 20)->default('interna');
            $table->timestamps();

            $table->index(['tarea_id', 'visibilidad']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_tarea');
    }
};
