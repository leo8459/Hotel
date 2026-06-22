<?php

namespace App\Livewire;

use App\Models\Alquiler;
use App\Models\Habitacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboardgeneral extends Component
{
    public $selectedHabitacionId = null;

    public function toggleHabitacion(int $habitacionId): void
    {
        $this->selectedHabitacionId = $this->selectedHabitacionId === $habitacionId
            ? null
            : $habitacionId;
    }

    public function cancelarAlquiler(int $alquilerId): void
    {
        try {
            DB::transaction(function () use ($alquilerId) {
                $alquiler = Alquiler::lockForUpdate()->findOrFail($alquilerId);

                if ($alquiler->estado !== 'alquilado') {
                    abort(400, 'El alquiler no esta activo.');
                }

                $detalle = json_decode($alquiler->inventario_detalle, true) ?? [];
                foreach ($detalle as $invId => $item) {
                    $cantidad = (int) ($item['cantidad'] ?? 0);
                    if ($cantidad < 1) {
                        continue;
                    }

                    DB::table('inventarios')
                        ->where('id', $invId)
                        ->increment('stock', $cantidad);
                }

                $alquiler->update([
                    'tipoingreso' => null,
                    'tipopago' => null,
                    'aireacondicionado' => null,
                    'entrada' => null,
                    'salida' => null,
                    'horas' => null,
                    'total' => null,
                    'inventario_detalle' => null,
                    'tarifa_seleccionada' => null,
                    'aire_inicio' => null,
                    'aire_fin' => null,
                    'inventario_id' => null,
                    'usuario_id' => null,
                    'estado' => 'disponible',
                ]);

                Habitacion::whereKey($alquiler->habitacion_id)->update([
                    'estado' => 1,
                    'estado_texto' => 'Disponible',
                    'color' => 'bg-success text-white',
                ]);
            });

            $this->selectedHabitacionId = null;
            session()->flash('message', 'Alquiler cancelado y habitacion liberada.');
        } catch (\Throwable $e) {
            session()->flash('error', 'No se pudo cancelar el alquiler: ' . $e->getMessage());
        }
    }

    private function buildSections(Collection $habitaciones): array
    {
        return [
            [
                'title' => 'En uso',
                'rooms' => $habitaciones->where('estado_texto', 'En uso')->values(),
                'iconClass' => 'icon-uso',
                'icon' => 'H',
                'empty' => 'No hay habitaciones en uso.',
            ],
            [
                'title' => 'Disponibles',
                'rooms' => $habitaciones->where('estado_texto', 'Disponible')->values(),
                'iconClass' => 'icon-disponible',
                'icon' => 'C',
                'empty' => 'No hay habitaciones disponibles.',
            ],
            [
                'title' => 'En limpieza',
                'rooms' => $habitaciones->where('estado_texto', 'En limpieza')->values(),
                'iconClass' => 'icon-limpieza',
                'icon' => 'L',
                'empty' => 'No hay habitaciones en limpieza.',
            ],
            [
                'title' => 'Mantenimiento',
                'rooms' => $habitaciones->where('estado_texto', 'Mantenimiento')->values(),
                'iconClass' => 'icon-mantenimiento',
                'icon' => 'M',
                'empty' => 'No hay habitaciones en mantenimiento.',
            ],
            [
                'title' => 'Pagado',
                'rooms' => $habitaciones->where('estado_texto', 'Pagado')->values(),
                'iconClass' => 'icon-pagado',
                'icon' => 'P',
                'empty' => 'No hay habitaciones en estado pagado.',
            ],
        ];
    }

    public function render()
    {
        $habitaciones = Habitacion::query()
            ->select(['id', 'habitacion', 'estado_texto', 'color'])
            ->orderBy('habitacion')
            ->get();

        $alquileresActivos = DB::table('alquiler')
            ->select(['id', 'habitacion_id', 'estado'])
            ->whereIn('estado', ['alquilado', 'pagado'])
            ->orderByDesc('entrada')
            ->get()
            ->keyBy('habitacion_id');

        $totalGenerado = DB::table('alquiler')
            ->where('estado', 'pagado')
            ->sum('total');

        return view('livewire.dashboardgeneral', [
            'sections' => $this->buildSections($habitaciones),
            'alquileresActivos' => $alquileresActivos,
            'totalGenerado' => $totalGenerado,
        ]);
    }
}
