<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Habitacion;

class HabitacionCambiarEstado extends Component
{
    public Habitacion $habitacion;
    public string $nuevoEstado = '';

    public array $estadosDisponibles = [
        'En uso','Disponible','Pagado','En limpieza','Mantenimiento',
    ];

    public function mount(Habitacion $habitacion): void
    {
        $this->habitacion  = $habitacion;
        $this->nuevoEstado = $habitacion->estado_texto ?? 'Disponible';
    }

    public function guardar()
    {
        $this->validate([
            'nuevoEstado' => 'required|in:' . implode(',', $this->estadosDisponibles),
        ]);

        $map = [
            'Disponible'    => ['bool' => 1, 'css' => 'bg-success text-white'],
            'En uso'        => ['bool' => 0, 'css' => 'bg-danger text-white'],
            'Pagado'        => ['bool' => 0, 'css' => 'bg-primary text-white'],
            'En limpieza'   => ['bool' => 0, 'css' => 'bg-warning text-dark'],
            'Mantenimiento' => ['bool' => 0, 'css' => 'bg-secondary text-white'],
        ];

        $meta = $map[$this->nuevoEstado];

        $this->habitacion->update([
            'estado_texto' => $this->nuevoEstado,
            'estado'       => $meta['bool'],
            'color'        => $meta['css'],
        ]);

        session()->flash('estadoActualizado', 'Estado actualizado correctamente.');
        return redirect()->route('crear-alquiler');
    }

    public function render()
    {
        return view('livewire.habitacion-cambiar-estado')
            ->extends('adminlte::page')
            ->section('content');
    }
}
