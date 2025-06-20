<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Alquiler;
use App\Models\Habitacion;
use App\Models\Inventario;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\BoletaAlquiler;

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
    public $horaEntradaTrabajo, $horaSalidaTrabajo;
    public $fechaInicio;
    public $fechaFin;


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
            // 1) Tiempo transcurrido
            $alquiler->tiempo_transcurrido = $this->getTiempoTranscurrido(
                $alquiler->entrada,
                $alquiler->estado
            );

            // 2) Calculamos el total de inventario para este alquiler
            $alquiler->total_inventario = 0;
            $detalles = json_decode($alquiler->inventario_detalle, true) ?? [];
           foreach ($detalles as $item) {
    if (!isset($item['id'], $item['cantidad'])) {
        continue; // Evita el error si faltan datos
    }

    $inv = Inventario::find($item['id']);
    if ($inv) {
        $alquiler->total_inventario += $inv->precio * $item['cantidad'];
    }
}


            // 3) El costo de la habitación es el total global - el inventario
            $alquiler->total_habitacion = $alquiler->total - $alquiler->total_inventario;
        }

        $habitaciones = Habitacion::whereDoesntHave('alquileres', function ($query) {
            $query->where('estado', 'alquilado');
        })->get();

        $this->inventarios = Inventario::where('stock', '>', 0)->get();

        return view('livewire.alquileres', [
            'alquileres'    => $alquileres,
            'habitaciones'  => $habitaciones,
            'inventarios'   => $this->inventarios,
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



    public function updatedSelectedInventarios()
    {
        // Cada vez que cambia cantidad de un inventario, recalculamos:
        $this->calcularTotal();
    }

    // Abrir el modal de pago
    public function openPayModal($id)
    {
        $this->selectedAlquiler = Alquiler::find($id);

        if (!$this->selectedAlquiler) {
            session()->flash('error', 'El alquiler no existe.');
            return;
        }

        $this->horaSalida        = Carbon::now('America/La_Paz')->format('Y-m-d\TH:i');
        $this->tarifaSeleccionada = null;

        // Mapeo de los inventarios: cargamos también el precio
        $this->selectedInventarios = collect(
            json_decode($this->selectedAlquiler->inventario_detalle, true) ?? []
        )->mapWithKeys(function ($item) {
            $inventario = Inventario::find($item['id']);
            return [
                $item['id'] => [
                    'id'       => $item['id'],
                    'articulo' => $inventario->articulo ?? 'Sin nombre',
                    'cantidad' => $item['cantidad'] ?? 0,
                    'stock'    => $inventario->stock ?? 0,
                    'precio'   => $inventario->precio ?? 0, // <--- Aquí guardamos el precio
                ],
            ];
        })->toArray();

        // Asegurarse de cargar correctamente el estado del aire
        $this->aireacondicionado = $this->selectedAlquiler->aireacondicionado;

        // Si quieres que se calcule de inmediato el total, puedes llamar a:
        // $this->calcularTotal();

        $this->dispatch('show-pay-modal');
    }





    // Método para realizar el pago
    public function pay()
    {
        $this->validate([
            'horaSalida' => 'required|date|after_or_equal:selectedAlquiler.entrada',
            'tipopago'   => 'required|string|in:EFECTIVO,QR,TARJETA',
        ]);

        // 1) Verifica que tengas un alquiler seleccionado
        if (!$this->selectedAlquiler) {
            session()->flash('error', 'No se encontró el alquiler para efectuar el pago.');
            return;
        }

        // 2) Parseamos las fechas
        $entrada    = Carbon::parse($this->selectedAlquiler->entrada, 'America/La_Paz');
        $salida     = Carbon::parse($this->horaSalida, 'America/La_Paz');
        $habitacion = $this->selectedAlquiler->habitacion;

        if (!$habitacion) {
            session()->flash('error', 'No se encontró la habitación asociada.');
            return;
        }

        // 3) Calculamos la diferencia en minutos y el costo base por horas
        $diferenciaMinutosTotales = $entrada->diffInMinutes($salida);
        $precioHoras              = $habitacion->preciohora;

        // Si pasa 75 minutos (1h15m), cobramos extra
        if ($diferenciaMinutosTotales > 75) {
            // Cada hora adicional (o fracción) se cobra con precio_extra
            $precioHoras += (intdiv($diferenciaMinutosTotales - 75, 60) + 1)
                * $habitacion->precio_extra;
        }

        // 4) Costo adicional por aire (si está habilitado)
        $costoAire        = $this->calcularCostoAire();
        $this->totalHoras = $precioHoras + $costoAire;

        // 5) Sumar los inventarios (productos consumidos)
        $this->totalInventario = 0;
        foreach ($this->selectedInventarios as $item) {
            $inventario = Inventario::find($item['id']);
            if ($inventario) {
                $this->totalInventario += ($inventario->precio * $item['cantidad']);
                // Descontar el stock
                $inventario->decrement('stock', $item['cantidad']);
            }
        }

        // 6) Suma total final
        $this->totalGeneral = $this->totalHoras + $this->totalInventario;

        // 7) Marcamos el alquiler como pagado y guardamos
        $this->selectedAlquiler->update([
            'salida'              => $this->horaSalida,
            'horas'               => ceil($diferenciaMinutosTotales / 60),
            'estado'              => 'pagado',
            'tipopago'            => $this->tipopago,
            'total'               => $this->totalGeneral,
            'tarifa_seleccionada' => $this->tarifaSeleccionada,
            'inventario_detalle'  => json_encode($this->selectedInventarios),
            'aireacondicionado'   => $this->aireacondicionado,
            'aire_fin'            => Carbon::now('America/La_Paz'),
            'usuario_id'          => auth()->id(), // usuario que realiza el cobro
        ]);

        // Guardamos el ID para la boleta, antes de resetear nada
        $alquilerId = $this->selectedAlquiler->id;

        // 8) Notificación de éxito
        session()->flash('message', "Habitación pagada. Total: Bs {$this->totalGeneral}.");

        // 9) Cierra modales y limpia propiedades de Livewire
        $this->dispatch('close-modal');
        $this->reset([
            'selectedAlquiler',
            'horaSalida',
            'tipopago',
            'tarifaSeleccionada',
            'selectedInventarios',
            'aireacondicionado',
            'totalHoras',
            'totalInventario',
            'totalGeneral'
        ]);
        $this->resetPage();

        // 10) Generar la boleta en PDF y retornarla
        return $this->generarBoleta($alquilerId);
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
        $this->tipoingreso        = $alquiler->tipoingreso;
        $this->entrada            = $alquiler->entrada;
        $this->aireacondicionado  = $alquiler->aireacondicionado;
        $this->aireInicio         = $alquiler->aire_inicio
            ? Carbon::parse($alquiler->aire_inicio)->format('Y-m-d\TH:i')
            : null;
        $this->aireFin            = $alquiler->aire_fin
            ? Carbon::parse($alquiler->aire_fin)->format('Y-m-d\TH:i')
            : null;

        // Mapear inventarios y cargar precio
        $this->selectedInventarios = collect(json_decode($alquiler->inventario_detalle, true) ?? [])
            ->mapWithKeys(function ($item) {
                $inventario = Inventario::find($item['id']);
                return [
                    $item['id'] => [
                        'id'       => $item['id'],
                        'articulo' => $inventario->articulo ?? 'Sin nombre',
                        'cantidad' => $item['cantidad'] ?? 0,
                        'stock'    => $inventario->stock ?? 0,
                        'precio'   => $inventario->precio ?? 0,
                    ],
                ];
            })->toArray();

        // Para que el <input> "Total" (solo inventarios) muestre la suma inicial
        $this->total = $this->calcularTotalSoloInventario();

        // Carga lista de inventarios disponibles (si fuera necesario)
        $this->inventarios = Inventario::where('stock', '>', 0)->get();

        $this->dispatch('show-edit-modal'); // Abre el modal
    }

    /**
     * Retorna la suma del inventario (cantidad * precio) 
     * en el arreglo $this->selectedInventarios, sin incluir horas o aire.
     */
    public function calcularTotalSoloInventario()
    {
        $suma = 0;
        foreach ($this->selectedInventarios as $item) {
            // Asegúrate de que existan 'precio' y 'cantidad'
            if (isset($item['precio'], $item['cantidad'])) {
                $suma += ($item['precio'] * $item['cantidad']);
            }
        }
        return $suma;
    }



    public function update()
    {
        $alquiler = Alquiler::find($this->selectedAlquilerId);

        if (!$alquiler) {
            session()->flash('error', 'El alquiler no existe.');
            return;
        }

        $aireInicio = $this->aireInicio ? Carbon::parse($this->aireInicio) : null;

        // Suma solo de los productos:
        $soloArticulos = $this->calcularTotalSoloInventario();

        $alquiler->update([
            'tipoingreso'        => $this->tipoingreso,
            'entrada'            => $this->entrada,
            'aireacondicionado'  => $this->aireacondicionado,
            'aire_inicio'        => $aireInicio,
            'inventario_detalle' => json_encode($this->selectedInventarios),
            'total'              => $soloArticulos, // <-- Guardo sólo el total de artículos
        ]);

        session()->flash('message', 'Alquiler actualizado correctamente.');
        $this->dispatch('close-modal');

        $this->reset([
            'selectedAlquilerId',
            'tipoingreso',
            'entrada',
            'selectedInventarios',
            'aireacondicionado',
            'aireInicio',
            'total'
        ]);
        $this->resetPage();

        // Redirigir o refrescar
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
                'id'       => $inventario->id,
                'articulo' => $inventario->articulo ?? 'Sin nombre', // Nombre
                'cantidad' => 1,                                     // Cantidad inicial
                'stock'    => $inventario->stock ?? 0,              // Stock
                'precio'   => $inventario->precio ?? 0,             // <--- AÑADE EL PRECIO AQUÍ
            ];
        }

        // Reinicia la selección
        $this->selectedInventarioId = null;
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
            ->where('usuario_id', $usuario->id) // Ahora este campo existe en la tabla
            ->get();

        $totalGenerado = $alquileres->sum('total');

        $pdf = Pdf::loadView('pdf.reporte_turno', compact('usuario', 'alquileres', 'totalGenerado'));

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'reporte_turno_' . $usuario->id . '.pdf'
        );
    }

    public function generarBoleta($alquilerId, bool $enviarEmail = true)
    {
        $alquiler = Alquiler::findOrFail($alquilerId);

        // --- Cálculo de totales e inventario (igual que antes) ---
        $detalleInventario = json_decode($alquiler->inventario_detalle, true) ?? [];

        $totalInventario = 0;
        foreach ($detalleInventario as $item) {
            $inv = Inventario::find($item['id']);
            if ($inv) {
                $totalInventario += ($inv->precio * $item['cantidad']);
            }
        }
        $totalHabitacion = $alquiler->total - $totalInventario;

        // --- Generar PDF en memoria ---
        $pdf = Pdf::loadView('pdf.boleta', [
            'alquiler'          => $alquiler,
            'detalleInventario' => $detalleInventario,
            'totalInventario'   => $totalInventario,
            'totalHabitacion'   => $totalHabitacion,
            'fechaPago'         => optional($alquiler->updated_at)->format('d-m-Y H:i'),
        ]);

        // --- Enviar por correo si se pide ---
        if ($enviarEmail) {
            Mail::to('hector.fernandez.z@gmail.com')
                ->send(new BoletaAlquiler($alquiler, $pdf->output()));
        }

        // --- Descargar / mostrar al usuario como ya lo hacías ---
        return response()->streamDownload(
            fn() => print($pdf->output()),
            "boleta_{$alquiler->id}.pdf"
        );
    }

    public function reimprimirBoleta($alquilerId)
    {
        // Simplemente llamamos a 'generarBoleta'
        return $this->generarBoleta($alquilerId);
    }

    public function exportarResumenPorFechas()
    {
        $this->validate([
            'fechaInicio' => 'required|date',
            'fechaFin'    => 'required|date|after_or_equal:fechaInicio',
        ]);

        $alquileres = Alquiler::with(['habitacion', 'usuario'])
            ->where('estado', 'pagado')
            ->whereBetween('updated_at', [$this->fechaInicio, $this->fechaFin])
            ->get();

        $totalGeneral = $alquileres->sum('total');
        $resumenProductos = [];
        $resumenPorHabitacion = [];

        foreach ($alquileres as $alq) {
            // Por habitación
            $nombreHab = optional($alq->habitacion)->habitacion ?? 'Sin nombre';
            if (!isset($resumenPorHabitacion[$nombreHab])) {
                $resumenPorHabitacion[$nombreHab] = 0;
            }
            $resumenPorHabitacion[$nombreHab] += $alq->total;

            // Por productos
            $detalle = json_decode($alq->inventario_detalle, true) ?? [];
            foreach ($detalle as $item) {
                $inv = Inventario::find($item['id']);
                if (!$inv) continue;

                $nombreProd = $inv->articulo;
                if (!isset($resumenProductos[$nombreProd])) {
                    $resumenProductos[$nombreProd] = 0;
                }
                $resumenProductos[$nombreProd] += $item['cantidad'];
            }
        }

        $fechaHoy = now('America/La_Paz')->format('d-m-Y');

        $pdf = Pdf::loadView('pdf.reporte-resumen-alquileres', [
            'totalGeneral'        => $totalGeneral,
            'resumenProductos'    => $resumenProductos,
            'resumenPorHabitacion' => $resumenPorHabitacion,
            'alquileres'          => $alquileres,
            'fechaInicio'         => $this->fechaInicio,
            'fechaFin'            => $this->fechaFin,
            'fechaHoy'            => $fechaHoy,
        ]);

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'reporte_resumen_' . $fechaHoy . '.pdf'
        );
    }
}
