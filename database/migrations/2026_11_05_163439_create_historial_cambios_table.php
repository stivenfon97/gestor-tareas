<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de cambios de todos los registros auditados, en una sola tabla
 * polimórfica: una fila por guardado, con los campos que cambiaron y su valor
 * antes y después.
 *
 * `registro_type` guarda un alias en español (`tarea`, `usuario`), no el
 * nombre de la clase, y **no** sale del `morphMap` global: medialibrary y
 * spatie/permission ya tienen filas con `App\Models\…` y activar el mapa las
 * dejaría huérfanas. El mapa vive en `HistorialCambio::TIPOS`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_cambios', function (Blueprint $table) {
            $table->id();
            $table->string('registro_type', 40);
            // Sin clave foránea: el historial sobrevive al borrado del registro.
            $table->unsignedBigInteger('registro_id');
            // Si el usuario desaparece, el hecho registrado sigue siendo cierto.
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('evento', 30);
            $table->json('campos');
            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();
            $table->string('ip', 45)->nullable();
            // Solo created_at: un registro histórico no se corrige, se añade otro.
            $table->timestamp('created_at')->nullable();

            $table->index(['registro_type', 'registro_id', 'created_at']);
            $table->index(['usuario_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_cambios');
    }
};
