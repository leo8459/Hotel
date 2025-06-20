<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Alquiler;
use App\Models\Inventario;
use Carbon\Carbon;

class Editaralquiler extends Component
{
    public $alquiler;
    public $aireacondicionado;
    public $aire_inicio;
    public $inventario_id;
    public $inventarios = [];
    public $consumos = [];
    public $total = 0;

    public function mount($alquiler)
    {
        $this->alquiler = Alquiler::findOrFail($alquiler);
        $this->aireacondicionado = $this->alquiler->aireacondicionado;
        $this->aire_inicio = $this->alquiler->aire_inicio ? Carbon::parse($this->alquiler->aire_inicio)->format('Y-m-d\TH:i') : null;

        // Parsear consumos anteriores (si vienen como JSON)
        $this->consumos = $this->alquiler->inventario_detalle ? json_decode($this->alquiler->inventario_detalle, true) : [];

        $this->actualizarTotal();

        $this->inventarios = Inventario::all();
    }

    public function agregarInventario()
    {
        if (!$this->inventario_id) return;

        $item = Inventario::find($this->inventario_id);
        if (!$item) return;

        if (isset($this->consumos[$item->id])) {
            $this->consumos[$item->id]['cantidad'] += 1;
        } else {
            $this->consumos[$item->id] = [
    'articulo' => $item->articulo, // ✅ Campo correcto
                'precio'   => $item->precio,
                'cantidad' => 1
            ];
        }

        $this->actualizarTotal();
    }

    public function eliminarConsumo($id)
    {
        unset($this->consumos[$id]);
        $this->actualizarTotal();
    }

    public function actualizarCantidad($id, $cantidad)
    {
        if (isset($this->consumos[$id])) {
            $this->consumos[$id]['cantidad'] = max(1, (int) $cantidad);
            $this->actualizarTotal();
        }
    }

    public function actualizarTotal()
    {
        $this->total = collect($this->consumos)->sum(fn($item) => $item['precio'] * $item['cantidad']);
    }

    public function guardarCambios()
{
    // 1. Restaurar stock anterior (si existía)
    $original = json_decode($this->alquiler->inventario_detalle, true) ?? [];

    foreach ($original as $id => $item) {
        $inv = \App\Models\Inventario::find($id);
        if ($inv) {
            $inv->stock += $item['cantidad'];
            $inv->save();
        }
    }

    // 2. Aplicar nuevo descuento
    foreach ($this->consumos as $id => $item) {
        $inv = \App\Models\Inventario::find($id);
        if ($inv) {
            $inv->stock -= $item['cantidad'];
            $inv->save();
        }
    }

    // 3. Guardar cambios en el alquiler
    $this->alquiler->update([
        'aireacondicionado'   => $this->aireacondicionado,
        'aire_inicio'         => $this->aire_inicio ?? null,
        'inventario_detalle'  => json_encode($this->consumos),
        'total'               => $this->total,
    ]);

    session()->flash('mensaje', 'Alquiler actualizado correctamente.');
    return redirect()->route('crear-alquiler');
}


    public function render()
    {
        return view('livewire.editaralquiler')
            ->extends('adminlte::page')
            ->section('content');
    }
}
