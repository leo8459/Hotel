<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Inventario;
use App\Models\Eventos;
use App\Models\Habitacion;
use Illuminate\Support\Facades\Auth;

class Inventarios extends Component
{
    use WithPagination;

    public $searchTerm = '';
    public $articulo, $precio, $stock, $estado, $precio_entrada;
    public $selectedArticuloId = null;
    public $salidaInventarioId = null;
    public $salidaCantidad = 0;

    // ✅ Totales automáticos
    public $total_compra = 0;
    public $total_venta = 0;

    public function render()
    {
        $articulos = Inventario::where('articulo', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $freezerTotales = $this->getFreezerTotales();

        $freezerDetalles = $this->getFreezerDetalles();

        return view('livewire.inventarios', compact('articulos', 'freezerTotales', 'freezerDetalles'));
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

    public function openSalidaModal($id)
    {
        $articulo = Inventario::find($id);
        if (!$articulo) {
            session()->flash('error', 'El artículo no existe.');
            return;
        }

        $this->salidaInventarioId = $articulo->id;
        $this->salidaCantidad = 1;
        $this->dispatch('show-salida-modal');
    }

    public function registrarSalida()
    {
        $this->registrarSalidaInterna(false);
    }

    public function registrarSalidaConCobro()
    {
        $this->registrarSalidaInterna(true);
    }

    private function registrarSalidaInterna(bool $conCobro): void
    {
        $this->validate([
            'salidaInventarioId' => 'required|integer|exists:inventarios,id',
            'salidaCantidad'     => 'required|integer|min:1',
        ]);

        $inventario = Inventario::find($this->salidaInventarioId);
        if (!$inventario) {
            session()->flash('error', 'El art?culo no existe.');
            return;
        }

        if ($inventario->stock < $this->salidaCantidad) {
            session()->flash('error', "Stock insuficiente. Disponible: {$inventario->stock}");
            return;
        }

        $inventario->decrement('stock', $this->salidaCantidad);

        $precioVendido = $conCobro ? ($inventario->precio * $this->salidaCantidad) : 0;
        $tipoVenta = $conCobro ? 'VENTA' : 'SALIDA';

        Eventos::create([
            'articulo'       => $inventario->articulo,
            'precio'         => 0,
            'stock'          => 0,
            'vendido'        => $this->salidaCantidad,
            'tipo_venta'     => $tipoVenta,
            'precio_vendido' => $precioVendido,
            'inventario_id'  => $inventario->id,
            'usuario_id'     => Auth::id(),
        ]);

        session()->flash('message', $conCobro ? 'Salida con cobro registrada correctamente.' : 'Salida registrada correctamente.');
        $this->salidaInventarioId = null;
        $this->salidaCantidad = 0;
        $this->dispatch('close-modal');
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

    private function getFreezerTotales(): array
    {
        $totales = [];

        $freezers = Habitacion::query()->pluck('freezer_stock');
        foreach ($freezers as $freezer) {
            if (!is_array($freezer)) {
                continue;
            }

            foreach ($freezer as $id => $qty) {
                $id = (int)$id;
                $qty = (int)$qty;
                if ($id <= 0 || $qty <= 0) {
                    continue;
                }

                $totales[$id] = ($totales[$id] ?? 0) + $qty;
            }
        }

        return $totales;
    }

    private function getFreezerDetalles(): array
    {
        $detalles = [];

        $habitaciones = Habitacion::query()->select('id', 'habitacion', 'freezer_stock')->get();
        foreach ($habitaciones as $hab) {
            $freezer = $hab->freezer_stock;
            if (!is_array($freezer)) {
                continue;
            }

            $habLabel = trim((string)($hab->habitacion ?? ''));
            $habLabel = $habLabel !== '' ? $habLabel : 'ID ' . $hab->id;

            foreach ($freezer as $id => $qty) {
                $id = (int)$id;
                $qty = (int)$qty;
                if ($id <= 0 || $qty <= 0) {
                    continue;
                }

                $detalles[$id][] = [
                    'hab' => $habLabel,
                    'qty' => $qty,
                ];
            }
        }

        return $detalles;
    }
}
