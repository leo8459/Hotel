<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Habitacion;

class CrearAlquiler extends Component
{
    public $habitaciones = [];

    public function mount(): void
    {
        $this->habitaciones = Habitacion::orderBy('habitacion')->get();
    }

    public function alquilar(int $id): void
    {
        $this->dispatch('abrir-modal-alquiler', id: $id);
    }

    public function render()
    {
        return view('livewire.crearalquiler')
            ->extends('adminlte::page')
            ->section('content');
    }
}
