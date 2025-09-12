<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Habitacion;
use App\Models\Alquiler;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AlquilerCrear extends Component
{
    /* ─── Campos visible en formulario ─── */
    public string  $tipoingreso = '';
    public bool    $aireacondicionado = false;
    public string  $entrada;          // datetime-local
    public ?int    $habitacion_id = null;

    public array $tiposIngreso = ['Auto', 'Peatón', 'Reservación', 'Moto'];

    /* ─── Ciclo de vida ─── */
    public function mount(?Habitacion $habitacion = null): void
    {
        $this->entrada = now()->format('Y-m-d\TH:i');
        if ($habitacion) {
            $this->habitacion_id = $habitacion->id;
        }
    }

    /* ─── Reglas ─── */
    protected function rules(): array
    {
        return [
            'tipoingreso'   => ['required', Rule::in($this->tiposIngreso)],
            'habitacion_id' => ['required', 'exists:habitaciones,id'],
            'entrada'       => ['required', 'date'],
            'aireacondicionado' => ['boolean'],
        ];
    }

    /* ─── Guardar ─── */
    public function guardar()
    {
        $this->validate();

        /* 1. Crear el registro de alquiler */
        Alquiler::create([
            'tipoingreso'       => $this->tipoingreso,
            'aireacondicionado' => $this->aireacondicionado,
            'entrada'           => $this->entrada,
            'estado'            => 'alquilado',
            'habitacion_id'     => $this->habitacion_id,
            'usuario_id'        => auth()->id(),
            // horas, salida y total quedan null; podrás completarlos al cerrar el alquiler
        ]);

        /* 2. Cambiar la habitación a “En uso” */
        $hab = Habitacion::find($this->habitacion_id);
        $hab->update([
            'estado'       => 0,
            'estado_texto' => 'En uso',
            'color'        => 'bg-danger text-white',
        ]);

        /* 3. Redirigir */
        session()->flash('estadoActualizado', 'Alquiler creado y habitación marcada en uso.');
        return redirect()->route('crear-alquiler');
    }

    public function render()
    {
        return view('livewire.alquiler-crear', [
            'habitacionesLibres' => Habitacion::orderBy('habitacion')->get(),
        ])->extends('adminlte::page')
          ->section('content');
    }
}
