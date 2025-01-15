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
    public $aireInicio; // Hora de inicio del aire acondicionado
    public $aireFin; // Hora de fin del aire acondicionado



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
    
        $this->selectedInventarios = collect(json_decode($this->selectedAlquiler->inventario_detalle, true) ?? [])
            ->mapWithKeys(function ($item) {
                $inventario = Inventario::find($item['id']);
                return [
                    $item['id'] => [
                        'id' => $item['id'],
                        'articulo' => $inventario->articulo ?? 'Sin nombre',
                        'cantidad' => $item['cantidad'] ?? 0,
                        'stock' => $inventario->stock ?? 0,
                    ],
                ];
            })->toArray();
    
        // Asegurarse de cargar correctamente el estado del aire acondicionado
        $this->aireacondicionado = $this->selectedAlquiler->aireacondicionado;
    
        $this->dispatch('show-pay-modal');
    }
    
    
    


    // Método para realizar el pago
    public function pay()
    {
        $this->validate([
            'horaSalida' => 'required|date|after_or_equal:selectedAlquiler.entrada',
            'tipopago' => 'required|string|in:EFECTIVO,QR,TARJETA',
        ]);
    
        // Parsear las fechas con la zona horaria de La Paz
    $entrada = Carbon::parse($this->selectedAlquiler->entrada, 'America/La_Paz');
    $salida = Carbon::parse($this->horaSalida, 'America/La_Paz');

        $habitacion = $this->selectedAlquiler->habitacion;
    
        if (!$habitacion) {
            session()->flash('error', 'No se encontró la habitación asociada.');
            return;
        }
    
        $precioTotal = 0;
    
        // Calcular precio por horas de la habitación
        $diferenciaHoras = $entrada->diffInHours($salida);
        $diferenciaMinutos = $entrada->diff($salida)->i;
    
        if ($diferenciaHoras == 0 && $diferenciaMinutos > 0) {
            $precioTotal += $habitacion->preciohora;
        } else {
            $precioTotal += $habitacion->preciohora;
            if ($diferenciaHoras > 1) {
                $precioTotal += ($diferenciaHoras - 1) * $habitacion->precio_extra;
            }
            if ($diferenciaMinutos > 0) {
                $precioTotal += $habitacion->precio_extra;
            }
        }
    
        // Calcular costo del aire acondicionado
        $costoAire = 0;
        if ($this->aireacondicionado) {
            $aireInicio = $this->selectedAlquiler->aire_inicio ? Carbon::parse($this->selectedAlquiler->aire_inicio) : null;
            $aireFin = Carbon::now('America/La_Paz'); // Asignar automáticamente la hora actual como fin del aire acondicionado
    
            if ($aireInicio && $aireFin->greaterThan($aireInicio)) {
                $diferenciaHorasAire = $aireInicio->diffInHours($aireFin);
                $costoAire = $diferenciaHorasAire * 10; // 10 Bs por hora
                $precioTotal += $costoAire;
            }
    
            \Log::info('Costo del aire acondicionado calculado', [
                'aireInicio' => $aireInicio,
                'aireFin' => $aireFin,
                'diferenciaHorasAire' => $diferenciaHorasAire ?? 0,
                'costoAire' => $costoAire,
            ]);
        }
    
        // Calcular precio del inventario seleccionado
        foreach ($this->selectedInventarios as $item) {
            $inventario = Inventario::find($item['id']);
            if ($inventario) {
                $precioTotal += $inventario->precio * $item['cantidad'];
                if ($inventario->stock >= $item['cantidad']) {
                    $inventario->decrement('stock', $item['cantidad']);
                } else {
                    session()->flash('error', "El inventario '{$inventario->articulo}' no tiene suficiente stock.");
                    return;
                }
            }
        }
    
        // Actualizar el alquiler con los nuevos valores
        $this->selectedAlquiler->update([
            'salida' => $this->horaSalida,
            'horas' => $diferenciaHoras + ($diferenciaMinutos > 0 ? 1 : 0),
            'estado' => 'pagado',
            'tipopago' => $this->tipopago,
            'total' => $precioTotal,
            'tarifa_seleccionada' => $this->tarifaSeleccionada,
            'inventario_detalle' => json_encode($this->selectedInventarios),
            'aireacondicionado' => $this->aireacondicionado,
            'aire_fin' => Carbon::now('America/La_Paz'), // Guardar la hora actual como aire_fin
        ]);
    
        session()->flash('message', "Habitación pagada. Total: $precioTotal.");
    
        // Cierra el modal y recarga la página
        $this->dispatch('close-modal');
        $this->reset(['selectedAlquiler', 'horaSalida', 'tipopago', 'tarifaSeleccionada', 'selectedInventarios', 'aireacondicionado']);
        $this->resetPage();
    
        // Refrescar la página
        return redirect()->to(request()->header('Referer'));
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

        // Calcular costo del aire acondicionado
        $total += $this->calcularCostoAire();

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
        $this->aireacondicionado = $alquiler->aireacondicionado;
        $this->aireInicio = $alquiler->aire_inicio ? Carbon::parse($alquiler->aire_inicio)->format('Y-m-d\TH:i') : null;
        $this->aireFin = $alquiler->aire_fin ? Carbon::parse($alquiler->aire_fin)->format('Y-m-d\TH:i') : null;
    
        $this->inventarios = Inventario::where('stock', '>', 0)->get();
    
        $this->dispatch('show-edit-modal');
    }
    

    public function update()
    {
        
        $alquiler = Alquiler::find($this->selectedAlquilerId);
    
        if (!$alquiler) {
            session()->flash('error', 'El alquiler no existe.');
            return;
        }
    
        // Asegurar que aireInicio y aireFin estén en el formato correcto
        $aireInicio = $this->aireInicio ? Carbon::parse($this->aireInicio) : null;
        $aireFin = $this->aireFin ? Carbon::parse($this->aireFin) : null;
    
        $alquiler->update([
            'tipoingreso' => $this->tipoingreso,
            'entrada' => $this->entrada,
            'aireacondicionado' => $this->aireacondicionado,
            'aire_inicio' => $aireInicio,
            'aire_fin' => $aireFin,
            'inventario_detalle' => json_encode($this->selectedInventarios),
            'total' => $this->calcularTotal(),
        ]);
    
        session()->flash('message', 'Alquiler actualizado exitosamente.');
    
        $this->dispatch('close-modal');
        $this->reset(['selectedAlquilerId', 'tipoingreso', 'entrada', 'selectedInventarios', 'aireacondicionado', 'aireInicio', 'aireFin', 'total']);
        $this->resetPage();
    
        return redirect()->to(request()->header('Referer'));
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

    public function calcularCostoAire()
{
    if (!$this->aireInicio || !$this->aireacondicionado) {
        return 0;
    }

    $inicio = Carbon::parse($this->aireInicio, 'America/La_Paz');
    $fin = Carbon::now('America/La_Paz'); // Hora actual de La Paz

    if ($fin->lessThanOrEqualTo($inicio)) {
        return 0;
    }

    $horasCompletas = $inicio->diffInHours($fin);
    $costoPorHora = 10;

    return $horasCompletas * $costoPorHora;
}

    
    
    

}
