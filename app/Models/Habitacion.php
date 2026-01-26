<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Habitacion extends Model
{
    use HasFactory;

    protected $table = 'habitaciones';

    protected $fillable = [
        'habitacion',
        'tipo',
        'preciohora',
        'precio_extra',
        'tarifa_opcion1',
        'tarifa_opcion2',
        'tarifa_opcion3',
        'tarifa_opcion4',
        'estado',
        'estado_texto',
        'color',
                'freezer_stock', // ✅ IMPORTANTE

    ];
    protected $casts = [
        'freezer_stock' => 'array',
    ];

    /* ───── Relaciones ───── */
    public function alquileres()
    {
        return $this->hasMany(Alquiler::class);
    }

    /* ───── Accesores corregidos ───── */
    public function getColorAttribute(): string
    {
        // Devuelve lo que está en la BD o, si viniera null, un color neutro
        return $this->attributes['color'] ?? 'bg-secondary text-white';
    }

    public function getEstadoTextoAttribute(): string
    {
        // Devuelve el literal guardado en la BD
        return $this->attributes['estado_texto'] ?? 'Sin estado';
    }
}
