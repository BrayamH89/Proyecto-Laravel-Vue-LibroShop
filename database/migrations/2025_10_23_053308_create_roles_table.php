<?php

// database/migrations/xxxx_xx_xx_create_roles_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('roles');
    }
};
