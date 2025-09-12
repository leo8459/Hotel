<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Alquiler;
use App\Models\Inventario;
use App\Models\Habitacion;
use Carbon\Carbon;

class Editaralquiler extends Component
{
    public $alquiler;

    // Campos existentes
    public $aireacondicionado;
    public $aire_inicio;
    public $inventario_id;
    public $inventarios = [];
    public $consumos = [];
    public $total = 0; // total artículos

    // ➕ Nuevos campos de tiempo/costo
    public $entrada;              // datetime-local (readonly en vista)
    public $salida;               // datetime-local (default: now)
    public $horas = 1;            // mínimo 1
    public $costoServicio = 0.0;  // horas * preciohora
    public $totalGeneral = 0.0;   // costoServicio + total artículos

    public function mount($alquiler)
    {
        $this->alquiler = Alquiler::findOrFail($alquiler);

        // Aire
        $this->aireacondicionado = (bool) $this->alquiler->aireacondicionado;
        $this->aire_inicio = $this->alquiler->aire_inicio
            ? Carbon::parse($this->alquiler->aire_inicio)->format('Y-m-d\TH:i')
            : null;

        // Inventarios/consumos
        $this->consumos = $this->alquiler->inventario_detalle
            ? json_decode($this->alquiler->inventario_detalle, true)
            : [];
        $this->inventarios = Inventario::all();

        // ⏱️ Tiempos
        $entrada = $this->alquiler->entrada ?: $this->alquiler->created_at;
        $this->entrada = Carbon::parse($entrada, 'America/La_Paz')->format('Y-m-d\TH:i');
        $this->salida  = Carbon::now('America/La_Paz')->format('Y-m-d\TH:i');

        // Totales iniciales
        $this->actualizarTotalArticulos();
        $this->calcularTiempoYCosto();
    }

    /* =========================
     * Helpers de stock
     * ========================= */
    public function getStockTotal(int $id): int
    {
        $inv = collect($this->inventarios)->firstWhere('id', $id);
        return (int) ($inv->stock ?? 0);
    }

    public function getStockDisponible(int $id): int
    {
        // Disponible = stock total - cantidad en este alquiler (carrito)
        $stock = $this->getStockTotal($id);
        $enCarrito = (int) ($this->consumos[$id]['cantidad'] ?? 0);
        return max(0, $stock - $enCarrito);
    }

    /* =========================
     *   Inventario
     * ========================= */
    public function agregarInventario()
    {
        if (!$this->inventario_id) return;

        $item = collect($this->inventarios)->firstWhere('id', (int) $this->inventario_id);
        if (!$item) return;

        $actual = (int) ($this->consumos[$item->id]['cantidad'] ?? 0);
        $stock  = (int) ($item->stock ?? 0);

        // No permitir superar stock
        if ($actual + 1 > $stock) {
            $this->dispatch('toast', type: 'warning', message: "Stock insuficiente de {$item->articulo}. Máx: {$stock}");
            return;
        }

        if (isset($this->consumos[$item->id])) {
            $this->consumos[$item->id]['cantidad'] += 1;
        } else {
            $this->consumos[$item->id] = [
                'articulo' => $item->articulo,
                'precio'   => $item->precio,
                'cantidad' => 1,
            ];
        }

        $this->actualizarTotalArticulos();
    }

    public function eliminarConsumo($id)
    {
        unset($this->consumos[$id]);
        $this->actualizarTotalArticulos();
    }

    public function actualizarCantidad($id, $cantidad)
    {
        if (!isset($this->consumos[$id])) return;

        $cantidad = max(1, (int) $cantidad);
        $stock = $this->getStockTotal((int)$id);

        if ($cantidad > $stock) {
            $cantidad = $stock;
            $this->dispatch('toast', type: 'warning', message: "No puedes superar el stock disponible ({$stock}).");
        }

        $this->consumos[$id]['cantidad'] = $cantidad;
        $this->actualizarTotalArticulos();
    }

    private function actualizarTotalArticulos()
    {
        $this->total = collect($this->consumos)->sum(fn($i) => ($i['precio'] ?? 0) * ($i['cantidad'] ?? 0));
        $this->actualizarTotalGeneral();
    }

    /* =========================
     *   Tiempo y costos
     * ========================= */
    public function actualizarSalida($valor)
    {
        $this->salida = $valor;
        $this->calcularTiempoYCosto();
    }

    public function calcularTiempoYCosto()
    {
        $entrada = Carbon::parse($this->entrada, 'America/La_Paz');
        $salida  = Carbon::parse($this->salida,  'America/La_Paz');

        if ($salida->lessThan($entrada)) {
            $salida = $entrada->copy();
            $this->salida = $salida->format('Y-m-d\TH:i');
        }

        $diffMin = max(0, $entrada->diffInMinutes($salida));
        $this->horas = max(1, (int) ceil($diffMin / 60));

        $habitacion = Habitacion::find($this->alquiler->habitacion_id);
        $precioHora = $habitacion?->preciohora ?? 0;

        $this->costoServicio = round($this->horas * $precioHora, 2);
        $this->actualizarTotalGeneral();
    }

    private function actualizarTotalGeneral()
    {
        $this->totalGeneral = round($this->costoServicio + $this->total, 2);
    }

    /* =========================
     *   Guardar
     * ========================= */
    public function guardarCambios()
    {
        // 1) Restaurar stock anterior
        $original = json_decode($this->alquiler->inventario_detalle, true) ?? [];
        foreach ($original as $id => $item) {
            $inv = Inventario::find($id);
            if ($inv) {
                $inv->stock += (int) ($item['cantidad'] ?? 0);
                $inv->save();
            }
        }

        // 2) Aplicar nuevo descuento
        foreach ($this->consumos as $id => $item) {
            $inv = Inventario::find($id);
            if ($inv) {
                $inv->stock -= (int) ($item['cantidad'] ?? 0);
                $inv->save();
            }
        }

        // 3) Persistir tiempo y costos
        $entrada = Carbon::parse($this->entrada, 'America/La_Paz');
        $salida  = Carbon::parse($this->salida,  'America/La_Paz');

        $this->alquiler->update([
            'aireacondicionado'  => $this->aireacondicionado,
            'aire_inicio'        => $this->aire_inicio ?: null,
            'inventario_detalle' => json_encode($this->consumos),
            'entrada'            => $entrada,
            'salida'             => $salida,
            'horas'              => $this->horas,
            'total'              => $this->totalGeneral,
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
