<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('libros', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('autor')->nullable();
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('precio_cents'); // precio en centavos
            $table->string('moneda', 3)->default('COP');
            $table->string('imagen_path')->nullable(); // portada
            $table->string('contenido_path')->nullable(); // archivo digital (PDF/EPUB)
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('libros');
    }
};
