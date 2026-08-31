<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            // restrict: borrar un cliente con proyectos vivos es un accidente,
            // no una operación de negocio.
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->string('nombre');
            $table->string('slug')->unique();
            // El prefijo alimenta el código correlativo de tarea (MAQ-0001).
            $table->string('prefijo_tarea', 10)->unique();
            $table->text('descripcion')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyectos');
    }
};
