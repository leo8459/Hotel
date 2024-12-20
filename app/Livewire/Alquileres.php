<?php
namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Alquiler;

class Alquileres extends Component
{
    use WithPagination;

    public $searchTerm = '';
    public $perPage = 10;
    public $tipoingreso, $tipopago, $aireacondicionado = false, $total = 0;
    public $selectedAlquilerId = null; // Para manejar edición

    public $showCreateModal = false; // Controla el modal de creación

    public function render()
    {
        $alquileres = Alquiler::where('tipoingreso', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.alquileres', [
            'alquileres' => $alquileres,
        ]);
    }

    public function openCreateModal()
    {
        $this->reset(['tipoingreso', 'tipopago', 'aireacondicionado', 'total']);
        $this->dispatch('show-create-modal');
    }

    public function closeCreateModal()
    {
        $this->dispatch('close-modal');
    }

    public function updatedAireacondicionado($value)
    {
        // Si aire acondicionado está activado, agregar 35 al total
        if ($value) {
            $this->total += 35;
        } else {
            // Si se desactiva, restar 35 del total
            $this->total -= 35;
        }
    }

    public function store()
    {
        $this->validate([
            'tipoingreso' => 'required|string|max:255',
            'tipopago' => 'required|string|max:255',
            'aireacondicionado' => 'required|boolean',
            'total' => 'required|numeric|min:0',
        ]);

        Alquiler::create([
            'tipoingreso' => $this->tipoingreso,
            'tipopago' => $this->tipopago,
            'aireacondicionado' => $this->aireacondicionado,
            'total' => $this->total,
        ]);

        session()->flash('message', 'Alquiler creado exitosamente.');

        // Cerrar el modal
        $this->closeCreateModal();

        // Reiniciar la paginación y recargar la lista
        $this->resetPage();
    }
}
