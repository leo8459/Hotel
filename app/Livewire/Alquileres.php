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
    public $selectedInventarioId; // ID del inventario seleccionado en el combo box

    // Renderiza la vista y carga datos necesarios
    public function render()
    {
        $alquileres = Alquiler::with(['habitacion', 'inventario'])
            ->where('tipoingreso', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('entrada', 'desc')
            ->paginate($this->perPage);
    
        foreach ($alquileres as $alquiler) {
            $alquiler->tiempo_transcurrido = $this->getTiempoTranscurrido($alquiler->entrada, $alquiler->estado);
        }
    
        $habitaciones = Habitacion::whereDoesntHave('alquileres', function ($query) {
            $query->where('estado', 'alquilado');
        })->get();
    
        $this->inventarios = Inventario::where('stock', '>', 0)->get();
    
        return view('livewire.alquileres', [
            'alquileres' => $alquileres,
            'habitaciones' => $habitaciones,
            'inventarios' => $this->inventarios,
            'esHorarioNocturno' => $this->esHorarioNocturno(),
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
    if (isset($this->selectedInventarios[$id])) {
        unset($this->selectedInventarios[$id]);
    } else {
        $inventario = $this->inventarios->firstWhere('id', $id);
        if ($inventario) {
            $this->selectedInventarios[$id] = [
                'id' => $id,
                'cantidad' => 1, // Cantidad inicial
                'stock' => $inventario->stock,
            ];
        }
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
    
        $this->horaSalida = Carbon::now('America/La_Paz')->format('Y-m-d\TH:i');
        $this->tarifaSeleccionada = null;
    
        // Cargar los inventarios previamente seleccionados
        $this->selectedInventarios = json_decode($this->selectedAlquiler->inventario_detalle, true) ?? [];
    
        // Cargar inventarios disponibles
        $this->inventarios = Inventario::where('stock', '>', 0)->get();
    
        // Sincronizar cantidades seleccionadas con el stock actual
        foreach ($this->selectedInventarios as $id => $item) {
            $inventario = $this->inventarios->firstWhere('id', $item['id']);
            if ($inventario) {
                $this->selectedInventarios[$id]['stock'] = $inventario->stock;
            } else {
                unset($this->selectedInventarios[$id]); // Eliminar si ya no está disponible
            }
        }
    
        $this->dispatch('show-pay-modal');
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
    
        $precioTotal = 0;
    
        // Calcular el tiempo transcurrido
        $diferenciaHoras = $entrada->diffInHours($salida);
        $diferenciaMinutos = $entrada->diff($salida)->i;
    
        if ($diferenciaHoras == 0 && $diferenciaMinutos > 0) {
            // Si solo pasó un minuto pero no una hora completa, aplicar el precio por hora
            $precioTotal += $habitacion->preciohora;
        } else {
            // Agregar el precio de la primera hora
            $precioTotal += $habitacion->preciohora;
    
            // Agregar el precio extra por las horas adicionales
            if ($diferenciaHoras > 1) {
                $precioTotal += ($diferenciaHoras - 1) * $habitacion->precio_extra;
            }
    
            // Si hay minutos adicionales, se cuenta como una hora adicional
            if ($diferenciaMinutos > 0) {
                $precioTotal += $habitacion->precio_extra;
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
        session()->flash('message', "Habitación pagada. Total a cobrar: $precioTotal (Primera hora: {$habitacion->preciohora}, Horas adicionales: {$habitacion->precio_extra}, Inventario: $precioInventario, Aire acondicionado: " . ($this->aireacondicionado ? '40' : '0') . ").");
    
        $this->dispatch('close-modal');
        $this->reset(['selectedAlquiler', 'horaSalida', 'tipopago', 'tarifaSeleccionada', 'selectedInventarios', 'aireacondicionado']);
        $this->resetPage();
    }
    
    public function getTiempoTranscurrido($entrada, $estado)
    {
        if ($estado === 'pagado') {
            return 'Tiempo finalizado'; // Mensaje estático si está pagado
        }
    
        $entrada = Carbon::parse($entrada);
        $ahora = Carbon::now();
    
        $diferenciaHoras = $entrada->diffInHours($ahora);
        $diferenciaMinutos = $entrada->diff($ahora)->i;
        $diferenciaSegundos = $entrada->diff($ahora)->s;
    
        return sprintf('%02d horas, %02d minutos, %02d segundos', $diferenciaHoras, $diferenciaMinutos, $diferenciaSegundos);
    }
    public function calcularTotal()
{
    $total = 0;

    // Calcular precio por tiempo de uso
    if ($this->selectedAlquiler && $this->horaSalida) {
        $entrada = Carbon::parse($this->selectedAlquiler->entrada);
        $salida = Carbon::parse($this->horaSalida);

        $diferenciaHoras = $entrada->diffInHours($salida);
        $diferenciaMinutos = $entrada->diff($salida)->i;

        $habitacion = $this->selectedAlquiler->habitacion;
        if ($habitacion) {
            if ($diferenciaHoras == 0 && $diferenciaMinutos > 0) {
                $total += $habitacion->preciohora;
            } else {
                $total += $habitacion->preciohora; // Primera hora
                if ($diferenciaHoras > 1) {
                    $total += ($diferenciaHoras - 1) * $habitacion->precio_extra;
                }
                if ($diferenciaMinutos > 0) {
                    $total += $habitacion->precio_extra; // Minutos adicionales cuentan como una hora
                }
            }
        }
    }

    // Calcular precio del inventario seleccionado
    foreach ($this->selectedInventarios as $item) {
        $inventario = Inventario::find($item['id']);
        if ($inventario) {
            $total += $inventario->precio * ($item['cantidad'] ?? 1);
        }
    }

    // Costo adicional por aire acondicionado
    if ($this->aireacondicionado) {
        $total += 40; // Valor fijo por aire acondicionado
    }

    $this->total = $total;
}

public function updated($propertyName)
{
    if (in_array($propertyName, ['horaSalida', 'aireacondicionado', 'selectedInventarios'])) {
        $this->calcularTotal();
    }
}
public function esHorarioNocturno()
{
    $horaActual = Carbon::now('America/La_Paz')->format('H');
    return $horaActual >= 21 || $horaActual < 7; // Entre 21:00 y 07:00
}


public function openEditModal($id)
{
    $alquiler = Alquiler::find($id);

    if (!$alquiler) {
        session()->flash('error', 'El alquiler no existe.');
        return;
    }

    $this->selectedAlquilerId = $id;
    $this->tipoingreso = $alquiler->tipoingreso;
    $this->entrada = $alquiler->entrada;
    $this->selectedInventarios = json_decode($alquiler->inventario_detalle, true) ?? [];
    $this->total = $alquiler->total;

    $this->inventarios = Inventario::where('stock', '>', 0)->get();

    $this->dispatch('show-edit-modal');
}
public function update()
{
    $this->validate([
        'tipoingreso' => 'required|string',
        'entrada' => 'required|date',
        'selectedInventarios.*.cantidad' => 'nullable|integer|min:1',
    ]);

    $alquiler = Alquiler::find($this->selectedAlquilerId);

    if (!$alquiler) {
        session()->flash('error', 'El alquiler no existe.');
        return;
    }

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

    $alquiler->update([
        'tipoingreso' => $this->tipoingreso,
        'entrada' => $this->entrada,
        'inventario_detalle' => json_encode($this->selectedInventarios),
        'total' => $this->calcularTotal(),
    ]);

    foreach ($this->selectedInventarios as $item) {
        $inventario = Inventario::find($item['id']);
        $inventario->decrement('stock', $item['cantidad']);
    }

    session()->flash('message', 'Alquiler actualizado exitosamente.');
    $this->dispatch('close-modal');
    $this->reset(['selectedAlquilerId', 'tipoingreso', 'entrada', 'selectedInventarios', 'total']);
    $this->resetPage();
}

public function addInventario()
{
    if (!$this->selectedInventarioId) {
        return;
    }

    $inventario = Inventario::find($this->selectedInventarioId);

    if (!$inventario) {
        session()->flash('error', 'Inventario no encontrado.');
        return;
    }

    // Verifica que el inventario no esté ya en la lista
    if (!isset($this->selectedInventarios[$inventario->id])) {
        $this->selectedInventarios[$inventario->id] = [
            'id' => $inventario->id,
            'articulo' => $inventario->articulo ?? 'Sin nombre', // Asegura que 'articulo' exista
            'cantidad' => 1, // Cantidad inicial
            'stock' => $inventario->stock ?? 0, // Asegura que 'stock' exista
        ];
    }

    $this->selectedInventarioId = null; // Reinicia la selección
}


public function removeInventario($id)
{
    unset($this->selectedInventarios[$id]);
}

    

    
    
    
}
