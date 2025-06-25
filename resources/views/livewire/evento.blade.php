<div class="container-fluid">
    <h1 class="mb-4">Reporte Detallado de Inventario</h1>

    {{-- BUSCADOR + BOTÓN PDF --}}
    <div class="mb-3 d-flex justify-content-between">
        <input type="text"
               wire:model.debounce.500ms="search"
               class="form-control w-50"
               placeholder="Buscar por artículo...">

        <button class="btn btn-primary ms-3" wire:click="exportarPDF">
            <i class="fas fa-file-pdf"></i> Exportar PDF
        </button>
    </div>

    {{-- TABLA --}}
    @if ($eventos->count())
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Artículo</th>
                    <th>Stock Ingresado</th>
                    <th>Precio Unitario (Bs)</th>
                    <th>Total Ingresado (Bs)</th>
                    <th>Vendido</th>
                    <th>Total Vendido (Bs)</th>
                    <th>Stock Actual</th>
                    <th>Diferencia (Bs)</th>
                    <th>Último Movimiento</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($eventos as $e)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $e->articulo }}</td>
                        <td>{{ $e->stock_ingresado }}</td>
                        <td>Bs {{ number_format($e->precio_unitario, 2) }}</td>
                        <td>Bs {{ number_format($e->total_ingresado, 2) }}</td>
                        <td>{{ $e->vendido }}</td>
                        <td>Bs {{ number_format($e->total_vendido, 2) }}</td>
                        <td>{{ $e->stock_ingresado - $e->vendido }}</td>
                        <td>
                            @if ($e->diferencia >= 0)
                                <span class="text-success">Bs {{ number_format($e->diferencia, 2) }}</span>
                            @else
                                <span class="text-danger">Bs {{ number_format($e->diferencia, 2) }}</span>
                            @endif
                        </td>
                        <td>{{ $e->fecha }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- PAGINACIÓN --}}
        <div class="mt-3">
            {{ $eventos->links() }}
        </div>
    @else
        <div class="alert alert-warning">No se encontraron registros.</div>
    @endif
</div>
