<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyecto_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('rol', 20);
            $table->timestamps();

            // Un usuario tiene un solo rol por proyecto, garantizado por la base
            // de datos y no solo por la validación.
            $table->unique(['proyecto_id', 'usuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyecto_usuario');
    }
};
