<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Inventario;

class Inventarios extends Component
{
    use WithPagination;

    public $searchTerm = '';
    public $articulo, $precio, $stock, $estado;
    public $selectedArticuloId = null;

    public function render()
    {
        $articulos = Inventario::where('articulo', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.inventarios', compact('articulos'));
    }

    public function openEditModal($id)
    {
        $articulo = Inventario::find($id);

        if ($articulo) {
            $this->selectedArticuloId = $articulo->id;
            $this->articulo = $articulo->articulo;
            $this->precio = $articulo->precio;
            $this->stock = $articulo->stock;
            $this->estado = $articulo->estado;

            $this->dispatch('show-edit-modal');
        } else {
            session()->flash('error', 'El artículo no existe.');
        }
    }

    public function delete($id)
    {
        $articulo = Inventario::find($id);

        if ($articulo) {
            $articulo->delete();
            session()->flash('message', 'Artículo eliminado correctamente.');
        } else {
            session()->flash('error', 'El artículo no existe.');
        }
    }

    public function store()
    {
        $this->validate([
            'articulo' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'estado' => 'required|boolean',
        ]);

        Inventario::create([
            'articulo' => $this->articulo,
            'precio' => $this->precio,
            'stock' => $this->stock,
            'estado' => $this->estado,
        ]);

        session()->flash('message', 'Artículo creado con éxito.');

        $this->resetInputFields();
        $this->dispatch('close-modal');
    }

    public function update()
    {
        $this->validate([
            'articulo' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'estado' => 'required|boolean',
        ]);

        $articulo = Inventario::find($this->selectedArticuloId);

        if ($articulo) {
            $articulo->update([
                'articulo' => $this->articulo,
                'precio' => $this->precio,
                'stock' => $this->stock,
                'estado' => $this->estado,
            ]);

            session()->flash('message', 'Artículo actualizado con éxito.');

            $this->resetInputFields();
            $this->dispatch('close-modal');
        } else {
            session()->flash('error', 'El artículo no existe.');
        }
    }

    private function resetInputFields()
    {
        $this->articulo = '';
        $this->precio = null;
        $this->stock = null;
        $this->estado = null;
        $this->selectedArticuloId = null;
    }
    public function openCreateModal()
    {
        $this->resetInputFields(); // Limpia los campos del formulario (si es necesario)
        $this->dispatch('show-create-modal'); // Lanza el evento para mostrar el modal
    }
}


