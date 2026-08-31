<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            // El unique es lo que hace real la regla "un periodo, un pago".
            // Sin él es solo una intención.
            $table->foreignId('periodo_id')->unique()->constrained('periodos')->cascadeOnDelete();
            $table->date('fecha_pago');
            $table->decimal('monto_usd', 12, 2);
            $table->decimal('tasa_cambio', 12, 4);
            $table->decimal('monto_cop', 14, 2);
            $table->string('origen_tasa', 20);
            $table->string('referencia')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
