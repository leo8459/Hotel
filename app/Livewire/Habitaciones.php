<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Habitacion;
use Livewire\WithPagination;

class Habitaciones extends Component
{
    use WithPagination;

    public $searchTerm = '';
    public $perPage = 10;
    public $habitacion, $tipo, $entrada, $salida, $horas;
    public $selectedHabitacionId = null; // Para manejar edición
    public $selectedAlquilerId;
    
    public $showCreateModal = false; // Controla el modal de creación

    protected $rules = [
        'habitacion' => 'required|string|max:255',
        'tipo' => 'required|string|max:255',
        'entrada' => 'nullable|date',
        'salida' => 'nullable|date|after_or_equal:entrada',
        'horas' => 'nullable|integer|min:0',
    ];

    public function render()
    {
        $alquileres = Habitacion::where('habitacion', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.habitaciones', [
            'alquileres' => $alquileres,
        ]);
    }

    public function openCreateModal()
    {
        $this->resetInputFields();
        $this->showCreateModal = true;
        $this->dispatch('show-create-modal');
    }

    public function store()
    {
        $this->validate();

        Habitacion::create([
            'habitacion' => $this->habitacion,
            'tipo' => $this->tipo,
            
        ]);

        session()->flash('message', 'Habitación creada con éxito.');

        $this->dispatch('close-modal');
        $this->resetInputFields();
    }

    private function resetInputFields()
    {
        $this->habitacion = '';
        $this->tipo = '';
        $this->entrada = null;
        $this->salida = null;
        $this->horas = null;
        $this->selectedHabitacionId = null;
    }
    public function update()
    {
        $this->validate([
            'habitacion' => 'required|string|max:255',
            'tipo' => 'required|string|max:255',
        ]);
    
        $alquiler = Habitacion::find($this->selectedAlquilerId);
    
        if ($alquiler) {
            $alquiler->update([
                'habitacion' => $this->habitacion,
                'tipo' => $this->tipo,
            ]);
    
            session()->flash('message', 'Habitación actualizada con éxito.');
    
            // Cierra el modal y limpia los campos
            $this->dispatch('close-modal');
            $this->resetInputFields();
        } else {
            session()->flash('error', 'El alquiler no existe.');
        }
    }
    
public function openEditModal($id)
{
    $alquiler = Habitacion::find($id);

    if ($alquiler) {
        $this->selectedAlquilerId = $alquiler->id;
        $this->habitacion = $alquiler->habitacion;
        $this->tipo = $alquiler->tipo;

        // Lanza el evento para mostrar el modal
        $this->dispatch('show-edit-modal');
    } else {
        session()->flash('error', 'El alquiler no existe.');
    }
}


public function delete($id)
{
    $alquiler = Habitacion::find($id);

    if ($alquiler) {
        $alquiler->delete();
        session()->flash('message', 'Alquiler eliminado correctamente.');
    } else {
        session()->flash('error', 'El alquiler no existe.');
    }
}

}
