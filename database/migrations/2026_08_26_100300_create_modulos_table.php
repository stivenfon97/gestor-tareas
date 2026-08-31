<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            // Autoreferencial: un módulo puede contener submódulos.
            $table->foreignId('parent_id')->nullable()->constrained('modulos')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->index(['proyecto_id', 'parent_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modulos');
    }
};
