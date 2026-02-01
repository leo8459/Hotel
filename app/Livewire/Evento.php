<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Eventos;
use App\Models\Habitacion;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class Evento extends Component
{
    use WithPagination;

    public $search = '';
    public $fechaInicio = null; // vacío al inicio
    public $fechaFin    = null; // vacío al inicio

    protected $paginationTheme = 'bootstrap';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedFechaInicio()
    {
        $this->resetPage();
    }

    public function updatedFechaFin()
    {
        $this->resetPage();
    }

    private function rangoFechas(): array
    {
        $tz = 'America/La_Paz';

        $inicio = $this->fechaInicio
            ? Carbon::parse($this->fechaInicio, $tz)->toDateTimeString()
            : null;

        $fin = $this->fechaFin
            ? Carbon::parse($this->fechaFin, $tz)->toDateTimeString()
            : null;

        return [$inicio, $fin];
    }

    /** ✅ Query DETALLE */
    private function queryDetalle()
    {
        [$inicio, $fin] = $this->rangoFechas();

        return Eventos::query()
            ->leftJoin('users', 'users.id', '=', 'eventos.usuario_id')
            ->leftJoin('inventarios', 'inventarios.id', '=', 'eventos.inventario_id')

            ->when($this->search, fn($q) => $q->where('eventos.articulo', 'like', "%{$this->search}%"))
            ->when($inicio, fn($q) => $q->where('eventos.created_at', '>=', $inicio))
            ->when($fin, fn($q) => $q->where('eventos.created_at', '<=', $fin))

            ->select([
                'eventos.*',
                DB::raw('users.name as usuario_nombre'),
                DB::raw('inventarios.articulo as inventario_nombre'),

                // ✅ saldo acumulado por artículo
                DB::raw("
                    SUM(eventos.stock - eventos.vendido)
                    OVER (PARTITION BY eventos.articulo ORDER BY eventos.created_at, eventos.id)
                    as saldo_stock
                "),

                // ✅ precio unitario
                DB::raw("
                    CASE
                        WHEN eventos.stock > 0 AND eventos.precio > 0 THEN (eventos.precio / NULLIF(eventos.stock,0))
                        WHEN inventarios.precio IS NOT NULL THEN inventarios.precio
                        ELSE 0
                    END as precio_unit
                "),
            ]);
    }

    /** ✅ Query RESUMEN POR ARTÍCULO */
    private function queryResumen()
    {
        [$inicio, $fin] = $this->rangoFechas();

        return Eventos::query()
            ->leftJoin('inventarios', 'inventarios.id', '=', 'eventos.inventario_id')

            ->when($this->search, fn($q) => $q->where('eventos.articulo', 'like', "%{$this->search}%"))
            ->when($inicio, fn($q) => $q->where('eventos.created_at', '>=', $inicio))
            ->when($fin, fn($q) => $q->where('eventos.created_at', '<=', $fin))

            ->selectRaw("
                eventos.articulo as articulo,
                MAX(inventarios.articulo) as inventario_nombre,
                MAX(inventarios.id) as inventario_id,
                MAX(inventarios.stock) as stock_actual,

                SUM(eventos.stock) as stock_ingresado,
                SUM(eventos.vendido) as vendido,

                SUM(CASE WHEN eventos.stock > 0 THEN eventos.precio ELSE 0 END) as total_ingresado,
                SUM(CASE WHEN eventos.vendido > 0 THEN eventos.precio_vendido ELSE 0 END) as total_vendido,

                SUM(eventos.stock - eventos.vendido) as saldo_stock,
                MAX(eventos.created_at) as ultimo_mov
            ")
            ->groupBy('eventos.articulo')
            ->orderByDesc('ultimo_mov');
    }

    private function getFreezerTotales(): array
    {
        $totales = [];

        $freezers = Habitacion::query()->pluck('freezer_stock');
        foreach ($freezers as $freezer) {
            if (!is_array($freezer)) {
                continue;
            }

            foreach ($freezer as $id => $qty) {
                $id = (int)$id;
                $qty = (int)$qty;
                if ($id <= 0 || $qty <= 0) {
                    continue;
                }

                $totales[$id] = ($totales[$id] ?? 0) + $qty;
            }
        }

        return $totales;
    }

    public function render()
    {
        $freezerTotales = $this->getFreezerTotales();

        $resumen = $this->queryResumen()->get()->map(function ($row) {
            $row->precio_unit = ($row->stock_ingresado > 0)
                ? ($row->total_ingresado / $row->stock_ingresado)
                : 0;

            $row->ultimo_mov_fmt = Carbon::parse($row->ultimo_mov)->format('d/m/Y H:i');
            return $row;
        });

        $resumen = $resumen->map(function ($row) use ($freezerTotales) {
            $freezerStock = (int)($freezerTotales[$row->inventario_id] ?? 0);
            $stockActual = (int)($row->stock_actual ?? 0);
            $stockDisponible = max(0, $stockActual - $freezerStock);

            $row->stock_disponible = $stockDisponible;
            $row->freezer_stock = $freezerStock;
            $row->stock_total = $stockDisponible + $freezerStock;
            return $row;
        });

        $eventos = $this->queryDetalle()
            ->orderBy('eventos.created_at', 'desc')
            ->orderBy('eventos.id', 'desc')
            ->paginate(10);

        return view('livewire.evento', compact('eventos', 'resumen'))
            ->extends('adminlte::page')
            ->section('content');
    }

    public function exportarPDF()
    {
        $eventos = $this->queryDetalle()
            ->orderBy('eventos.created_at', 'desc')
            ->orderBy('eventos.id', 'desc')
            ->get();

        $freezerTotales = $this->getFreezerTotales();

        $resumen = $this->queryResumen()->get()->map(function ($row) {
            $row->precio_unit = ($row->stock_ingresado > 0)
                ? ($row->total_ingresado / $row->stock_ingresado)
                : 0;

            $row->ultimo_mov_fmt = Carbon::parse($row->ultimo_mov)->format('d/m/Y H:i');
            return $row;
        });

        $resumen = $resumen->map(function ($row) use ($freezerTotales) {
            $freezerStock = (int)($freezerTotales[$row->inventario_id] ?? 0);
            $stockActual = (int)($row->stock_actual ?? 0);
            $stockDisponible = max(0, $stockActual - $freezerStock);

            $row->stock_disponible = $stockDisponible;
            $row->freezer_stock = $freezerStock;
            $row->stock_total = $stockDisponible + $freezerStock;
            return $row;
        });

        $tz = 'America/La_Paz';
        $inicioTxt = $this->fechaInicio ? Carbon::parse($this->fechaInicio, $tz)->format('d/m/Y H:i') : 'TODO';
        $finTxt    = $this->fechaFin ? Carbon::parse($this->fechaFin, $tz)->format('d/m/Y H:i') : 'TODO';

        $pdf = Pdf::loadView('pdf.reporte-evento-fechas', [
            'eventos'   => $eventos,
            'resumen'   => $resumen,
            'inicioTxt' => $inicioTxt,
            'finTxt'    => $finTxt,
            'search'    => $this->search,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'reporte_eventos.pdf'
        );
    }
}
