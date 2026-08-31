<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tareas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            // Borrar un módulo no borra su trabajo: las tareas quedan sueltas.
            $table->foreignId('modulo_id')->nullable()->constrained('modulos')->nullOnDelete();
            $table->foreignId('periodo_id')->nullable()->constrained('periodos')->nullOnDelete();
            // Periodo del que se arrastró la tarea al no terminarse a tiempo.
            $table->foreignId('periodo_origen_id')->nullable()->constrained('periodos')->nullOnDelete();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->string('tipo', 20);
            $table->string('prioridad', 20);
            // motivo_espera y motivo_cancelacion son campos distintos: son cosas
            // diferentes y se consultan en momentos diferentes.
            $table->text('motivo_espera')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->integer('orden')->default(0);
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['proyecto_id', 'estado']);
            $table->index(['periodo_id', 'estado']);
            $table->index(['proyecto_id', 'modulo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
