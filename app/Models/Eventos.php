<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eventos extends Model
{
    use HasFactory;
    protected $table = 'eventos'; // Nombre de la tabla en plural

    protected $fillable = [
        'articulo',
        'precio',
        'stock',
        'vendido',
        'precio_vendido',       
        'habitacion_id', 
        'inventario_id', // Agregado para la relación       
        'estado',
        'usuario_id', // Asegúrate de incluir esto


        
    ];

    // Relación con Habitacion
    public function habitacion()
    {
        return $this->belongsTo(Habitacion::class, 'habitacion_id');
    }

    // Relación con Inventario
    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }
    public function usuario()
    {
        return $this->belongsTo(\App\Models\User::class, 'usuario_id');
    }
}
