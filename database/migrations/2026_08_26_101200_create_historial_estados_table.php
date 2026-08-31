<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_estados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained('tareas')->cascadeOnDelete();
            // Si el usuario desaparece, el hecho registrado sigue siendo cierto.
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado_anterior', 20)->nullable();
            $table->string('estado_nuevo', 20);
            $table->text('comentario')->nullable();
            // Solo created_at: un registro histórico no se actualiza.
            $table->timestamp('created_at')->nullable();

            $table->index(['tarea_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_estados');
    }
};
