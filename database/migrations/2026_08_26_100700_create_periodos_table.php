<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->integer('consecutivo');
            $table->date('fecha_inicio');
            // Nullable: se llena al cerrar. Un periodo abierto más allá de su
            // fecha prevista es un estado válido y frecuente.
            $table->date('fecha_fin')->nullable();
            $table->string('estado', 20)->default('abierto');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['proyecto_id', 'consecutivo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos');
    }
};
