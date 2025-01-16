<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Habitacion;
use Illuminate\Support\Facades\DB;

class Dashboardgeneral extends Component
{
    public $habitacionesAlquiladas = [];
    public $habitacionesLibres = [];
    public $totalGenerado = 0;

    public function mount()
    {
        $this->habitacionesAlquiladas = $this->getHabitacionesAlquiladas();
        $this->habitacionesLibres = $this->getHabitacionesLibres();
        $this->totalGenerado = $this->getTotalGenerado();
    }

    /**
     * Obtiene las habitaciones alquiladas.
     */
    private function getHabitacionesAlquiladas()
    {
        return Habitacion::join('alquiler', 'habitaciones.id', '=', 'alquiler.habitacion_id')
            ->where('alquiler.estado', 'alquilado')
            ->select('habitaciones.*')
            ->get();
    }

    /**
     * Obtiene las habitaciones libres.
     */
    private function getHabitacionesLibres()
    {
        return Habitacion::leftJoin('alquiler', function ($join) {
            $join->on('habitaciones.id', '=', 'alquiler.habitacion_id')
                ->where('alquiler.estado', 'alquilado');
        })
        ->whereNull('alquiler.habitacion_id')
        ->select('habitaciones.*')
        ->get();
    }

    /**
     * Calcula el total generado por los alquileres pagados.
     */
    private function getTotalGenerado()
    {
        return DB::table('alquiler')
            ->where('estado', 'pagado')
            ->sum(DB::raw('COALESCE(total, 0)'));
    }

    public function render()
    {
        return view('livewire.dashboardgeneral', [
            'habitacionesAlquiladas' => $this->habitacionesAlquiladas,
            'habitacionesLibres' => $this->habitacionesLibres,
            'totalGenerado' => $this->totalGenerado,
        ]);
    }
}
