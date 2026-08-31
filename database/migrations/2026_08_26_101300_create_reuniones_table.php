<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reuniones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->dateTime('fecha');
            $table->string('tipo', 20);
            $table->string('titulo');
            // TipTap: el JSON es la fuente editable, el HTML la copia renderizada.
            $table->json('contenido_json');
            $table->longText('contenido_html');
            $table->text('acuerdos')->nullable();
            $table->string('visibilidad', 20)->default('interna');
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['proyecto_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reuniones');
    }
};
