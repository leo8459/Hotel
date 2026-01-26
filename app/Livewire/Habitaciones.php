<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Habitacion;
use App\Models\Inventario;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class Habitaciones extends Component
{
    use WithPagination;

    public $searchTerm = '';
    public $perPage = 10;

    public $habitacion, $tipo, $preciohora, $precio_extra, $tarifa_opcion1, $tarifa_opcion2, $tarifa_opcion3, $tarifa_opcion4;
    public $selectedHabitacionId = null;

    // ✅ Freezer
    public $inventariosDisponibles;
    public $freezer = [];                 // [inventario_id => qty]
    public $freezerInventarioId = null;

    protected $rules = [
        'habitacion' => 'required|string|max:255',
        'tipo' => 'required|string|max:255',
        'preciohora' => 'nullable|integer|min:0',
        'precio_extra' => 'nullable|integer|min:0',
        'tarifa_opcion1' => 'nullable|integer|min:0',
        'tarifa_opcion2' => 'nullable|integer|min:0',
        'tarifa_opcion3' => 'nullable|integer|min:0',
        'tarifa_opcion4' => 'nullable|integer|min:0',
    ];

    public function render()
    {
        $habitaciones = Habitacion::where('habitacion', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        $this->inventariosDisponibles = Inventario::orderBy('articulo')->get();

        return view('livewire.habitaciones', [
            'habitaciones' => $habitaciones,
            'inventariosDisponibles' => $this->inventariosDisponibles,
        ])->extends('adminlte::page')->section('content');
    }

    public function openCreateModal()
    {
        $this->resetInputFields();
        $this->dispatch('show-create-modal');
    }

    public function store()
    {
        $this->validate();

        $freezerNuevo = $this->normalizarFreezer($this->freezer);
        $freezerViejo = []; // al crear no existía nada

        try {
            DB::transaction(function () use ($freezerViejo, $freezerNuevo) {

                // ✅ Ajustar inventario por delta freezer

                Habitacion::create([
                    'habitacion' => $this->habitacion,
                    'tipo' => $this->tipo,
                    'preciohora' => $this->preciohora,
                    'precio_extra' => $this->precio_extra,
                    'tarifa_opcion1' => $this->tarifa_opcion1,
                    'tarifa_opcion2' => $this->tarifa_opcion2,
                    'tarifa_opcion3' => $this->tarifa_opcion3,
                    'tarifa_opcion4' => $this->tarifa_opcion4,
                    'freezer_stock' => $freezerNuevo,
                ]);
            });

            session()->flash('message', 'Habitación creada con éxito (Freezer actualizado y stock descontado).');
            $this->dispatch('close-modal');
            $this->resetInputFields();
            $this->resetPage();

        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openEditModal($id)
    {
        $habitacion = Habitacion::find($id);

        if (!$habitacion) {
            session()->flash('error', 'La habitación no existe.');
            return;
        }

        $this->selectedHabitacionId = $habitacion->id;

        $this->habitacion = $habitacion->habitacion;
        $this->tipo = $habitacion->tipo;
        $this->preciohora = $habitacion->preciohora;
        $this->precio_extra = $habitacion->precio_extra;
        $this->tarifa_opcion1 = $habitacion->tarifa_opcion1;
        $this->tarifa_opcion2 = $habitacion->tarifa_opcion2;
        $this->tarifa_opcion3 = $habitacion->tarifa_opcion3;
        $this->tarifa_opcion4 = $habitacion->tarifa_opcion4;

        $this->freezer = $habitacion->freezer_stock ?? [];
        $this->freezerInventarioId = null;

        $this->dispatch('show-edit-modal');
    }

    public function update()
    {
        $this->validate();

        $habitacion = Habitacion::find($this->selectedHabitacionId);
        if (!$habitacion) {
            session()->flash('error', 'La habitación no existe.');
            return;
        }

        $freezerViejo = $habitacion->freezer_stock ?? [];
        $freezerNuevo = $this->normalizarFreezer($this->freezer);

        try {
            DB::transaction(function () use ($habitacion, $freezerViejo, $freezerNuevo) {

                // ✅ Ajustar inventario por delta freezer

                $habitacion->update([
                    'habitacion' => $this->habitacion,
                    'tipo' => $this->tipo,
                    'preciohora' => $this->preciohora,
                    'precio_extra' => $this->precio_extra,
                    'tarifa_opcion1' => $this->tarifa_opcion1,
                    'tarifa_opcion2' => $this->tarifa_opcion2,
                    'tarifa_opcion3' => $this->tarifa_opcion3,
                    'tarifa_opcion4' => $this->tarifa_opcion4,
                    'freezer_stock' => $freezerNuevo,
                ]);
            });

            session()->flash('message', 'Habitación actualizada (Freezer actualizado y stock ajustado).');
            $this->dispatch('close-modal');
            $this->resetInputFields();
            $this->resetPage();

        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function delete($id)
    {
        $habitacion = Habitacion::find($id);

        if ($habitacion) {
            $habitacion->delete();
            session()->flash('message', 'Habitación eliminada correctamente.');
        } else {
            session()->flash('error', 'La habitación no existe.');
        }
    }

    // ================= FREEZER UI =================

    public function addFreezerItem()
    {
        if (!$this->freezerInventarioId) return;

        $inv = Inventario::find($this->freezerInventarioId);
        if (!$inv) return;

        $id = (int)$inv->id;

        if (!isset($this->freezer[$id])) $this->freezer[$id] = 1;
        else $this->freezer[$id] = (int)$this->freezer[$id] + 1;

        $this->freezerInventarioId = null;
    }

    public function removeFreezerItem($id)
    {
        unset($this->freezer[(int)$id]);
    }

    public function updatedFreezer()
    {
        foreach ($this->freezer as $id => $qty) {
            $qty = (int)$qty;
            if ($qty < 0) $qty = 0;
            $this->freezer[(int)$id] = $qty;
        }
    }

    // ================= LOGICA STOCK =================

   

    private function normalizarFreezer($freezer): array
    {
        $out = [];
        if (!is_array($freezer)) return $out;

        foreach ($freezer as $id => $qty) {
            $id = (int)$id;
            $qty = (int)$qty;
            if ($id > 0 && $qty > 0) $out[$id] = $qty;
        }
        return $out;
    }

    private function resetInputFields()
    {
        $this->habitacion = '';
        $this->tipo = '';
        $this->preciohora = null;
        $this->precio_extra = null;
        $this->tarifa_opcion1 = null;
        $this->tarifa_opcion2 = null;
        $this->tarifa_opcion3 = null;
        $this->tarifa_opcion4 = null;
        $this->selectedHabitacionId = null;

        $this->freezer = [];
        $this->freezerInventarioId = null;
    }
}
