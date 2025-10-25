<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();

            // Relación con el usuario autenticado
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Relación con el libro comprado
            $table->foreignId('libro_id')->constrained('libros')->onDelete('cascade');

            // Método y estado del pago
            $table->enum('metodo_pago', ['transferencia', 'tarjeta', 'paypal'])->default('tarjeta');
            $table->enum('estado_pago', ['pendiente', 'pagado', 'rechazado'])->default('pendiente');

            // Precio total
            $table->unsignedInteger('total_cents');

            // Información adicional opcional
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('compras');
    }
};
