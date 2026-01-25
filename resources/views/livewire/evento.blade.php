<div class="container-fluid">
    <h1 class="mb-4">Reporte de Movimientos (Kardex)</h1>

    {{-- FILTROS --}}
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label">Fecha Inicio (opcional)</label>
            <input type="datetime-local" wire:model="fechaInicio" class="form-control">
        </div>

        <div class="col-md-3">
            <label class="form-label">Fecha Fin (opcional)</label>
            <input type="datetime-local" wire:model="fechaFin" class="form-control">
        </div>

        <div class="col-md-4">
            <label class="form-label">Buscar por artículo</label>
            <input type="text" wire:model.debounce.500ms="search" class="form-control" placeholder="Ej: Agua...">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100" wire:click="exportarPDF">
                <i class="fas fa-file-pdf"></i> Exportar PDF
            </button>
        </div>
    </div>

    {{-- RESUMEN --}}
    <div class="card mb-3">
        <div class="card-header bg-dark text-white">
            Totales por Artículo
        </div>
        <div class="card-body p-0">
            @if($resumen->count())
                <table class="table table-bordered table-striped mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th>Artículo</th>
                            <th>Stock Ingresado</th>
                            <th>Total Ingresado (Bs)</th>
                            <th>Vendido</th>
                            <th>Total Vendido (Bs)</th>
                            <th>Saldo Stock</th>
                            <th>Precio Unit. Prom. (Bs)</th>
                            <th>Último Mov.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resumen as $r)
                            <tr>
                                <td>{{ $r->articulo }}</td>
                                <td>{{ $r->stock_ingresado }}</td>
                                <td>Bs {{ number_format($r->total_ingresado, 2) }}</td>
                                <td>{{ $r->vendido }}</td>
                                <td>Bs {{ number_format($r->total_vendido, 2) }}</td>
                                <td><b>{{ $r->saldo_stock }}</b></td>
                                <td>Bs {{ number_format($r->precio_unit, 2) }}</td>
                                <td>{{ $r->ultimo_mov_fmt }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="p-3">No hay datos.</div>
            @endif
        </div>
    </div>

    {{-- DETALLE --}}
    <div class="card">
        <div class="card-header bg-primary text-white">
            Detalle de Movimientos (más nuevo primero)
        </div>
        <div class="card-body p-0">
            @if ($eventos->count())
                <table class="table table-bordered table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Artículo</th>
                            <th>Usuario</th>
                            <th>Tipo</th>
                            <th>Ingreso</th>
                            <th>Venta</th>
                            <th>Precio Unit.</th>
                            <th>Total Ingreso</th>
                            <th>Total Venta</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($eventos as $e)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::parse($e->created_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ $e->articulo }}</td>
                                <td>{{ $e->usuario_nombre ?? '—' }}</td>
                                <td>{{ $e->stock > 0 ? 'INGRESO' : 'VENTA' }}</td>
                                <td>{{ $e->stock }}</td>
                                <td>{{ $e->vendido }}</td>
                                <td>Bs {{ number_format($e->precio_unit ?? 0, 2) }}</td>
                                <td>Bs {{ number_format($e->stock > 0 ? $e->precio : 0, 2) }}</td>
                                <td>Bs {{ number_format($e->vendido > 0 ? $e->precio_vendido : 0, 2) }}</td>
                                <td><b>{{ $e->saldo_stock }}</b></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="p-3">
                    {{ $eventos->links() }}
                </div>
            @else
                <div class="p-3 alert alert-warning mb-0">No hay movimientos.</div>
            @endif
        </div>
    </div>
</div>
