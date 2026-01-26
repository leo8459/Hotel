<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Alquiler;
use App\Models\Inventario;
use App\Models\Habitacion;
use App\Models\Eventos;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

    // ✅ FREEZER
    public $usarFreezer = false; // checkbox
    public $freezerStock = [];   // copia del freezer de la habitación para mostrar

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

        $this->inventarios = Inventario::orderBy('articulo')->get();

        // ✅ freezer actual de la habitación
        $hab = Habitacion::find($this->alquiler->habitacion_id);
        $this->freezerStock = $hab?->freezer_stock ?? [];

        // ✅ si ya venía guardado en alquiler
        $this->usarFreezer = (bool) ($this->alquiler->usar_freezer ?? false);

        // Cargar consumos del alquiler
        $detalle = json_decode($this->alquiler->inventario_detalle, true) ?? [];
        $this->consumos = $this->detalleToConsumos($detalle);

        $entrada = $this->alquiler->entrada ?: $this->alquiler->created_at;
        $this->entrada = Carbon::parse($entrada, 'America/La_Paz')->format('Y-m-d\TH:i');
        $this->salida  = Carbon::now('America/La_Paz')->format('Y-m-d\TH:i');

        $this->actualizarTotalArticulos();
        $this->calcularTiempoYCosto();
    }

    /**
     * Convierte inventario_detalle a consumos asociativo:
     * [id => ['articulo'=>..., 'precio'=>..., 'cantidad'=>...]]
     */
    private function detalleToConsumos(array $detalle): array
    {
        $out = [];

        foreach ($detalle as $key => $item) {
            $id = $item['id'] ?? $key;
            if (!$id) continue;

            $inv = Inventario::find($id);
            $out[(int)$id] = [
                'articulo' => $inv?->articulo ?? ($item['articulo'] ?? 'Sin nombre'),
                'precio'   => (float) ($inv?->precio ?? ($item['precio'] ?? 0)),
                'cantidad' => (int) ($item['cantidad'] ?? 0),
            ];
        }

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

    // ✅ Freezer disponible (solo informativo)
    public function getFreezerDisponible(int $id): int
    {
        return (int) ($this->freezerStock[$id] ?? 0);
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

    // ========================= Inventario UI =========================

    public function agregarInventario()
    {
        if (!$this->inventario_id) return;

        $item = collect($this->inventarios)->firstWhere('id', (int)$this->inventario_id);
        if (!$item) return;

        $id = (int) $item->id;

        $stockTotal = (int) $item->stock;
        $enCarrito  = (int) ($this->consumos[$id]['cantidad'] ?? 0);

        if ($stockTotal - $enCarrito <= 0) {
            $this->dispatch('toast', type: 'error', message: "No hay stock disponible de {$item->articulo}");
            return;
        }

        if (!isset($this->consumos[$id])) {
            $this->consumos[$id] = [
                'articulo' => $item->articulo,
                'precio'   => (float)$item->precio,
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
            $this->dispatch('toast', type: 'warning', message: "Stock máximo disponible: {$stockTotal}");
        }

        $this->consumos[$id]['cantidad'] = $cantidad;
        $this->actualizarTotalArticulos();
    }

    private function actualizarTotalArticulos()
    {
        $this->total = collect($this->consumos)
            ->sum(fn($i) => ((float)($i['precio'] ?? 0)) * ((int)($i['cantidad'] ?? 0)));

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
        $precioHora = (float)($habitacion?->preciohora ?? 0);

        $this->costoServicio = round($this->horas * $precioHora, 2);
        $this->actualizarTotalGeneral();
    }

    private function actualizarTotalGeneral()
    {
        $this->totalGeneral = round(((float)$this->costoServicio) + ((float)$this->total), 2);
    }

    // ========================= LOGICA: INVENTARIO + FREEZER + EVENTOS =========================
    /**
     * Regla:
     * - SIEMPRE descuenta del INVENTARIO (almacén) por delta positivo
     * - Si usarFreezer = true, además descuenta del FREEZER (hasta donde alcance)
     * - Si delta negativo: devuelve SOLO al INVENTARIO (no tocamos freezer por seguridad)
     *
     * Devuelve desglose del delta positivo por origen en $origenDelta:
     * [id => ['freezer'=>x,'inventario'=>y]]
     */
    private function ajustarConsumoConFreezer(array $viejoQty, array $nuevoQty, array &$tipoVentaDelta = []): void
{
    $habitacion = Habitacion::lockForUpdate()->find($this->alquiler->habitacion_id);
    $freezer = $habitacion?->freezer_stock ?? [];

    $ids = array_unique(array_merge(array_keys($viejoQty), array_keys($nuevoQty)));

    foreach ($ids as $id) {
        $old = (int)($viejoQty[$id] ?? 0);
        $new = (int)($nuevoQty[$id] ?? 0);
        $delta = $new - $old;

        if ($delta === 0) continue;

        $inv = Inventario::lockForUpdate()->find($id);
        if (!$inv) continue;

        // ✅ AUMENTA consumo
        if ($delta > 0) {

            // 1) SIEMPRE descontar del INVENTARIO
            if ($inv->stock < $delta) {
                throw new \Exception("Stock insuficiente en almacén de {$inv->articulo}. Disponible: {$inv->stock}, requerido: {$delta}");
            }
            $inv->decrement('stock', $delta);

            // 2) Si usar freezer, descontar freezer (hasta donde alcance)
            $desdeFreezer = 0;
            if ($this->usarFreezer && $habitacion) {
                $freezerQty = (int)($freezer[$id] ?? 0);
                $desdeFreezer = min($delta, $freezerQty);
                $freezer[$id] = $freezerQty - $desdeFreezer;
            }

            // ✅ Tipo de venta para EVENTOS (UNO SOLO)
            $tipoVentaDelta[$id] = ($this->usarFreezer && $desdeFreezer > 0)
                ? 'FREEZER'
                : 'INVENTARIO';
        }

        // ✅ DISMINUYE consumo (devolución)
        if ($delta < 0) {
            $inv->increment('stock', abs($delta));
            // opcional: aquí no registramos eventos porque es devolución
        }
    }

    // guardar freezer actualizado si corresponde
    if ($this->usarFreezer && $habitacion) {
        foreach ($freezer as $k => $v) {
            if ((int)$v <= 0) unset($freezer[$k]);
        }
        $habitacion->update(['freezer_stock' => $freezer]);
        $this->freezerStock = $freezer;
    }
}


    // ========================= Guardar =========================

   public function guardarCambios()
{
    try {
        DB::transaction(function () {

            // 🔥 VIEJO: lo ya guardado en alquiler
            $detalleViejo = json_decode($this->alquiler->inventario_detalle, true) ?? [];
            $viejoQty = $this->detalleToQtyMap($detalleViejo); // [id => qty]

            // 🔥 NUEVO: lo que quedó en la UI
            $nuevoQty = [];
            foreach ($this->consumos as $id => $item) {
                $id  = (int)$id;
                $qty = (int)($item['cantidad'] ?? 0);
                if ($id > 0 && $qty > 0) {
                    $nuevoQty[$id] = $qty;
                }
            }

            // ✅ Ajuste stock + freezer (y me devuelve tipo_venta por item vendido)
            $tipoVentaDelta = []; // [id => 'FREEZER'|'INVENTARIO']
            $this->ajustarConsumoConFreezer($viejoQty, $nuevoQty, $tipoVentaDelta);

            // ✅ Registrar EVENTOS SOLO por delta positivo (UNA SOLA VEZ por item)
            foreach ($nuevoQty as $id => $newQty) {
                $oldQty = (int)($viejoQty[$id] ?? 0);
                $deltaVendida = $newQty - $oldQty;

                if ($deltaVendida <= 0) continue;

                $inv = Inventario::find($id);
                if (!$inv) continue;

                $tipo = $tipoVentaDelta[$id] ?? ($this->usarFreezer ? 'FREEZER' : 'INVENTARIO');

                Eventos::create([
                    'articulo'       => $inv->articulo,
                    'precio'         => 0,
                    'stock'          => 0,
                    'vendido'        => $deltaVendida,
                    'precio_vendido' => $deltaVendida * (float)($inv->precio ?? 0),
                    'tipo_venta'     => $tipo, // ✅ FREEZER o INVENTARIO
                    'habitacion_id'  => $this->alquiler->habitacion_id,
                    'inventario_id'  => $inv->id,
                    'usuario_id'     => auth()->id(),
                ]);
            }

            // ✅ Persistir horas/fechas
            $entrada = Carbon::parse($this->entrada, 'America/La_Paz');
            $salida  = Carbon::parse($this->salida,  'America/La_Paz');

            // ✅ Guardar inventario_detalle en formato consistente
            $detalleGuardar = [];
            foreach ($nuevoQty as $id => $qty) {
                $inv = Inventario::find($id);

                $detalleGuardar[$id] = [
                    'id'       => $id,
                    'articulo' => $inv?->articulo ?? ($this->consumos[$id]['articulo'] ?? 'Sin nombre'),
                    'precio'   => (float)($inv?->precio ?? ($this->consumos[$id]['precio'] ?? 0)),
                    'cantidad' => (int)$qty,
                ];
            }

            // ✅ Actualizar alquiler
            $this->alquiler->update([
                'aireacondicionado'  => $this->aireacondicionado,
                'aire_inicio'        => $this->aire_inicio ?: null,
                'inventario_detalle' => json_encode($detalleGuardar),
                'entrada'            => $entrada,
                'salida'             => $salida,
                'horas'              => $this->horas,
                'total'              => $this->totalGeneral,
                'usar_freezer'       => $this->usarFreezer,
            ]);
        });

        session()->flash('mensaje', 'Alquiler actualizado correctamente.');
        return redirect()->route('crear-alquiler');

    } catch (\Throwable $e) {
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
