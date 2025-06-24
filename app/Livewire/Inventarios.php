<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Inventario;
use App\Models\Eventos;
use Illuminate\Support\Facades\Auth;

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
            'precio'   => 'required|numeric|min:0',
            'stock'    => 'required|integer|min:0',
            'estado'   => 'required|boolean',
        ]);

        // Crear el artículo de inventario
        $inventario = Inventario::create([
            'articulo' => $this->articulo,
            'precio'   => $this->precio,
            'stock'    => $this->stock,
            'estado'   => $this->estado,
        ]);

        // Calcular total de los productos
        $precioTotal = $this->precio * $this->stock;

        // Crear el evento asociado
        Eventos::create([
            'articulo'        => $this->articulo,
            'precio'          => $precioTotal,
            'stock'           => $this->stock,
            'vendido'         => 0,                    // inicial
            'precio_vendido'  => 0,                    // inicial
            'habitacion_id'   => null,                 // opcional
            'inventario_id'   => $inventario->id,
            // 'estado'          => 1,
            'usuario_id'      => Auth::id(),           // el usuario logueado
        ]);

        session()->flash('message', 'Artículo y evento creados con éxito.');

        $this->resetInputFields();
        $this->dispatch('close-modal');
    }


    public function update()
    {
        $this->validate([
            'articulo' => 'required|string|max:255',
            'precio'   => 'required|numeric|min:0',
            'stock'    => 'required|integer|min:1',  // debe ser al menos 1 para sumar
            'estado'   => 'required|boolean',
        ]);

        $articulo = Inventario::find($this->selectedArticuloId);

        if ($articulo) {
            // Sumar el nuevo stock al existente
            $articulo->update([
                'articulo' => $this->articulo,
                'precio'   => $this->precio, // Si el precio cambia, lo actualiza
                'stock'    => $articulo->stock + $this->stock, // suma el nuevo stock
                'estado'   => $this->estado,
            ]);

            // Crear evento con SOLO lo añadido
            $precioTotal = $this->precio * $this->stock;

            Eventos::create([
                'articulo'        => $this->articulo,
                'precio'          => $precioTotal,
                'stock'           => $this->stock,           // solo lo añadido
                'vendido'         => 0,
                'precio_vendido'  => 0,
                'habitacion_id'   => null,
                'inventario_id'   => $articulo->id,
                'usuario_id'      => Auth::id(),
            ]);

            session()->flash('message', 'Stock añadido correctamente y evento registrado.');

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
