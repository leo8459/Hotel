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
    public $habitacion, $tipo, $preciohora, $precio_extra, $tarifa_opcion1, $tarifa_opcion2, $tarifa_opcion3, $tarifa_opcion4;
    public $selectedHabitacionId = null; // Para manejar edición
    public $showCreateModal = false; // Controla el modal de creación

    protected $rules = [
        'habitacion' => 'required|string|max:255',
        'tipo' => 'required|string|max:255',
        'preciohora' => 'nullable|integer|min:0',
        'precio_extra' => 'nullable|integer|min:0',
        'tarifa_opcion1' => 'nullable|integer|min:0',
        'tarifa_opcion2' => 'nullable|integer|min:0',
        'tarifa_opcion3' => 'nullable|integer|min:0',
        'tarifa_opcion4' => 'nullable|integer|min:0',
    ];

    public function render()
    {
        $habitaciones = Habitacion::where('habitacion', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.habitaciones', [
            'habitaciones' => $habitaciones,
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
            'preciohora' => $this->preciohora,
            'precio_extra' => $this->precio_extra,
            'tarifa_opcion1' => $this->tarifa_opcion1,
            'tarifa_opcion2' => $this->tarifa_opcion2,
            'tarifa_opcion3' => $this->tarifa_opcion3,
            'tarifa_opcion4' => $this->tarifa_opcion4,
        ]);

        session()->flash('message', 'Habitación creada con éxito.');

        $this->dispatch('close-modal');
        $this->resetInputFields();
    }

    private function resetInputFields()
    {
        $this->habitacion = '';
        $this->tipo = '';
        $this->preciohora = null;
        $this->precio_extra = null;
        $this->tarifa_opcion1 = null;
        $this->tarifa_opcion2 = null;
        $this->tarifa_opcion3 = null;
        $this->tarifa_opcion4 = null;
        $this->selectedHabitacionId = null;
    }

    public function update()
    {
        $this->validate();

        $habitacion = Habitacion::find($this->selectedHabitacionId);

        if ($habitacion) {
            $habitacion->update([
                'habitacion' => $this->habitacion,
                'tipo' => $this->tipo,
                'preciohora' => $this->preciohora,
                'precio_extra' => $this->precio_extra,
                'tarifa_opcion1' => $this->tarifa_opcion1,
                'tarifa_opcion2' => $this->tarifa_opcion2,
                'tarifa_opcion3' => $this->tarifa_opcion3,
                'tarifa_opcion4' => $this->tarifa_opcion4,
            ]);

            session()->flash('message', 'Habitación actualizada con éxito.');

            // Cierra el modal y limpia los campos
            $this->dispatch('close-modal');
            $this->resetInputFields();
        } else {
            session()->flash('error', 'La habitación no existe.');
        }
    }

    public function openEditModal($id)
    {
        $habitacion = Habitacion::find($id);

        if ($habitacion) {
            $this->selectedHabitacionId = $habitacion->id;
            $this->habitacion = $habitacion->habitacion;
            $this->tipo = $habitacion->tipo;
            $this->preciohora = $habitacion->preciohora;
            $this->precio_extra = $habitacion->precio_extra;
            $this->tarifa_opcion1 = $habitacion->tarifa_opcion1;
            $this->tarifa_opcion2 = $habitacion->tarifa_opcion2;
            $this->tarifa_opcion3 = $habitacion->tarifa_opcion3;
            $this->tarifa_opcion4 = $habitacion->tarifa_opcion4;

            // Lanza el evento para mostrar el modal
            $this->dispatch('show-edit-modal');
        } else {
            session()->flash('error', 'La habitación no existe.');
        }
    }

    public function delete($id)
    {
        $habitacion = Habitacion::find($id);

        if ($habitacion) {
            $habitacion->delete();
            session()->flash('message', 'Habitación eliminada correctamente.');
        } else {
            session()->flash('error', 'La habitación no existe.');
        }
    }
}
