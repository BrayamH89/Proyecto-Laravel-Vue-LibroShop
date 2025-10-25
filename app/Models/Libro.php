<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Libro extends Model
{
    use HasFactory;

    protected $table = 'libros';

    protected $fillable = [
        'titulo',
        'autor',
        'descripcion',
        'precio_cents',
        'moneda',
        'imagen_path',
        'contenido_path',
    ];

    protected $casts = [
        'precio_cents' => 'integer',
    ];

    // ✅ Añadir los accessors al array appends para que se incluyan automáticamente
    protected $appends = ['imagen_url', 'contenido_url', 'precio'];

    // 🔗 Relación muchos a muchos con categorías
    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'categoria_libro', 'libro_id', 'categoria_id');
    }

    // 💰 Accessor para mostrar el precio en formato decimal
    public function getPrecioAttribute()
    {
        return number_format($this->precio_cents / 100, 2, ',', '.');
    }

    // 📂 Accessor para devolver la URL de la portada
    public function getImagenUrlAttribute()
    {
        return $this->imagen_path ? asset('storage/' . $this->imagen_path) : null;
    }

    // 📄 Accessor para devolver la URL del archivo digital
    public function getContenidoUrlAttribute()
    {
        return $this->contenido_path ? asset('storage/' . $this->contenido_path) : null;
    }
}