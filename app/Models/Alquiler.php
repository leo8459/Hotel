<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alquiler extends Model
{
    use HasFactory;

    protected $table = 'alquiler'; // Nombre de la tabla en plural

    protected $fillable = [
        'tipoingreso',
        'tipopago',
        'aireacondicionado',
        'entrada',
        'salida',
        'horas',
        'total',
        'habitacion_id', 
        'inventario_id', // Agregado para la relación
        'inventario_detalle', // Agregado para permitir el guardado
        'tarifa_seleccionada', // Agregado para permitir el guardado
        'estado',
        'aire_inicio',
        'aire_fin',
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
