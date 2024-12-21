<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Habitacion extends Model
{
    use HasFactory;

    protected $table = 'habitaciones'; // Nombre de la tabla

    protected $fillable = ['habitacion', 'tipo', 'entrada', 'salida', 'horas']; // Campos que se pueden asignar masivamente

    /**
     * Relación con el modelo Alquiler.
     * Una habitación puede tener varios alquileres.
     */
    public function alquileres()
    {
        return $this->hasMany(Alquiler::class, 'habitacion_id');
    }}
