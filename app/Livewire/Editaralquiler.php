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

    // Tiempo/costo
    public $entrada;
    public $salida;
    public $horas = 1;
    public $costoServicio = 0.0;
    public $totalGeneral = 0.0;

    public function mount($alquiler)
    {
        $this->alquiler = Alquiler::findOrFail($alquiler);

        $this->aireacondicionado = (bool) $this->alquiler->aireacondicionado;
        $this->aire_inicio = $this->alquiler->aire_inicio
            ? Carbon::parse($this->alquiler->aire_inicio)->format('Y-m-d\TH:i')
            : null;

        $this->inventarios = Inventario::all();

        // Cargar consumos del alquiler (puede venir como array asociativo o indexado)
        $detalle = json_decode($this->alquiler->inventario_detalle, true) ?? [];
        $this->consumos = $this->detalleToConsumos($detalle);

        $entrada = $this->alquiler->entrada ?: $this->alquiler->created_at;
        $this->entrada = Carbon::parse($entrada, 'America/La_Paz')->format('Y-m-d\TH:i');
        $this->salida  = Carbon::now('America/La_Paz')->format('Y-m-d\TH:i');

        $this->actualizarTotalArticulos();
        $this->calcularTiempoYCosto();
    }

    /**
     * Convierte inventario_detalle (cualquier formato) a consumos asociativo:
     * [id => ['articulo'=>..., 'precio'=>..., 'cantidad'=>...]]
     */
    private function detalleToConsumos(array $detalle): array
    {
        $out = [];

        foreach ($detalle as $key => $item) {
            // Puede venir como ['id'=>x,'cantidad'=>y] o como [id => ['cantidad'=>y]]
            $id = $item['id'] ?? $key;
            if (!$id) continue;

            $inv = Inventario::find($id);
            $out[$id] = [
                'articulo' => $inv?->articulo ?? ($item['articulo'] ?? 'Sin nombre'),
                'precio'   => $inv?->precio   ?? ($item['precio'] ?? 0),
                'cantidad' => (int) ($item['cantidad'] ?? 0),
            ];
        }

        // quitar cantidades 0 o negativas
        foreach ($out as $id => $it) {
            if (($it['cantidad'] ?? 0) <= 0) unset($out[$id]);
        }

        return $out;
    }
/* =========================
 * Helpers de stock (para la vista)
 * ========================= */
public function getStockTotal(int $id): int
{
    $inv = collect($this->inventarios)->firstWhere('id', $id);
    return (int) ($inv->stock ?? 0);
}

public function getStockDisponible(int $id): int
{
    $stock = $this->getStockTotal($id);
    $enCarrito = (int) ($this->consumos[$id]['cantidad'] ?? 0);
    return max(0, $stock - $enCarrito);
}


    /**
     * Normaliza a mapa simple: [id => cantidad]
     */
    private function detalleToQtyMap(array $detalle): array
    {
        $map = [];
        foreach ($detalle as $key => $item) {
            $id = $item['id'] ?? $key;
            if (!$id) continue;
            $qty = (int) ($item['cantidad'] ?? 0);
            if ($qty > 0) $map[(int)$id] = $qty;
        }
        return $map;
    }

    /**
     * Ajusta stock por DELTA: nuevo - viejo
     * - Si delta > 0: se debe DESCONTAR delta
     * - Si delta < 0: se debe DEVOLVER abs(delta)
     */
    private function ajustarStockPorDelta(array $viejoQty, array $nuevoQty): void
    {
        $ids = array_unique(array_merge(array_keys($viejoQty), array_keys($nuevoQty)));

        foreach ($ids as $id) {
            $old = (int) ($viejoQty[$id] ?? 0);
            $new = (int) ($nuevoQty[$id] ?? 0);
            $delta = $new - $old;

            if ($delta === 0) continue;

            $inv = Inventario::find($id);
            if (!$inv) continue;

            // Necesito más => descontar
            if ($delta > 0) {
                if ($inv->stock < $delta) {
                    throw new \Exception("Stock insuficiente de {$inv->articulo}. Disponible: {$inv->stock}, requerido: {$delta}");
                }
                $inv->decrement('stock', $delta);
            }

            // Me pasé => devolver
            if ($delta < 0) {
                $inv->increment('stock', abs($delta));
            }
        }
    }

    // ========================= Inventario UI =========================

   public function agregarInventario()
{
    if (!$this->inventario_id) return;

    $item = collect($this->inventarios)->firstWhere('id', (int)$this->inventario_id);
    if (!$item) return;

    $id = (int) $item->id;

    // 🔴 STOCK TOTAL
    $stockTotal = (int) $item->stock;

    // 🔴 YA EN CARRITO
    $enCarrito = (int) ($this->consumos[$id]['cantidad'] ?? 0);

    // ❌ SIN STOCK
    if ($stockTotal - $enCarrito <= 0) {
        $this->dispatch(
            'toast',
            type: 'error',
            message: "No hay stock disponible de {$item->articulo}"
        );
        return;
    }

    // ✅ AÑADIR
    if (!isset($this->consumos[$id])) {
        $this->consumos[$id] = [
            'articulo' => $item->articulo,
            'precio'   => $item->precio,
            'cantidad' => 1,
        ];
    } else {
        $this->consumos[$id]['cantidad'] += 1;
    }

    $this->inventario_id = null;
    $this->actualizarTotalArticulos();
}


    public function eliminarConsumo($id)
    {
        unset($this->consumos[(int)$id]);
        $this->actualizarTotalArticulos();
    }

   public function actualizarCantidad($id, $cantidad)
{
    $id = (int)$id;
    if (!isset($this->consumos[$id])) return;

    $cantidad = max(1, (int)$cantidad);

    $stockTotal = $this->getStockTotal($id);

    if ($cantidad > $stockTotal) {
        $cantidad = $stockTotal;

        $this->dispatch(
            'toast',
            type: 'warning',
            message: "Stock máximo disponible: {$stockTotal}"
        );
    }

    $this->consumos[$id]['cantidad'] = $cantidad;
    $this->actualizarTotalArticulos();
}


    private function actualizarTotalArticulos()
    {
        $this->total = collect($this->consumos)->sum(fn($i) => ($i['precio'] ?? 0) * ($i['cantidad'] ?? 0));
        $this->actualizarTotalGeneral();
    }

    // ========================= Tiempo y costos =========================

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

    // ========================= Guardar (CORREGIDO) =========================

    public function guardarCambios()
    {
        try {
            // 🔥 VIEJO: lo que ya estaba guardado (y ya estaba descontado)
            $detalleViejo = json_decode($this->alquiler->inventario_detalle, true) ?? [];
            $viejoQty = $this->detalleToQtyMap($detalleViejo);

            // 🔥 NUEVO: lo que estás dejando ahora en el editor
            $nuevoQty = [];
            foreach ($this->consumos as $id => $item) {
                $id = (int)$id;
                $qty = (int)($item['cantidad'] ?? 0);
                if ($qty > 0) $nuevoQty[$id] = $qty;
            }

            // ✅ Ajustar stock SOLO por diferencia
            $this->ajustarStockPorDelta($viejoQty, $nuevoQty);

            // Persistir
            $entrada = Carbon::parse($this->entrada, 'America/La_Paz');
            $salida  = Carbon::parse($this->salida,  'America/La_Paz');

            // Guardar en formato consistente: [id => ['id'=>id,'cantidad'=>x]]
            $detalleGuardar = [];
            foreach ($nuevoQty as $id => $qty) {
                $inv = Inventario::find($id);
                $detalleGuardar[$id] = [
                    'id'       => $id,
                    'articulo' => $inv?->articulo ?? ($this->consumos[$id]['articulo'] ?? 'Sin nombre'),
                    'precio'   => $inv?->precio ?? ($this->consumos[$id]['precio'] ?? 0),
                    'cantidad' => $qty,
                ];
            }

            $this->alquiler->update([
                'aireacondicionado'  => $this->aireacondicionado,
                'aire_inicio'        => $this->aire_inicio ?: null,
                'inventario_detalle' => json_encode($detalleGuardar),
                'entrada'            => $entrada,
                'salida'             => $salida,
                'horas'              => $this->horas,
                'total'              => $this->totalGeneral,
            ]);

            session()->flash('mensaje', 'Alquiler actualizado correctamente.');
            return redirect()->route('crear-alquiler');

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            return;
        }
    }

    public function render()
    {
        return view('livewire.editaralquiler')
            ->extends('adminlte::page')
            ->section('content');
    }
}
