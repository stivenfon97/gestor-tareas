<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vistas_guardadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            // Nullable: una vista puede ser global a todos los proyectos.
            $table->foreignId('proyecto_id')->nullable()->constrained('proyectos')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('tipo', 20);
            $table->json('configuracion');
            $table->boolean('es_predeterminada')->default(false);
            $table->timestamps();

            $table->index(['usuario_id', 'proyecto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vistas_guardadas');
    }
};
