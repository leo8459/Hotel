<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Habitacion;
use App\Models\Alquiler;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReporteTurno;
use App\Models\Inventario;
use Carbon\Carbon;
use App\Models\User;
use App\Mail\BoletaAlquiler;
use Illuminate\Support\Facades\DB; // ✅

class CrearAlquiler extends Component
{
    public $habitaciones = [];
    public $fechaInicio;
    public $fechaFin;

    public function mount(): void
    {
        $this->habitaciones = Habitacion::orderBy('habitacion')->get();
    }

    public function alquilar(int $id): void
    {
        $hab = Habitacion::findOrFail($id);

        // 🔒 Bloquear si está en uso, limpieza, mantenimiento o pagado
        if (in_array($hab->estado_texto, ['En uso', 'En limpieza', 'Mantenimiento', 'Pagado'])) {
            $this->dispatch('toast', type: 'error', message: 'La habitación no está disponible para alquilar.');
            return;
        }

        $this->dispatch('abrir-modal-alquiler', id: $id);
    }

    /** ================================
     *  ✅ Cancelar alquiler (EN USO)
     *  - Restaura stock
     *  - Limpia datos del alquiler (null donde aplica) y estado = 'disponible'
     *  - Libera habitación => 'Disponible'
     * ================================= */
    public function cancelarAlquiler(int $alquilerId): void
    {
        try {
            DB::transaction(function () use ($alquilerId) {
                $alquiler = Alquiler::lockForUpdate()->findOrFail($alquilerId);

                // Opcional: aseguramos que sea un alquiler activo
                if ($alquiler->estado !== 'alquilado') {
                    abort(400, 'El alquiler no está activo.');
                }

                // Restaurar stock de consumos si existían
                $detalle = json_decode($alquiler->inventario_detalle, true) ?? [];
                foreach ($detalle as $invId => $item) {
                    $inv = Inventario::find($invId);
                    if ($inv) {
                        $inv->increment('stock', $item['cantidad'] ?? 0);
                    }
                }

                // Limpiar datos del alquiler (null donde la migración lo permite) + estado disponible
                $alquiler->update([
                    'tipoingreso'        => null,
                    'tipopago'           => null,
                    'aireacondicionado'  => null,
                    'entrada'            => null,
                    'salida'             => null,
                    'horas'              => null,
                    'total'              => null,
                    'inventario_detalle' => null,
                    'tarifa_seleccionada'=> null,
                    'aire_inicio'        => null,
                    'aire_fin'           => null,
                    'inventario_id'      => null,
                    'usuario_id'         => null,
                    'estado'             => 'disponible',
                ]);

                // Liberar habitación
                $hab = Habitacion::find($alquiler->habitacion_id);
                if ($hab) {
                    $hab->update([
                        'estado'       => 1, // asumiendo 1 = Disponible
                        'estado_texto' => 'Disponible',
                        'color'        => 'bg-success text-white',
                    ]);
                }
            });

            // Refrescar listado
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

        // ENVIAR CORREO
        Mail::to('hector.fernandez.z@gmail.com')->send(new ReporteTurno($pdfContent, $nombreArchivo));

        // DEVOLVER DESCARGA TAMBIÉN
        return response()->streamDownload(fn() => print($pdfContent), $nombreArchivo);
    }

    public function render()
    {
        return view('livewire.crearalquiler')
            ->extends('adminlte::page')
            ->section('content');
    }
}
