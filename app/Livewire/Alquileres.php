<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Alquiler;
use App\Models\Habitacion;
use App\Models\Inventario;
use Carbon\Carbon;

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
    public $tarifaSeleccionada; // Para almacenar la tarifa seleccionada al momento del pago

    public $showCreateModal = false;

    // Renderiza la vista y carga datos necesarios
    public function render()
{
    // Obtener los alquileres ordenados por fecha de entrada, del más reciente al más antiguo
    $alquileres = Alquiler::with(['habitacion', 'inventario'])
        ->where('tipoingreso', 'like', '%' . $this->searchTerm . '%')
        ->orderBy('entrada', 'desc') // Ordenar por fecha de entrada (descendente)
        ->paginate($this->perPage);

    // Habitaciones disponibles
    $habitaciones = Habitacion::whereDoesntHave('alquileres', function ($query) {
        $query->where('estado', 'alquilado');
    })->get();

    // Inventarios disponibles
    $this->inventarios = Inventario::where('stock', '>', 0)->get();

    // Renderizar la vista con los datos
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
            'inventario_detalle' => json_encode($this->selectedInventarios), // Guardar como JSON
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
        $this->resetPage(); // Resetear la página para recargar datos

        // Refrescar la página
        return redirect()->to(request()->header('Referer')); 
    }

    
    

    // Abrir el modal de pago
    public function openPayModal($id)
    {
        $this->selectedAlquiler = Alquiler::find($id);
    
        if (!$this->selectedAlquiler) {
            session()->flash('error', 'El alquiler no existe.');
            return;
        }
    
        // Establecer la hora de salida en la zona horaria de La Paz
        $this->horaSalida = Carbon::now('America/La_Paz')->format('Y-m-d\TH:i'); // Formato compatible con datetime-local
        $this->tarifaSeleccionada = null; // Inicializar tarifa seleccionada
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
    
        // Convertir las fechas a Carbon en la zona horaria de La Paz
        $entrada = Carbon::parse($this->selectedAlquiler->entrada, 'America/La_Paz');
        $salida = Carbon::parse($this->horaSalida, 'America/La_Paz');
    
        // Obtener la habitación
        $habitacion = $this->selectedAlquiler->habitacion;
    
        if (!$habitacion) {
            session()->flash('error', 'No se encontró la habitación asociada.');
            return;
        }
    
        // Obtener el precio de la tarifa seleccionada o usar `preciohora` si no hay tarifa seleccionada
        $precioTarifa = $this->tarifaSeleccionada 
            ? $habitacion->{$this->tarifaSeleccionada} 
            : $habitacion->preciohora;
    
        $precioTotal = 0;
    
        // Calcular el tiempo transcurrido
        $diferenciaHoras = $entrada->diffInHours($salida);
        $diferenciaMinutos = $entrada->diff($salida)->i;
    
        if ($diferenciaHoras == 0 && $diferenciaMinutos > 0) {
            // Si solo pasó un minuto pero no una hora completa, aplicar el precio por hora
            $precioTotal += $habitacion->preciohora;
        } else {
            // Si pasó una o más horas
            $precioTotal += $precioTarifa;
            if ($diferenciaHoras > 1 || $diferenciaMinutos > 0) {
                $precioTotal += ($diferenciaHoras - 1) * $habitacion->preciohora;
            }
        }
    
        // Calcular el precio del inventario seleccionado
        $precioInventario = 0;
        if (is_array($this->selectedInventarios) && !empty($this->selectedInventarios)) {
            foreach ($this->selectedInventarios as $item) {
                $inventario = Inventario::find($item['id']);
                if ($inventario) {
                    $precioInventario += $inventario->precio * $item['cantidad'];
                    if ($inventario->stock >= $item['cantidad']) {
                        $inventario->decrement('stock', $item['cantidad']);
                    } else {
                        session()->flash('error', "El inventario '{$inventario->articulo}' no tiene suficiente stock.");
                        return;
                    }
                }
            }
        }
    
        // Sumar el costo del inventario
        $precioTotal += $precioInventario;
    
        // Sumar el costo del aire acondicionado si está activado
        if ($this->aireacondicionado) {
            $precioTotal += 40; // Costo adicional por aire acondicionado
        }
    
        // Actualizar el alquiler con los datos calculados
        $this->selectedAlquiler->update([
            'salida' => $this->horaSalida,
            'horas' => $diferenciaHoras + ($diferenciaMinutos > 0 ? 1 : 0), // Ajustar las horas
            'estado' => 'pagado',
            'tipopago' => $this->tipopago,
            'total' => $precioTotal,
            'tarifa_seleccionada' => $this->tarifaSeleccionada,
            'inventario_detalle' => json_encode($this->selectedInventarios),
            'aireacondicionado' => $this->aireacondicionado,
        ]);
    
        // Mensaje de confirmación
        session()->flash('message', "Habitación pagada. Total a cobrar: $precioTotal (Tarifa: $precioTarifa, Inventario: $precioInventario, Aire acondicionado: " . ($this->aireacondicionado ? '40' : '0') . ").");
    
        $this->dispatch('close-modal');
        $this->reset(['selectedAlquiler', 'horaSalida', 'tipopago', 'tarifaSeleccionada', 'selectedInventarios', 'aireacondicionado']);
        $this->resetPage();
    }
    

    

    

    

    
    
    
}
