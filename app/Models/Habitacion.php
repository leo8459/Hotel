<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Habitacion extends Model
{
    use HasFactory;

    /** Nombre explícito de la tabla (opcional si coincide con la convención) */
    protected $table = 'habitaciones';

    /** Campos que se pueden asignar masivamente */
    protected $fillable = [
        'habitacion',
        'tipo',
        'preciohora',
        'precio_extra',
        'tarifa_opcion1',
        'tarifa_opcion2',
        'tarifa_opcion3',
        'tarifa_opcion4',
        'estado',          // 1 = libre, 0 = ocupada
    ];

    /* ================================================================
     |  Relaciones
     |================================================================ */

    /**
     * Una habitación puede tener muchos alquileres.
     */
    public function alquileres()
    {
        return $this->hasMany(Alquiler::class, 'habitacion_id');
    }

    /* ================================================================
     |  Scopes (consultas reutilizables)
     |================================================================ */

    /**
     * Habitaciones libres (estado = 1)
     */
    public function scopeLibres($query)
    {
        return $query->where('estado', 1);
    }

    /**
     * Habitaciones ocupadas (estado = 0)
     */
    public function scopeOcupadas($query)
    {
        return $query->where('estado', 0);
    }

    /* ================================================================
     |  Accesores / Mutadores
     |================================================================ */

    /**
     * Devuelve la clase de color Bootstrap según el estado.
     * bg-success  = verde (libre)
     * bg-danger   = rojo  (ocupada)
     */
    public function getColorAttribute(): string
    {
        return $this->estado ? 'bg-success' : 'bg-danger';
    }

    /**
     * Devuelve un texto amigable del estado.
     */
    public function getEstadoTextoAttribute(): string
    {
        return $this->estado ? 'Libre' : 'Ocupada';
    }
}
