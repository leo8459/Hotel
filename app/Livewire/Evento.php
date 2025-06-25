<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Eventos;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class Evento extends Component
{
    use WithPagination;

    public $search = '';

    /* ------ LISTADO EN PANTALLA ------------------------------------------------ */
    public function render()
    {
        $eventos = Eventos::selectRaw('
                    articulo,
                    SUM(stock)          AS stock_ingresado,
                    SUM(vendido)        AS vendido,
                    SUM(precio)         AS total_ingresado,
                    SUM(precio_vendido) AS total_vendido,
                    MAX(created_at)     AS fecha_ultimo_mov
                ')
                ->where('articulo', 'LIKE', "%{$this->search}%")
                ->groupBy('articulo')
                ->orderByDesc('fecha_ultimo_mov')
                ->paginate(10)
                ->through(function ($row) {
                    // Campos derivados
                    $row->precio_unitario = $row->stock_ingresado > 0
                        ? $row->total_ingresado / $row->stock_ingresado
                        : 0;

                    $row->diferencia = $row->total_vendido - $row->total_ingresado;
                    $row->fecha      = Carbon::parse($row->fecha_ultimo_mov)
                                        ->format('d/m/Y H:i');
                    return $row;
                });

        return view('livewire.evento', compact('eventos'));
    }

    /* ------ EXPORTAR PDF ------------------------------------------------------- */
    public function exportarPDF()
    {
        $eventos = Eventos::selectRaw('
                        articulo,
                        SUM(stock)          AS stock_ingresado,
                        SUM(vendido)        AS vendido,
                        SUM(precio)         AS total_ingresado,
                        SUM(precio_vendido) AS total_vendido,
                        MAX(created_at)     AS fecha_ultimo_mov
                    ')
                    ->groupBy('articulo')
                    ->orderByDesc('fecha_ultimo_mov')
                    ->get()
                    ->map(function ($row) {
                        $row->precio_unitario = $row->stock_ingresado > 0
                            ? $row->total_ingresado / $row->stock_ingresado
                            : 0;
                        $row->diferencia = $row->total_vendido - $row->total_ingresado;
                        $row->fecha      = Carbon::parse($row->fecha_ultimo_mov)
                                                ->format('d/m/Y H:i');
                        return $row;
                    });

        $pdf = Pdf::loadView('pdf.reporte-evento', compact('eventos'))
                  ->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'reporte_evento.pdf'
        );
    }
}
