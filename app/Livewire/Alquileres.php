<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Alquiler;
use App\Models\Habitacion;

class Alquileres extends Component
{
    use WithPagination;

    public $searchTerm = '';
    public $perPage = 10;
    public $tipoingreso, $tipopago, $aireacondicionado = false, $total = 0, $entrada, $salida, $horas, $habitacion_id;
    public $selectedAlquilerId = null; // Para manejar edición
    public $selectedAlquiler; // Alquiler seleccionado para el pago
    public $horaSalida; // Hora de salida para actualizar
    public $isPaying = false; // Define si el modal es para pagar

    public $showCreateModal = false;

    public function render()
    {
        $alquileres = Alquiler::with('habitacion')
            ->where('tipoingreso', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    
        // Filtrar habitaciones que no están en estado "alquilado"
        $habitaciones = Habitacion::whereDoesntHave('alquileres', function ($query) {
            $query->where('estado', 'alquilado');
        })->get();
    
        return view('livewire.alquileres', [
            'alquileres' => $alquileres,
            'habitaciones' => $habitaciones,
        ]);
    }
    
    

    public function openCreateModal()
    {
        $this->reset([
            'tipoingreso', 
            'tipopago', 
            'aireacondicionado', 
            'total', 
            'entrada', 
            'habitacion_id',
            'horaSalida',
        ]);
        $this->entrada = now()->format('Y-m-d\TH:i'); // Hora actual
        $this->isPaying = false; // Este modal no es para pagar
        $this->dispatch('show-create-modal'); // Mostrar el modal de creación
    }


    public function closeCreateModal()
    {
        $this->dispatch('close-modal');
    }

    public function store()
    {
        
    
        Alquiler::create([
            'tipoingreso' => $this->tipoingreso,
            'tipopago' => $this->tipopago,
            'aireacondicionado' => $this->aireacondicionado,
            'entrada' => $this->entrada,
            'salida' => null,
            'horas' => 0,
            'habitacion_id' => $this->habitacion_id,
            'total' => $this->total,
            'estado' => 'alquilado',
        ]);
    
        session()->flash('message', 'Alquiler creado exitosamente.');
    
        $this->closeCreateModal();
        $this->resetPage();
    }
    
    
    public function openPayModal($id)
    {
        $this->selectedAlquiler = Alquiler::find($id);
    
        if (!$this->selectedAlquiler) {
            session()->flash('error', 'El alquiler no existe.');
            return;
        }
    
        $this->horaSalida = now()->format('Y-m-d\TH:i'); // Inicializar la hora de salida con la hora actual
        $this->isPaying = true; // Este modal es para pagar
        $this->dispatch('show-pay-modal'); // Mostrar el modal de pago
    }
    
    public function pay()
    {
        $this->validate([
            'horaSalida' => 'required|date|after_or_equal:selectedAlquiler.entrada',
            'tipopago' => 'required|string|in:EFECTIVO,QR,TARJETA', // Validar el tipo de pago
        ]);
    
        $entrada = strtotime($this->selectedAlquiler->entrada);
        $salida = strtotime($this->horaSalida);
        $diferenciaSegundos = $salida - $entrada;
    
        $horas = floor($diferenciaSegundos / 3600);
        $minutos = floor(($diferenciaSegundos % 3600) / 60);
    
        $this->selectedAlquiler->update([
            'salida' => $this->horaSalida,
            'horas' => $horas,
            'estado' => 'pagado',
            'tipopago' => $this->tipopago, // Guardar el tipo de pago
        ]);
    
        session()->flash('message', "Habitación pagada. Tiempo transcurrido: $horas horas y $minutos minutos.");
    
        $this->dispatch('close-modal');
        $this->reset(['selectedAlquiler', 'horaSalida', 'tipopago']);
    }
    
    
    

    
}
