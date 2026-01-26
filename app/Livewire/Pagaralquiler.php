<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Alquiler;
use App\Models\Inventario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\BoletaAlquiler;
use App\Models\Eventos;

class Pagaralquiler extends Component
{
    public $alquilerId;
    public $alquiler;
    public $horaSalida;
    public $tipopago = '';
    public $selectedInventarioId;
    public $selectedInventarios = []; // [id => ['id','articulo','precio','cantidad','stock_actual','reservado','max_permitido']]
    public $tarifaSeleccionada = 'HORAS';

    public $totalHoras = 0;
    public $totalInventario = 0;
    public $totalGeneral = 0;
    public $usarFreezer = false;

    public function mount(Alquiler $alquiler)
    {
        $this->alquiler   = $alquiler;
        $this->alquilerId = $alquiler->id;
        $this->horaSalida = Carbon::now('America/La_Paz')->format('Y-m-d\TH:i');

        $detalleViejo = json_decode($alquiler->inventario_detalle, true) ?? [];
        $viejoQty = $this->detalleToQtyMap($detalleViejo); // [id => cantidad reservada]

        // Construir selectedInventarios con limites correctos
        $this->selectedInventarios = [];
        foreach ($viejoQty as $id => $qty) {
            $inv = Inventario::find($id);
            if (!$inv) continue;

            $stockActual = (int)$inv->stock; // stock real en BD (ya con reserva descontada)
            $reservado   = (int)$qty;        // lo que ya estaba reservado en este alquiler
            $maxPermitido = $stockActual + $reservado;

            $this->selectedInventarios[$id] = [
                'id'            => $id,
                'articulo'      => $inv->articulo,
                'precio'        => (float)$inv->precio,
                'cantidad'      => $reservado,
                'stock_actual'  => $stockActual,
                'reservado'     => $reservado,
                'max_permitido' => $maxPermitido,
            ];
        }
        $this->usarFreezer = (bool)($alquiler->usar_freezer ?? false);

        $this->recalcularTotales();
    }

    public function render()
    {
        return view('livewire.pagaralquiler', [
            'inventariosDisponibles' => Inventario::where('stock', '>', 0)->get(),
            'habitacion'             => $this->alquiler->habitacion,
            'freezer'    => $this->alquiler->habitacion?->freezer_stock ?? [],

        ])->extends('adminlte::page')->section('content');
    }

    private function detalleToQtyMap(array $detalle): array
    {
        $map = [];
        foreach ($detalle as $key => $item) {
            $id = $item['id'] ?? $key;
            if (!$id) continue;
            $qty = (int)($item['cantidad'] ?? 0);
            if ($qty > 0) $map[(int)$id] = $qty;
        }
        return $map;
    }

    private function ajustarStockPorDelta(array $viejoQty, array $nuevoQty): void
    {
        $ids = array_unique(array_merge(array_keys($viejoQty), array_keys($nuevoQty)));

        foreach ($ids as $id) {
            $old = (int)($viejoQty[$id] ?? 0);
            $new = (int)($nuevoQty[$id] ?? 0);
            $delta = $new - $old;

            if ($delta === 0) continue;

            $inv = Inventario::find($id);
            if (!$inv) continue;

            if ($delta > 0) {
                if ($inv->stock < $delta) {
                    throw new \Exception("Stock insuficiente de {$inv->articulo}. Disponible: {$inv->stock}, requerido: {$delta}");
                }
                $inv->decrement('stock', $delta);
            }

            if ($delta < 0) {
                $inv->increment('stock', abs($delta));
            }
        }
    }

    // ✅ refresca stock_actual/max_permitido por si cambió el stock en BD
    private function refreshLimits(): void
    {
        foreach ($this->selectedInventarios as $id => $item) {
            $inv = Inventario::find($id);
            if (!$inv) continue;

            $reservado = (int)($item['reservado'] ?? 0);
            $stockActual = (int)$inv->stock;

            $this->selectedInventarios[$id]['stock_actual']  = $stockActual;
            $this->selectedInventarios[$id]['max_permitido'] = $stockActual + $reservado;

            // clamp cantidad si se pasó
            $max = (int)$this->selectedInventarios[$id]['max_permitido'];
            $cant = (int)($this->selectedInventarios[$id]['cantidad'] ?? 0);
            if ($cant > $max) {
                $this->selectedInventarios[$id]['cantidad'] = $max;
            }
            if ($this->selectedInventarios[$id]['cantidad'] < 1) {
                $this->selectedInventarios[$id]['cantidad'] = 1;
            }
        }
    }

    public function addInventario()
    {
        if (!$this->selectedInventarioId) return;

        $inv = Inventario::find($this->selectedInventarioId);
        if (!$inv) return;

        $id = (int)$inv->id;

        // si no existe, lo agrego con reservado 0
        if (!isset($this->selectedInventarios[$id])) {
            $stockActual = (int)$inv->stock;
            $reservado = 0;
            $maxPermitido = $stockActual + $reservado;

            // si no hay stock, no dejar agregar
            if ($maxPermitido <= 0) {
                session()->flash('error', "No hay stock disponible de {$inv->articulo}.");
                $this->selectedInventarioId = null;
                return;
            }

            $this->selectedInventarios[$id] = [
                'id'            => $id,
                'articulo'      => $inv->articulo,
                'precio'        => (float)$inv->precio,
                'cantidad'      => 1,
                'stock_actual'  => $stockActual,
                'reservado'     => 0,
                'max_permitido' => $maxPermitido,
            ];
        } else {
            // subir +1 pero respetando max_permitido
            $this->refreshLimits();
            $max = (int)$this->selectedInventarios[$id]['max_permitido'];
            $cant = (int)$this->selectedInventarios[$id]['cantidad'];

            if ($cant + 1 > $max) {
                session()->flash('error', "No puedes superar el stock disponible de {$this->selectedInventarios[$id]['articulo']} (Máx: {$max}).");
                $this->selectedInventarioId = null;
                return;
            }

            $this->selectedInventarios[$id]['cantidad'] += 1;
        }

        $this->selectedInventarioId = null;
        $this->recalcularTotales();
    }

    public function removeInventario($id)
    {
        unset($this->selectedInventarios[(int)$id]);
        $this->recalcularTotales();
    }

    public function updatedSelectedInventarios()
    {
        // clamp por si escriben manualmente
        $this->refreshLimits();
        $this->recalcularTotales();
    }

    public function updatedHoraSalida()
    {
        $this->recalcularTotales();
    }

    public function updatedTarifaSeleccionada()
    {
        $this->recalcularTotales();
    }

    public function cambiarTarifa($valor)
    {
        $this->tarifaSeleccionada = $valor;
        $this->recalcularTotales();
    }

 public function pay()
{
    $this->validate([
        'tipopago'   => 'required|in:EFECTIVO,QR,TARJETA',
        'horaSalida' => 'required|date',
    ]);

    $pdf = null;

    DB::transaction(function () use (&$pdf) {

        // ✅ VIEJO (reservado en este alquiler)
        $detalleViejo = json_decode($this->alquiler->inventario_detalle, true) ?? [];
        $viejoQty = $this->detalleToQtyMap($detalleViejo); // [id => cantidad]

        // ✅ NUEVO (lo que queda en pantalla)
        $nuevoQty = [];
        foreach ($this->selectedInventarios as $id => $item) {
            $id  = (int)($item['id'] ?? $id);
            $qty = (int)($item['cantidad'] ?? 0);
            if ($id > 0 && $qty > 0) $nuevoQty[$id] = $qty;
        }

        // ✅ Ajuste stock con freezer + inventario y devuelve desglose por origen
        //    $origenDelta: [id => ['freezer'=>x,'inventario'=>y]] SOLO para deltas positivos
        $origenDelta = [];
        $this->ajustarConsumoConFreezer($viejoQty, $nuevoQty, $origenDelta);

        // ✅ Recalcular totales
        $this->recalcularTotales();

        // ✅ Eventos SOLO por lo NUEVO agregado (delta +)
        //    ✅ 1 SOLO EVENTO POR ITEM para evitar duplicados
        foreach ($nuevoQty as $id => $newQty) {
            $oldQty = (int)($viejoQty[$id] ?? 0);
            $deltaVendida = $newQty - $oldQty;
            if ($deltaVendida <= 0) continue;

            $inv = Inventario::find($id);
            if (!$inv) continue;

            $freezerUsado = (int)($origenDelta[$id]['freezer'] ?? 0);

            // Si marcó freezer y efectivamente se usó freezer -> FREEZER, si no INVENTARIO
            $tipoVenta = ($this->usarFreezer && $freezerUsado > 0)
                ? 'FREEZER'
                : 'INVENTARIO';

            Eventos::create([
                'articulo'        => $inv->articulo,
                'precio'          => 0,
                'stock'           => 0,
                'vendido'         => $deltaVendida,
                'precio_vendido'  => $deltaVendida * ((float)$inv->precio),
                'tipo_venta'      => $tipoVenta,
                'habitacion_id'   => $this->alquiler->habitacion_id,
                'inventario_id'   => $inv->id,
                'usuario_id'      => auth()->id(),
            ]);
        }

        // ✅ Guardar detalle consistente en alquiler
        $detalleGuardar = [];
        foreach ($nuevoQty as $id => $qty) {
            $inv = Inventario::find($id);
            $detalleGuardar[$id] = [
                'id'       => $id,
                'articulo' => $inv?->articulo ?? 'Sin nombre',
                'precio'   => (float)($inv?->precio ?? 0),
                'cantidad' => (int)$qty,
            ];
        }

        $salida     = Carbon::parse($this->horaSalida, 'America/La_Paz');
        $entrada    = Carbon::parse($this->alquiler->entrada, 'America/La_Paz');
        $habitacion = $this->alquiler->habitacion;

        $this->alquiler->update([
            'salida'              => $salida,
            'horas'               => $this->tarifaSeleccionada === 'NOCTURNA'
                ? null
                : ceil($entrada->diffInMinutes($salida) / 60),
            'tipopago'            => $this->tipopago,
            'total'               => $this->totalGeneral,
            'estado'              => 'Pagado',
            'usar_freezer'        => (bool)$this->usarFreezer,
            'tarifa_seleccionada' => $this->tarifaSeleccionada,
            'inventario_detalle'  => json_encode($detalleGuardar),
            'usuario_id'          => auth()->id(),
        ]);

        if ($habitacion) {
            $habitacion->update([
                'estado'       => 1,
                'estado_texto' => 'Pagado',
                'color'        => 'bg-info',
            ]);
        }

        // ✅ PDF boleta
        $detalleInventario = $detalleGuardar;
        $totalInventario   = $this->totalInventario;
        $totalHabitacion   = $this->totalHoras;
        $fechaPago         = now('America/La_Paz')->format('d-m-Y H:i');
        $alquiler          = $this->alquiler;

        $pdf = Pdf::loadView('pdf.boleta', compact(
            'alquiler',
            'detalleInventario',
            'totalInventario',
            'totalHabitacion',
            'fechaPago'
        ));

        $correoDestino = env('MAIL_RECEIVER', 'joseaguilar987654321@gmail.com');
        Mail::to($correoDestino)->send(new BoletaAlquiler($alquiler, $pdf->output()));
    });

    session()->flash('message', 'Pago registrado correctamente (Bs ' . $this->totalGeneral . ').');

    return response()->streamDownload(
        fn() => print($pdf->output()),
        "boleta_{$this->alquiler->id}.pdf"
    );
}




    private function recalcularTotales()
    {
        $this->totalInventario = collect($this->selectedInventarios)
            ->sum(fn($item) => ($item['precio'] ?? 0) * ($item['cantidad'] ?? 1));

        $habitacion = $this->alquiler->habitacion;

        if ($this->tarifaSeleccionada === 'NOCTURNA') {
            $this->totalHoras   = $habitacion->tarifa_opcion1 ?? 0;
            $this->totalGeneral = $this->totalHoras + $this->totalInventario;
            return;
        }

        $salida  = Carbon::parse($this->horaSalida, 'America/La_Paz');
        $entrada = Carbon::parse($this->alquiler->entrada, 'America/La_Paz');

        $minTot = $entrada->diffInMinutes($salida);
        $precioHoras = $habitacion?->preciohora ?? 0;

        if ($minTot > 75) {
            $precioHoras += (intdiv($minTot - 75, 60) + 1)
                * ($habitacion->precio_extra ?? 0);
        }

        $this->totalHoras   = $precioHoras;
        $this->totalGeneral = $this->totalHoras + $this->totalInventario;
    }
   private function ajustarConsumoConFreezer(array $viejoQty, array $nuevoQty, array &$origenDelta): void
{
    // 🔒 Bloquear habitación para actualizar freezer con seguridad
    $habitacion = $this->alquiler->habitacion
        ? \App\Models\Habitacion::lockForUpdate()->find($this->alquiler->habitacion->id)
        : null;

    $freezer = (array)($habitacion?->freezer_stock ?? []);

    $origenDelta = []; // [id => ['freezer'=>x,'inventario'=>y]]

    $ids = array_unique(array_merge(array_keys($viejoQty), array_keys($nuevoQty)));

    foreach ($ids as $id) {
        $id = (int)$id;

        $old   = (int)($viejoQty[$id] ?? 0);
        $new   = (int)($nuevoQty[$id] ?? 0);
        $delta = $new - $old;

        if ($delta === 0) continue;

        // 🔒 Lock inventario
        $inv = Inventario::lockForUpdate()->find($id);
        if (!$inv) continue;

        // ✅ AUMENTA consumo => DESCUENTA SIEMPRE DEL INVENTARIO
        if ($delta > 0) {

            // 1) SIEMPRE INVENTARIO
            if ((int)$inv->stock < $delta) {
                throw new \Exception("Stock insuficiente en inventario de {$inv->articulo}. Disponible: {$inv->stock}, requerido: {$delta}");
            }
            $inv->decrement('stock', $delta);

            $origenDelta[$id] = [
                'inventario' => $delta,
                'freezer'    => 0,
            ];

            // 2) SI freezer marcado, TAMBIÉN descuenta del freezer (DOBLE)
            if ($this->usarFreezer && $habitacion) {
                $freezerQty = (int)($freezer[$id] ?? 0);
                $take = min($delta, $freezerQty);

                if ($take > 0) {
                    $freezer[$id] = $freezerQty - $take;
                    $origenDelta[$id]['freezer'] = $take;
                }
            }
        }

        // ✅ DISMINUYE consumo => devolución SOLO a inventario (no tocamos freezer)
        if ($delta < 0) {
            $devolver = abs($delta);
            $inv->increment('stock', $devolver);

            // No registramos origen en devoluciones
            unset($origenDelta[$id]);
        }
    }

    // ✅ Guardar freezer actualizado
    if ($this->usarFreezer && $habitacion) {
        foreach ($freezer as $k => $v) {
            if ((int)$v <= 0) unset($freezer[$k]);
        }
        $habitacion->update(['freezer_stock' => $freezer]);
    }
}


}
