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
    public $totalHoras = 0;
    public $totalInventario = 0;
    public $totalGeneral = 0;


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




    public function updateTotals()
    {
        // Calcula el total de las horas en base a la diferencia de tiempo y la tarifa
        $this->totalHoras = $this->calcularHoras(...) ?? 0;
    
        // Calcula el total del inventario consumido recorriendo $this->selectedInventarios
        $this->totalInventario = 0;
        foreach ($this->selectedInventarios as $item) {
            // Supongamos que cada artículo tiene un 'precio' en la base de datos
            $inventario = Inventario::find($item['id']);
            if ($inventario) {
                $this->totalInventario += ($inventario->precio * $item['cantidad']);
            }
        }
    
        // Calculamos el total general
        $this->totalGeneral = $this->totalHoras + $this->totalInventario;
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
            'tipopago'   => 'required|string|in:EFECTIVO,QR,TARJETA',
        ]);
    
        // Parsear fechas con zona horaria de La Paz
        $entrada = Carbon::parse($this->selectedAlquiler->entrada, 'America/La_Paz');
        $salida  = Carbon::parse($this->horaSalida, 'America/La_Paz');
    
        $habitacion = $this->selectedAlquiler->habitacion;
        if (!$habitacion) {
            session()->flash('error', 'No se encontró la habitación asociada.');
            return;
        }
    
        // ---------------------------
        // Cálculo de horas de habitación
        // ---------------------------
        $diferenciaMinutosTotales = $entrada->diffInMinutes($salida);
        $minutosPrimeraHora = 75; // 1 hora 15 min
        $precioHoras = 0;
    
        if ($diferenciaMinutosTotales <= $minutosPrimeraHora) {
            // Solo la primera hora
            $precioHoras += $habitacion->preciohora;
        } else {
            // Sumar la primera hora
            $precioHoras += $habitacion->preciohora;
    
            // Calcular minutos restantes
            $minutosRestantes = $diferenciaMinutosTotales - $minutosPrimeraHora;
    
            // Horas extra completas
            $horasExtras = intval($minutosRestantes / 60);
            $precioHoras += $horasExtras * $habitacion->precio_extra;
    
            // Si quedan minutos sobrantes, sumamos otra hora extra
            $minutosSobrantes = $minutosRestantes % 60;
            if ($minutosSobrantes > 0) {
                $precioHoras += $habitacion->precio_extra;
            }
        }
    
        // ---------------------------
        // Costo aire acondicionado
        // ---------------------------
        $costoAire = $this->calcularCostoAire(); 
        // Este método ya lo tienes definido. Devuelve las horas completas desde aireInicio hasta ahora * 10.
        
        // Sumamos a "horas" el costo de aire para que todo sea "costos por uso de la habitación"
        $this->totalHoras = $precioHoras + $costoAire;
    
        // ---------------------------
        // Cálculo de inventario
        // ---------------------------
        $this->totalInventario = 0;
        foreach ($this->selectedInventarios as $item) {
            $inventario = Inventario::find($item['id']);
            if ($inventario) {
                // Verificamos stock
                if ($inventario->stock < $item['cantidad']) {
                    session()->flash('error', "El inventario '{$inventario->articulo}' no tiene suficiente stock.");
                    return;
                }
                // Sumamos el precio al total de inventario
                $this->totalInventario += $inventario->precio * $item['cantidad'];
    
                // Descontamos el stock
                $inventario->decrement('stock', $item['cantidad']);
            }
        }
    
        // ---------------------------
        // Total general
        // ---------------------------
        $this->totalGeneral = $this->totalHoras + $this->totalInventario;
    
        // Guardamos en la base de datos
        $this->selectedAlquiler->update([
            'salida'             => $this->horaSalida,
            'horas'              => ceil($diferenciaMinutosTotales / 60),
            'estado'             => 'pagado',
            'tipopago'           => $this->tipopago,
            'total'              => $this->totalGeneral,
            'tarifa_seleccionada'=> $this->tarifaSeleccionada,
            'inventario_detalle' => json_encode($this->selectedInventarios),
            'aireacondicionado'  => $this->aireacondicionado,
            'aire_fin'           => Carbon::now('America/La_Paz'),
        ]);
    
        session()->flash('message', "Habitación pagada. Total: Bs {$this->totalGeneral}.");
    
        // Cierra el modal y recarga
        $this->dispatch('close-modal');
        $this->reset([
            'selectedAlquiler', 'horaSalida', 'tipopago', 'tarifaSeleccionada',
            'selectedInventarios', 'aireacondicionado', 'totalHoras',
            'totalInventario', 'totalGeneral'
        ]);
        $this->resetPage();
    
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
        // Esto solamente lo usarías para actualizar en el modal "en vivo"
        // cuando cambias la hora de salida, agregas inventarios, etc.
    
        $habitacion = $this->selectedAlquiler?->habitacion;
        if (!$habitacion || !$this->horaSalida) {
            // Si no hay habitación o no has seleccionado horaSalida todavía, salimos
            $this->total = 0;
            $this->totalHoras = 0;
            $this->totalInventario = 0;
            $this->totalGeneral = 0;
            return 0;
        }
    
        // -----------------------------------------
        // 1) Cálculo del costo por horas
        // -----------------------------------------
        $entrada = Carbon::parse($this->selectedAlquiler->entrada);
        $salida  = Carbon::parse($this->horaSalida);
        $diferenciaMinutosTotales = $entrada->diffInMinutes($salida);
    
        $minutosPrimeraHora = 75;
        $precioHoras = 0;
    
        if ($diferenciaMinutosTotales <= $minutosPrimeraHora) {
            $precioHoras = $habitacion->preciohora;
        } else {
            $precioHoras = $habitacion->preciohora;
            $minutosRestantes = $diferenciaMinutosTotales - $minutosPrimeraHora;
            $horasExtras = intval($minutosRestantes / 60);
            $precioHoras += $horasExtras * $habitacion->precio_extra;
            $minutosSobrantes = $minutosRestantes % 60;
            if ($minutosSobrantes > 0) {
                $precioHoras += $habitacion->precio_extra;
            }
        }
    
        // Costo aire
        $costoAire = $this->calcularCostoAire();
        $this->totalHoras = $precioHoras + $costoAire;
    
        // -----------------------------------------
        // 2) Cálculo de inventario
        // -----------------------------------------
        $this->totalInventario = 0;
        foreach ($this->selectedInventarios as $item) {
            $inventario = Inventario::find($item['id']);
            if ($inventario) {
                $this->totalInventario += $inventario->precio * ($item['cantidad'] ?? 1);
            }
        }
    
        // -----------------------------------------
        // 3) Suma total
        // -----------------------------------------
        $this->totalGeneral = $this->totalHoras + $this->totalInventario;
    
        // Si quieres mantener "total" como antes
        // para otros usos, lo igualas a $totalGeneral
        $this->total = $this->totalGeneral;
    
        return $this->totalGeneral;
    }
    
    


    public function updated($propertyName)
{
    if ($propertyName === 'aireacondicionado' && $this->aireacondicionado) {
        $this->aireInicio = Carbon::now('America/La_Paz')->format('Y-m-d\TH:i');
    }

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

    // Asegurarse de que aireInicio esté en el formato correcto
    $aireInicio = $this->aireInicio ? Carbon::parse($this->aireInicio) : null;

    $alquiler->update([
        'tipoingreso' => $this->tipoingreso,
        'entrada' => $this->entrada,
        'aireacondicionado' => $this->aireacondicionado,
        'aire_inicio' => $aireInicio,
        'inventario_detalle' => json_encode($this->selectedInventarios),
        'total' => $this->calcularTotal(),
    ]);

    session()->flash('message', 'Alquiler actualizado exitosamente.');
    $this->dispatch('close-modal');
    $this->reset(['selectedAlquilerId', 'tipoingreso', 'entrada', 'selectedInventarios', 'aireacondicionado', 'aireInicio', 'total']);
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
