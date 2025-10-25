<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Categoria;

return new class extends Migration
{
    public function up()
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->string('slug')->after('nombre')->nullable();
        });

        // Generar slugs para categorías existentes
        Categoria::all()->each(function ($categoria) {
            $categoria->slug = Str::slug($categoria->nombre);
            $categoria->save();
        });

        // Hacer el slug obligatorio después de generar los existentes
        Schema::table('categorias', function (Blueprint $table) {
            $table->string('slug')->unique()->change();
        });
    }

    public function down()
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};