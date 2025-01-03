<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InventarioController extends Controller
{
    public function obtenerinventario ()
    {
        return view('alquileres.inventario');
    }
}
