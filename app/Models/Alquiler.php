<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alquiler extends Model
{
    use HasFactory;

    protected $table = 'alquiler'; // Nombre de la tabla
    protected $fillable = ['tipoingreso', 'tipopago', 'aireacondicionado', 'total'];
}
