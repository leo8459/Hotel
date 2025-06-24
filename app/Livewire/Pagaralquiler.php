<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Alquiler;
use App\Models\Inventario;
use App\Models\Habitacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\BoletaAlquiler;

class Pagaralquiler extends Component
{
    // ---------- Propiedades públicas ----------
    public $alquilerId;           // id que llega por URL
    public $alquiler;             // instancia Alquiler
    public $horaSalida;           // datetime-local
    public $tipopago = '';        // EFECTIVO | QR | TARJETA
    public $selectedInventarioId; // para el <select>
    public $selectedInventarios = []; // [id => ['id'=>, 'articulo'=>, 'precio'=>, 'cantidad'=>, 'stock'=>]]

    // Totales
    public $totalHoras = 0;
    public $totalInventario = 0;
    public $totalGeneral = 0;

    // ---------- Ciclo de vida ----------
    public function mount(Alquiler $alquiler)
    {
        // El modelo llega inyectado por la ruta
        $this->alquiler      = $alquiler;
        $this->alquilerId    = $alquiler->id;
        $this->horaSalida    = Carbon::now('America/La_Paz')->format('Y-m-d\TH:i');

        // Cargar inventarios consumidos previos (si los hay)
        $this->selectedInventarios = collect(
            json_decode($alquiler->inventario_detalle, true) ?? []
        )->mapWithKeys(function ($item, $key) {
            // la clave $key puede ya ser el ID
            $id  = $item['id'] ?? $key;
            $inv = Inventario::find($id);
            return [
                $id => [
                    'id'       => $id,
                    'articulo' => $inv?->articulo ?? ($item['articulo'] ?? 'Sin nombre'),
                    'precio'   => $inv?->precio   ?? ($item['precio']   ?? 0),
                    'cantidad' => $item['cantidad'] ?? 1,
                    'stock'    => $inv?->stock    ?? 0,
                ],
            ];
        })->toArray();

        $this->recalcularTotales();
    }

    // ---------- Render ----------
    public function render()
    {
        return view('livewire.pagaralquiler', [
            'inventariosDisponibles' => Inventario::where('stock', '>', 0)->get(),
            'habitacion'             => $this->alquiler->habitacion,
        ])
            ->extends('adminlte::page')
            ->section('content');
    }

    // ---------- Acciones UI ----------
    public function addInventario()
    {
        if (!$this->selectedInventarioId) return;

        $inv = Inventario::find($this->selectedInventarioId);
        if (!$inv) return;

        if (!isset($this->selectedInventarios[$inv->id])) {
            $this->selectedInventarios[$inv->id] = [
                'id'       => $inv->id,
                'articulo' => $inv->articulo,
                'precio'   => $inv->precio,
                'cantidad' => 1,
                'stock'    => $inv->stock,
            ];
        }

        $this->selectedInventarioId = null;
        $this->recalcularTotales();
    }

    public function removeInventario($id)
    {
        unset($this->selectedInventarios[$id]);
        $this->recalcularTotales();
    }

    public function updatedSelectedInventarios()
    {
        $this->recalcularTotales();
    }

    public function updatedHoraSalida()
    {
        $this->recalcularTotales();
    }

    public function updatedTipopago()
    {
        // solo para refrescar – no recalcula nada
    }

    // ---------- Pagar ----------
  public function pay()
{
    $this->validate([
        'tipopago'   => 'required|in:EFECTIVO,QR,TARJETA',
        'horaSalida' => 'required|date',
    ]);

    $pdf = null; // Variable para guardar PDF generado

    DB::transaction(function () use (&$pdf) {
        $salida     = Carbon::parse($this->horaSalida, 'America/La_Paz');
        $entrada    = Carbon::parse($this->alquiler->entrada, 'America/La_Paz');
        $habitacion = $this->alquiler->habitacion;

        $this->recalcularTotales();

        // Descontar stock
        foreach ($this->selectedInventarios as $item) {
            $inv = Inventario::find($item['id']);
            if ($inv) {
                $inv->stock = max(0, $inv->stock - $item['cantidad']);
                $inv->save();
            }
        }

        // Actualizar alquiler
        $this->alquiler->update([
            'salida'             => $salida,
            'horas'              => ceil($entrada->diffInMinutes($salida) / 60),
            'tipopago'           => $this->tipopago,
            'total'              => $this->totalGeneral,
            'estado'             => 'pagado',
            'inventario_detalle' => json_encode($this->selectedInventarios),
            'usuario_id'         => auth()->id(),
        ]);

        // Actualizar habitación
        $habitacion->update([
            'estado'       => 1,
            'estado_texto' => 'Pagado',
            'color'        => 'bg-info',
        ]);

        // Generar PDF
        $detalleInventario = $this->selectedInventarios;
        $totalInventario   = $this->totalInventario;
        $totalHabitacion   = $this->totalHoras;
        $fechaPago         = now('America/La_Paz')->format('d-m-Y H:i');
        $alquiler          = $this->alquiler;

        $pdf = Pdf::loadView('pdf.boleta', compact(
            'alquiler', 'detalleInventario', 'totalInventario', 'totalHabitacion', 'fechaPago'
        ));

        // Enviar por correo
        $correoDestino = env('MAIL_RECEIVER', 'joseaguilar987654321@gmail.com');
        Mail::to($correoDestino)->send(new BoletaAlquiler($alquiler, $pdf->output()));
    });

    session()->flash(
        'message',
        'Pago registrado correctamente (Bs ' . $this->totalGeneral . '). Se envió al correo y se descargó la boleta.'
    );

    // Descargar el PDF después del pago
    return response()->streamDownload(
        fn() => print($pdf->output()),
        "boleta_{$this->alquiler->id}.pdf"
    );
        return redirect()->route('crear-alquiler');

}





    // ---------- Helpers ----------
    private function recalcularTotales()
    {
        // Vuelve a calcular cada vez que algo cambia
        $this->totalInventario = collect($this->selectedInventarios)
            ->sum(fn ($item) => ($item['precio'] ?? 0) * ($item['cantidad'] ?? 1));

        // Horas + aire
        $salida  = Carbon::parse($this->horaSalida, 'America/La_Paz');
        $entrada = Carbon::parse($this->alquiler->entrada, 'America/La_Paz');
        $habitacion = $this->alquiler->habitacion;

        $minTot = $entrada->diffInMinutes($salida);
        $precioHoras = $habitacion?->preciohora ?? 0;
        if ($minTot > 75) {
            $precioHoras += (intdiv($minTot - 75, 60) + 1) * ($habitacion->precio_extra ?? 0);
        }

        $costoAire = $this->alquiler->aireacondicionado
            ? $this->calcularCostoAire($salida)
            : 0;

        $this->totalHoras   = $precioHoras + $costoAire;
        $this->totalGeneral = $this->totalHoras + $this->totalInventario;
    }

    private function calcularCostoAire(Carbon $fin)
    {
        if (!$this->alquiler->aire_inicio) return 0;

        $inicio = Carbon::parse($this->alquiler->aire_inicio, 'America/La_Paz');
        if ($fin->lessThanOrEqualTo($inicio)) return 0;

        $horas = $inicio->diffInHours($fin);
        return $horas * 10; // Bs 10 por hora de aire
    }
}
