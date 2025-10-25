<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'libro_id',
        'precio_unitario_cents',
        'total_cents',
        'moneda',
        'estado_pago',
        'metodo_pago',
        'transaction_id',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario_cents' => 'integer',
        'total_cents' => 'integer',
    ];

    // Relación con el usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación con el libro
    public function libro()
    {
        return $this->belongsTo(Libro::class);
    }

    // Accessor para obtener el precio total formateado
    public function getTotalFormateadoAttribute()
    {
        return number_format($this->total_cents / 100, 2);
    }

    // Accessor para obtener el precio unitario formateado
    public function getPrecioUnitarioFormateadoAttribute()
    {
        return number_format($this->precio_unitario_cents / 100, 2);
    }

    // ✅ AGREGAR ESTOS SCOPES
    public function scopeCompletadas($query)
    {
        return $query->where('estado', 'completada');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeCanceladas($query)
    {
        return $query->where('estado', 'cancelada');
    }
}