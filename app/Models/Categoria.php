<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
    ];

    // Relación con libros (muchos a muchos)
    public function libros()
    {
        return $this->belongsToMany(Libro::class, 'categoria_libro');
    }
}