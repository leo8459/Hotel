<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Habitacion;
use App\Models\Alquiler;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReporteTurno;
use App\Models\Inventario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CrearAlquiler extends Component
{
    public $habitaciones = [];
    public $fechaInicio;
    public $fechaFin;

    // ✅ Mapa: habitacion_id => alquiler activo
    // public $alquileresActivos = [];

    public function mount(): void
    {
        $this->habitaciones = Habitacion::orderBy('habitacion')->get();
    }

    public function alquilar(int $id): void
    {
        $hab = Habitacion::findOrFail($id);

        if (in_array($hab->estado_texto, ['En uso', 'En limpieza', 'Mantenimiento', 'Pagado'])) {
            $this->dispatch('toast', type: 'error', message: 'La habitación no está disponible para alquilar.');
            return;
        }

        $this->dispatch('abrir-modal-alquiler', id: $id);
    }

    // ✅ Temporizador HH:MM:SS (tiempo transcurrido desde entrada)
    public function getTiempoTranscurrido($entrada): string
    {
        if (!$entrada) return '00:00:00';

        $inicio = Carbon::parse($entrada, 'America/La_Paz');
        $ahora  = Carbon::now('America/La_Paz');

        if ($ahora->lessThan($inicio)) {
            return '00:00:00';
        }

        $segundos = $inicio->diffInSeconds($ahora);

        $h = intdiv($segundos, 3600);
        $m = intdiv($segundos % 3600, 60);
        $s = $segundos % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    // ✅ Total estimado hasta el momento (habitacion + inventario)
    public function getTotalActual($alquiler, $habitacion): float
    {
        if (!$alquiler || !$habitacion || !$alquiler->entrada) {
            return 0.0;
        }

        $totalInventario = 0.0;
        $detalles = json_decode($alquiler->inventario_detalle, true) ?? [];
        foreach ($detalles as $key => $item) {
            if (!is_array($item)) {
                continue;
            }

            $id = $item['id'] ?? (is_numeric($key) ? (int)$key : null);
            if (!$id) {
                continue;
            }

            $cantidad = (int)($item['cantidad'] ?? 1);
            if ($cantidad < 1) {
                $cantidad = 1;
            }

            $inv = Inventario::find($id);
            if ($inv) {
                $totalInventario += ((float)$inv->precio) * $cantidad;
            }
        }

        if (($alquiler->tarifa_seleccionada ?? null) === 'NOCTURNA') {
            $totalHoras = (float)($habitacion->tarifa_opcion1 ?? 0);
            return $totalHoras + $totalInventario;
        }

        $entrada = Carbon::parse($alquiler->entrada, 'America/La_Paz');
        $ahora = Carbon::now('America/La_Paz');

        if ($ahora->lessThan($entrada)) {
            return $totalInventario;
        }

        $minTot = $entrada->diffInMinutes($ahora);
        $precioHoras = (float)($habitacion->preciohora ?? 0);

        if ($minTot > 75) {
            $precioExtra = (float)($habitacion->precio_extra ?? 0);
            $precioHoras += (intdiv($minTot - 75, 60) + 1) * $precioExtra;
        }

        return $precioHoras + $totalInventario;
    }

    /** ✅ Cancelar alquiler (EN USO) y restaurar stock */
    public function cancelarAlquiler(int $alquilerId): void
    {
        try {
            DB::transaction(function () use ($alquilerId) {
                $alquiler = Alquiler::lockForUpdate()->findOrFail($alquilerId);

                if ($alquiler->estado !== 'alquilado') {
                    abort(400, 'El alquiler no está activo.');
                }

                // Restaurar stock
                $detalle = json_decode($alquiler->inventario_detalle, true) ?? [];
                foreach ($detalle as $invId => $item) {
                    $inv = Inventario::find($invId);
                    if ($inv) {
                        $inv->increment('stock', $item['cantidad'] ?? 0);
                    }
                }

                // Limpiar alquiler
                $alquiler->update([
                    'tipoingreso'         => null,
                    'tipopago'            => null,
                    'aireacondicionado'   => null,
                    'entrada'             => null,
                    'salida'              => null,
                    'horas'               => null,
                    'total'               => null,
                    'inventario_detalle'  => null,
                    'tarifa_seleccionada' => null,
                    'aire_inicio'         => null,
                    'aire_fin'            => null,
                    'inventario_id'       => null,
                    'usuario_id'          => null,
                    'estado'              => 'disponible',
                ]);

                // Liberar habitación
                $hab = Habitacion::find($alquiler->habitacion_id);
                if ($hab) {
                    $hab->update([
                        'estado'       => 1,
                        'estado_texto' => 'Disponible',
                        'color'        => 'bg-success text-white',
                    ]);
                }
            });

            $this->habitaciones = Habitacion::orderBy('habitacion')->get();
            session()->flash('message', 'Alquiler cancelado y habitación liberada.');
        } catch (\Throwable $e) {
            session()->flash('error', 'No se pudo cancelar el alquiler: ' . $e->getMessage());
        }
    }

    public function iniciarTrabajo()
    {
        $usuario = auth()->user();
        $usuario->update([
            'hora_entrada_trabajo' => Carbon::now('America/La_Paz')
        ]);

        session()->flash('message', 'Has iniciado tu turno a las ' . Carbon::now('America/La_Paz')->format('H:i:s'));
    }

    public function finalizarTrabajo()
    {
        $usuario = auth()->user();
        $usuario->update([
            'hora_salida_trabajo' => Carbon::now('America/La_Paz')
        ]);

        session()->flash('message', 'Has finalizado tu turno a las ' . Carbon::now('America/La_Paz')->format('H:i:s'));

        return $this->generarReporte();
    }

    public function generarReporte()
    {
        $usuario = auth()->user();

        if (!$usuario->hora_entrada_trabajo || !$usuario->hora_salida_trabajo) {
            session()->flash('error', 'No se puede generar el reporte sin un horario válido.');
            return;
        }

        $entradaTrabajo = Carbon::parse($usuario->hora_entrada_trabajo);
        $salidaTrabajo  = Carbon::parse($usuario->hora_salida_trabajo);

        $alquileres = Alquiler::where('estado', 'pagado')
            ->whereBetween('updated_at', [$entradaTrabajo, $salidaTrabajo])
            ->where('usuario_id', $usuario->id)
            ->get();

        $totalGenerado = $alquileres->sum('total');

        $pdf = Pdf::loadView('pdf.reporte_turno', compact('usuario', 'alquileres', 'totalGenerado'));

        $nombreArchivo = 'reporte_turno_' . $usuario->id . '.pdf';
        $pdfContent = $pdf->output();

        Mail::to('hector.fernandez.z@gmail.com')->send(new ReporteTurno($pdfContent, $nombreArchivo));

        return response()->streamDownload(fn() => print($pdfContent), $nombreArchivo);
    }

  public function render()
{
    // Recargar habitaciones (para que el poll refresque estados)
    $this->habitaciones = Habitacion::orderBy('habitacion')->get();

    // ✅ Colección keyBy(habitacion_id) (NO toArray)
    $activos = Alquiler::where('estado', 'alquilado')
        ->orderBy('entrada', 'desc')
        ->get()
        ->keyBy('habitacion_id');

    return view('livewire.crearalquiler', [
        'habitaciones'      => $this->habitaciones,
        'alquileresActivos' => $activos,
    ])
        ->extends('adminlte::page')
        ->section('content');
}

}
