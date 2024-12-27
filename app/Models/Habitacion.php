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
