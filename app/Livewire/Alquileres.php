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
        'entrada', // Asegúrate de inicializar 'entrada'
        'horas', 
        'habitacion_id'
    ]);

    $this->entrada = now()->format('Y-m-d\TH:i'); // Hora actual
    $this->dispatch('show-create-modal'); // Mostrar el modal
}


    public function closeCreateModal()
    {
        $this->dispatch('close-modal');
    }

    public function store()
    {
        $this->validate([
            'tipoingreso' => 'required|string|in:A PIE,AUTOMOVIL,MOTO,OTRO',
            'tipopago' => 'required|string|in:EFECTIVO,QR,TARJETA',
            'aireacondicionado' => 'required|boolean',
            'entrada' => 'required|date',
            'habitacion_id' => 'required|exists:habitaciones,id',
            'total' => 'required|numeric|min:0',
        ]);
    
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
        $this->selectedAlquiler = Alquiler::findOrFail($id);
        $this->horaSalida = now()->format('Y-m-d\TH:i'); // Hora actual en formato datetime-local
        $this->dispatch('show-pay-modal');
    }
    
    public function pay()
    {
        $this->validate([
            'horaSalida' => 'required|date|after_or_equal:selectedAlquiler.entrada',
        ]);
    
        // Calcular la diferencia en tiempo
        $entrada = strtotime($this->selectedAlquiler->entrada);
        $salida = strtotime($this->horaSalida);
        $diferenciaSegundos = $salida - $entrada;
    
        // Convertir segundos a horas y minutos
        $horas = floor($diferenciaSegundos / 3600);
        $minutos = floor(($diferenciaSegundos % 3600) / 60);
    
        // Actualizar el alquiler con la nueva hora de salida, horas calculadas y estado
        $this->selectedAlquiler->update([
            'salida' => $this->horaSalida,
            'horas' => $horas, // Guardar las horas totales
            'estado' => 'pagado', // Cambiar el estado a pagado
        ]);
    
        session()->flash('message', "Habitación pagada. Tiempo transcurrido: $horas horas y $minutos minutos.");
    
        $this->dispatch('close-modal');
        $this->reset(['selectedAlquiler', 'horaSalida']);
    }
    
    

    
}
