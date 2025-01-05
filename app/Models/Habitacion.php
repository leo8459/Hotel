<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Habitacion extends Model
{
    use HasFactory;
    protected $table = 'habitaciones'; // Nombre explícito de la tabla

    protected $fillable = [
        'habitacion',
        'tipo',
        'preciohora',      // Nuevo campo
        'precio_extra',    // Nuevo campo
        'tarifa_opcion1',  // Nuevo campo
        'tarifa_opcion2',  // Nuevo campo
        'tarifa_opcion3',  // Nuevo campo
        'tarifa_opcion4',  // Nuevo campo
        'estado',
    ];

    /**
     * Relación con el modelo Alquiler.
     * Una habitación puede tener muchos alquileres.
     */
    public function alquileres()
    {
        return $this->hasMany(Alquiler::class, 'habitacion_id');
    }
}
