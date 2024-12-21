<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Alquiler;
use App\Models\Habitacion;

class Alquileres extends Component
{
    use WithPagination;

    public $searchTerm = '';
    public $perPage = 10;
    public $tipoingreso, $tipopago, $aireacondicionado = false, $total = 0, $entrada, $salida, $horas, $habitacion_id;
    public $selectedAlquilerId = null; // Para manejar edición

    public $showCreateModal = false;

    public function render()
    {
        $alquileres = Alquiler::with('habitacion') // Carga la relación
            ->where('tipoingreso', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    
        $habitaciones = Habitacion::all();
    
        return view('livewire.alquileres', [
            'alquileres' => $alquileres,
            'habitaciones' => $habitaciones,
        ]);
    }
    

    public function openCreateModal()
    {
        $this->reset([
            'tipoingreso', 'tipopago', 'aireacondicionado', 'total', 
            'entrada', 'salida', 'horas', 'habitacion_id'
        ]);
        $this->dispatch('show-create-modal');
    }

    public function closeCreateModal()
    {
        $this->dispatch('close-modal');
    }

    public function store()
    {
        $this->validate([
            'tipoingreso' => 'required|string|max:255',
            'tipopago' => 'required|string|max:255',
            'aireacondicionado' => 'required|boolean',
            'entrada' => 'required|date',
            'salida' => 'required|date|after_or_equal:entrada',
            'habitacion_id' => 'required|exists:habitaciones,id',
            'total' => 'required|numeric|min:0',
        ]);

        $this->horas = round((strtotime($this->salida) - strtotime($this->entrada)) / 3600);

        Alquiler::create([
            'tipoingreso' => $this->tipoingreso,
            'tipopago' => $this->tipopago,
            'aireacondicionado' => $this->aireacondicionado,
            'entrada' => $this->entrada,
            'salida' => $this->salida,
            'horas' => $this->horas,
            'habitacion_id' => $this->habitacion_id,
            'total' => $this->total,
        ]);

        session()->flash('message', 'Alquiler creado exitosamente.');

        $this->closeCreateModal();
        $this->resetPage();
    }
}
