<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Habitacion;
use Illuminate\Support\Facades\DB;

class Dashboardgeneral extends Component
{
    // Listas por estado
    public $enUso = [];
    public $disponibles = [];
    public $enLimpieza = [];
    public $mantenimiento = [];
    public $pagado = [];

    // Métrica
    public $totalGenerado = 0;

    public function mount()
    {
        // Cargar estados por texto (según tu modelo Habitacion.estado_texto)
        $this->enUso       = $this->getByEstadoTexto('En uso');
        $this->disponibles = $this->getByEstadoTexto('Disponible');
        $this->enLimpieza  = $this->getByEstadoTexto('En limpieza');
        $this->mantenimiento = $this->getByEstadoTexto('Mantenimiento');
        $this->pagado      = $this->getByEstadoTexto('Pagado');

        // Total generado por alquileres pagados
        $this->totalGenerado = $this->getTotalGenerado();
    }

    /**
     * Retorna habitaciones por estado_texto.
     */
    private function getByEstadoTexto(string $estadoTexto)
    {
        return Habitacion::where('estado_texto', $estadoTexto)
            ->orderBy('habitacion')
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
            'enUso'        => $this->enUso,
            'disponibles'  => $this->disponibles,
            'enLimpieza'   => $this->enLimpieza,
            'mantenimiento'=> $this->mantenimiento,
            'pagado'       => $this->pagado,
            'totalGenerado'=> $this->totalGenerado,
        ]);
    }
}
