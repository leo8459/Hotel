<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alquiler extends Model
{
    use HasFactory;

    protected $table = 'alquiler'; // Nombre de la tabla

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'tipoingreso', 
        'tipopago', 
        'aireacondicionado', 
        'total', 
        'entrada', 
        'salida', 
        'horas', 
        'habitacion_id' // Agrega este campo
    ];

    /**
     * Relación con el modelo Habitacion.
     * Un alquiler pertenece a una habitación.
     */
    public function habitacion()
    {
        return $this->belongsTo(Habitacion::class, 'habitacion_id');
    }
}
