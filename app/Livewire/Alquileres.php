<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Alquiler;
use App\Models\Habitacion;
use App\Models\Inventario;

class Alquileres extends Component
{
    use WithPagination;

    // Propiedades públicas
    public $searchTerm = '';
    public $perPage = 10;
    public $tipoingreso, $tipopago, $aireacondicionado = false, $total = 0, $entrada, $salida, $horas, $habitacion_id, $inventario_id;
    public $selectedAlquilerId = null; // Para manejar edición
    public $selectedAlquiler; // Alquiler seleccionado para el pago
    public $horaSalida; // Hora de salida para actualizar
    public $isPaying = false; // Define si el modal es para pagar
    public $inventarios = []; // Lista de inventarios disponibles
    public $selectedInventarios = []; // Para manejar inventarios seleccionados y cantidades

    public $showCreateModal = false;

    // Renderiza la vista y carga datos necesarios
    public function render()
    {
        $alquileres = Alquiler::with(['habitacion', 'inventario'])
            ->where('tipoingreso', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        $habitaciones = Habitacion::whereDoesntHave('alquileres', function ($query) {
            $query->where('estado', 'alquilado');
        })->get();

        $this->inventarios = Inventario::where('stock', '>', 0)->get();

        return view('livewire.alquileres', [
            'alquileres' => $alquileres,
            'habitaciones' => $habitaciones,
            'inventarios' => $this->inventarios,
        ]);
    }

    // Abrir el modal de creación
    public function openCreateModal()
    {
        $this->reset([
            'tipoingreso', 
            'tipopago', 
            'aireacondicionado', 
            'total', 
            'entrada', 
            'habitacion_id',
            'selectedInventarios',
        ]);
        $this->entrada = now()->format('Y-m-d\TH:i'); // Hora actual
        $this->isPaying = false; // Este modal no es para pagar
        $this->dispatch('show-create-modal'); // Mostrar el modal de creación
    }

    // Cerrar el modal de creación
    public function closeCreateModal()
    {
        $this->dispatch('close-modal');
    }

    // Método para almacenar el alquiler
    public function toggleInventario($id, $index)
    {
        // Si el inventario ya está seleccionado, lo quitamos
        if (isset($this->selectedInventarios[$id])) {
            unset($this->selectedInventarios[$id]);
        } else {
            // Si no está seleccionado, lo añadimos
            $this->selectedInventarios[$id] = [
                'id' => $id,
                'cantidad' => 1, // Valor inicial
            ];
        }
    }

    public function store()
    {
        // Filtrar solo inventarios con cantidad válida
        $this->selectedInventarios = array_filter($this->selectedInventarios, function ($item) {
            return isset($item['cantidad']) && $item['cantidad'] > 0;
        });

        $this->validate([
            'tipoingreso' => 'required|string|max:50',
            'tipopago' => 'nullable|string|max:50',
            'aireacondicionado' => 'boolean',
            'entrada' => 'required|date',
            'habitacion_id' => 'required|exists:habitaciones,id',
            'selectedInventarios' => 'required|array|min:1',
            'selectedInventarios.*.id' => 'required|exists:inventarios,id',
            'selectedInventarios.*.cantidad' => 'required|integer|min:1',
        ]);

        // Validar stock y descontar
        foreach ($this->selectedInventarios as $item) {
            $inventario = Inventario::find($item['id']);
            if ($inventario->stock < $item['cantidad']) {
                session()->flash('error', "El inventario '{$inventario->articulo}' no tiene suficiente stock.");
                return;
            }
        }

        $alquiler = Alquiler::create([
            'tipoingreso' => $this->tipoingreso,
            'tipopago' => $this->tipopago,
            'aireacondicionado' => $this->aireacondicionado,
            'entrada' => $this->entrada,
            'salida' => null,
            'horas' => 0,
            'habitacion_id' => $this->habitacion_id,
            'inventario_detalle' => json_encode($this->selectedInventarios),
            'total' => $this->total,
            'estado' => 'alquilado',
        ]);

        foreach ($this->selectedInventarios as $item) {
            $inventario = Inventario::find($item['id']);
            $inventario->decrement('stock', $item['cantidad']);
        }

        session()->flash('message', 'Alquiler creado exitosamente.');
        $this->reset(['tipoingreso', 'tipopago', 'aireacondicionado', 'entrada', 'habitacion_id', 'selectedInventarios', 'total']);
        $this->dispatch('close-modal');
        $this->resetPage();
    }
    
    

    // Abrir el modal de pago
    public function openPayModal($id)
    {
        $this->selectedAlquiler = Alquiler::find($id);

        if (!$this->selectedAlquiler) {
            session()->flash('error', 'El alquiler no existe.');
            return;
        }

        $this->horaSalida = now()->format('Y-m-d\TH:i'); // Inicializar la hora de salida
        $this->isPaying = true; // Este modal es para pagar
        $this->dispatch('show-pay-modal'); // Mostrar el modal de pago
    }

    // Método para realizar el pago
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
            'tipopago' => $this->tipopago,
        ]);

        session()->flash('message', "Habitación pagada. Tiempo transcurrido: $horas horas y $minutos minutos.");

        $this->dispatch('close-modal');
        $this->reset(['selectedAlquiler', 'horaSalida', 'tipopago']);
    }
}
