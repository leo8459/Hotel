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
        $this->dispatch('abrir-modal-alquiler', id: $id);
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
    $salidaTrabajo = Carbon::parse($usuario->hora_salida_trabajo);

    $alquileres = Alquiler::where('estado', 'pagado')
        ->whereBetween('updated_at', [$entradaTrabajo, $salidaTrabajo])
        ->where('usuario_id', $usuario->id)
        ->get();

    $totalGenerado = $alquileres->sum('total');

    $pdf = Pdf::loadView('pdf.reporte_turno', compact('usuario', 'alquileres', 'totalGenerado'));

    $nombreArchivo = 'reporte_turno_' . $usuario->id . '.pdf';
    $pdfContent = $pdf->output();

    // ENVIAR CORREO
    Mail::to('hector.fernandez.z@gmail.com')
        ->send(new ReporteTurno($pdfContent, $nombreArchivo));

    // DEVOLVER DESCARGA TAMBIÉN
    return response()->streamDownload(
        fn() => print($pdfContent),
        $nombreArchivo
    );
}

    public function render()
    {
        return view('livewire.crearalquiler')
            ->extends('adminlte::page')
            ->section('content');
    }
}
