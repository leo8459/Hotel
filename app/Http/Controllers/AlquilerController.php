<?php

namespace App\Http\Controllers;
use App\Models\Alquiler;

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
public function alquiler2(Alquiler $alquiler)
{
    // Ya tienes el modelo cargado automáticamente
    return view('alquiler.editar', compact('alquiler'));
}


}
