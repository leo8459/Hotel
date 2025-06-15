<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AlquilerController extends Controller
{
    public function obteneralquileres ()
    {
        return view('alquileres.alquilere');
    }
    public function alquiler ()
    {
        return view('alquileres.crearalquiler');
    }
}
