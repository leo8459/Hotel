<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Dashboardgeneral extends Controller
{
    public function obtenerdashboard ()
    {
        return view('alquileres.dashboardgeneral');
    }
}
