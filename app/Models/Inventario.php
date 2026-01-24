<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    use HasFactory;
    protected $table = 'inventarios'; // Nombre de la tabla

    protected $fillable = [
        'articulo',
        'precio_entrada',
        'precio',
        'stock',
        'estado',
        'total_compra', // ✅
    ];
}
