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
    public $articulo, $precio, $stock, $estado, $precio_entrada;
    public $selectedArticuloId = null;

    // ✅ Totales automáticos
    public $total_compra = 0;
    public $total_venta = 0;

    public function render()
    {
        $articulos = Inventario::where('articulo', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.inventarios', compact('articulos'));
    }

    // ✅ recalcular cuando cambie compra/venta/stock
    public function updatedPrecioEntrada()
    {
        $this->calcularTotales();
    }

    public function updatedPrecio()
    {
        $this->calcularTotales();
    }

    public function updatedStock()
    {
        $this->calcularTotales();
    }

    private function calcularTotales()
    {
        $precioCompra = floatval($this->precio_entrada ?? 0);
        $precioVenta  = floatval($this->precio ?? 0);
        $stock        = intval($this->stock ?? 0);

        $this->total_compra = $precioCompra * $stock;
        $this->total_venta  = $precioVenta * $stock;
    }

    public function openEditModal($id)
    {
        $articulo = Inventario::find($id);

        if ($articulo) {
            $this->selectedArticuloId = $articulo->id;
            $this->articulo = $articulo->articulo;
            $this->precio = $articulo->precio; // venta
            $this->precio_entrada = $articulo->precio_entrada; // compra
            $this->stock = null; // en editar se usa como "stock a añadir"
            $this->estado = $articulo->estado;

            $this->calcularTotales();

            $this->dispatch('show-edit-modal');
        } else {
            session()->flash('error', 'El artículo no existe.');
        }
    }

    public function openCreateModal()
    {
        $this->resetInputFields();
        $this->dispatch('show-create-modal');
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
        'articulo'        => 'required|string|max:255',
        'precio_entrada'  => 'required|numeric|min:0',
        'precio'          => 'required|numeric|min:0',
        'stock'           => 'required|integer|min:0',
        'estado'          => 'required|boolean',
    ]);

    // ✅ CALCULAR TOTAL DE COMPRA
    $totalCompra = $this->precio_entrada * $this->stock;

    $inventario = Inventario::create([
        'articulo'       => $this->articulo,
        'precio_entrada' => $this->precio_entrada,
        'precio'         => $this->precio,
        'stock'          => $this->stock,
        'estado'         => $this->estado,
        'total_compra'   => $totalCompra, // ✅ GUARDADO
    ]);

    // Evento (venta)
    $precioTotalVenta = $this->precio * $this->stock;

    Eventos::create([
        'articulo'       => $this->articulo,
        'precio'         => $precioTotalVenta,
        'stock'          => $this->stock,
        'vendido'        => 0,
        'precio_vendido' => 0,
        'inventario_id'  => $inventario->id,
        'usuario_id'     => Auth::id(),
    ]);

    session()->flash('message', 'Artículo registrado correctamente.');

    $this->resetInputFields();
    $this->dispatch('close-modal');
}


  public function update()
{
    $this->validate([
        'articulo'        => 'required|string|max:255',
        'precio_entrada'  => 'required|numeric|min:0',
        'precio'          => 'required|numeric|min:0',
        'stock'           => 'required|integer|min:1',
        'estado'          => 'required|boolean',
    ]);

    $articulo = Inventario::find($this->selectedArticuloId);

    if ($articulo) {

        // ✅ total de la NUEVA compra
        $nuevoTotalCompra = $this->precio_entrada * $this->stock;

        $articulo->update([
            'articulo'       => $this->articulo,
            'precio_entrada' => $this->precio_entrada,
            'precio'         => $this->precio,
            'stock'          => $articulo->stock + $this->stock,
            'estado'         => $this->estado,

            // ✅ SUMA del total de compras
            'total_compra'   => $articulo->total_compra + $nuevoTotalCompra,
        ]);

        // Evento
        $precioTotalVenta = $this->precio * $this->stock;

        Eventos::create([
            'articulo'       => $this->articulo,
            'precio'         => $precioTotalVenta,
            'stock'          => $this->stock,
            'vendido'        => 0,
            'precio_vendido' => 0,
            'inventario_id'  => $articulo->id,
            'usuario_id'     => Auth::id(),
        ]);

        session()->flash('message', 'Stock añadido y compra registrada.');

        $this->resetInputFields();
        $this->dispatch('close-modal');
    }
}


    private function resetInputFields()
    {
        $this->articulo = '';
        $this->precio = null;
        $this->precio_entrada = null;
        $this->stock = null;
        $this->estado = null;
        $this->selectedArticuloId = null;

        // ✅ reset totales
        $this->total_compra = 0;
        $this->total_venta = 0;
    }
}
