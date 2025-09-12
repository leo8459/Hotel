<div class="container py-4">
    <h2 class="fw-bold mb-4 text-primary">Editar Alquiler</h2>

    @if (session()->has('mensaje'))
        <div class="alert alert-success">{{ session('mensaje') }}</div>
    @endif

    <div class="card">
        <div class="card-body">

            {{-- Aire acondicionado --}}
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="aire" wire:model="aireacondicionado">
                <label class="form-check-label" for="aire">
                    Aire Acondicionado
                </label>
            </div>

            {{-- Hora inicio aire --}}
            <div class="mb-3">
                <label for="aire_inicio" class="form-label">Hora de Inicio del Aire Acondicionado</label>
                <input type="datetime-local" id="aire_inicio" class="form-control" wire:model="aire_inicio">
            </div>

            <hr>

            {{-- ⏱️ Tiempo de alquiler --}}
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Entrada</label>
                    <input type="datetime-local" class="form-control" wire:model="entrada" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Salida (por defecto: ahora)</label>
                    <input type="datetime-local" class="form-control" wire:model.live="salida" wire:change="actualizarSalida($event.target.value)">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Horas (redondeo ↑)</label>
                    <input type="number" class="form-control" value="{{ $horas }}" readonly>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <label class="form-label">Costo servicio (horas x precio)</label>
                    <input type="text" class="form-control" value="{{ number_format($costoServicio, 2) }}" readonly>
                </div>
            </div>

            <hr>

            {{-- Inventario --}}
            <div class="mb-2">
                <label class="form-label">Agregar Inventario</label>
                <select class="form-select" wire:model="inventario_id" wire:change="agregarInventario">
                    <option value="">Seleccione o busque un inventario</option>
                    @foreach($inventarios as $inv)
                        <option value="{{ $inv->id }}">
                            {{ $inv->articulo }} - Precio: {{ $inv->precio }} (Stock: {{ $inv->stock }})
                        </option>
                    @endforeach
                </select>
                @if ($inventario_id)
                    <small class="text-muted d-block mt-1">
                        Disponible del seleccionado:
                        <strong>{{ $this->getStockDisponible((int)$inventario_id) }}</strong>
                        / Stock total: <strong>{{ $this->getStockTotal((int)$inventario_id) }}</strong>
                    </small>
                @endif
            </div>

            {{-- Lista consumos --}}
            @if ($consumos)
                <div class="mb-3">
                    <label class="form-label fw-bold">Lista de Inventarios Consumidos</label>
                    <ul class="list-unstyled">
                        @foreach($consumos as $id => $item)
                            <li class="mb-2">
                                • {{ $item['articulo'] }} (Precio: {{ $item['precio'] }}) —
                                Cantidad:
                                <input
                                    type="number"
                                    class="form-control d-inline-block w-auto"
                                    wire:change="actualizarCantidad({{ $id }}, $event.target.value)"
                                    value="{{ $item['cantidad'] }}"
                                    min="1"
                                    max="{{ $this->getStockTotal($id) }}"
                                >
                                <span class="badge bg-light text-dark ms-2">
                                    Disp: {{ $this->getStockDisponible($id) }}
                                </span>
                                <button class="btn btn-danger btn-sm ms-2" wire:click="eliminarConsumo({{ $id }})">
                                    Eliminar
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Totales --}}
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Total de Artículos</label>
                    <input type="text" class="form-control" value="{{ number_format($total, 2) }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Total Final (servicio + artículos)</label>
                    <input type="text" class="form-control fw-bold" value="{{ number_format($totalGeneral, 2) }}" readonly>
                </div>
            </div>

            {{-- Botones --}}
            <div class="d-flex justify-content-end mt-4">
                <a href="{{ route('crear-alquiler') }}" class="btn btn-secondary me-2">Cerrar</a>
                <button class="btn btn-primary" wire:click="guardarCambios">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>
