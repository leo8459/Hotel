<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EventoController extends Controller
{
    public function obtenereventos ()
    {
        return view('alquileres.evento');
    }
}
