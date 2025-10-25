<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('categoria_libro', function (Blueprint $table) {
            $table->id();

            // 🔗 Relación con libros
            $table->foreignId('libro_id')
                  ->constrained('libros')
                  ->onDelete('cascade');

            // 🔗 Relación con categorías
            $table->foreignId('categoria_id')
                  ->constrained('categorias')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('categoria_libro');
    }
};
