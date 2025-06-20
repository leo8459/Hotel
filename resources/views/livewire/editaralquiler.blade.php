<div class="container py-4">
    <h2 class="fw-bold mb-4 text-primary">Editar Alquiler</h2>

    @if (session()->has('mensaje'))
        <div class="alert alert-success">{{ session('mensaje') }}</div>
    @endif

    <div class="card">
        <div class="card-body">

            <!-- Aire acondicionado -->
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="aire" wire:model="aireacondicionado">
                <label class="form-check-label" for="aire">
                    Aire Acondicionado
                </label>
            </div>

            <!-- Hora inicio aire -->
            <div class="mb-3">
                <label for="aire_inicio" class="form-label">Hora de Inicio del Aire Acondicionado</label>
                <input type="datetime-local" id="aire_inicio" class="form-control" wire:model="aire_inicio">
            </div>

            <!-- Inventario -->
            <div class="mb-3">
                <label class="form-label">Agregar Inventario</label>
                <select class="form-select" wire:model="inventario_id" wire:change="agregarInventario">
                    <option value="">Seleccione o busque un inventario</option>
                    @foreach($inventarios as $inv)
                        <option value="{{ $inv->id }}">
                            {{ $inv->articulo }} - Precio: {{ $inv->precio }} (Stock: {{ $inv->stock }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Lista consumos -->
            @if ($consumos)
                <div class="mb-3">
                    <label class="form-label fw-bold">Lista de Inventarios Consumidos</label>
                    <ul class="list-unstyled">
                        @foreach($consumos as $id => $item)
                            <li class="mb-2">
                                • {{ $item['articulo'] }} (Precio: {{ $item['precio'] }}) -
                                Cantidad:
                                <input type="number" class="form-control d-inline-block w-auto"
                                       wire:change="actualizarCantidad({{ $id }}, $event.target.value)"
                                       value="{{ $item['cantidad'] }}" min="1">
                                <button class="btn btn-danger btn-sm ms-2" wire:click="eliminarConsumo({{ $id }})">Eliminar</button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Total -->
            <div class="mb-3">
                <label class="form-label">Total de Artículos</label>
                <input type="text" class="form-control" value="{{ $total }}" readonly>
            </div>

            <!-- Botones -->
            <div class="d-flex justify-content-end">
                <a href="{{ route('crear-alquiler') }}" class="btn btn-secondary me-2">Cerrar</a>
                <button class="btn btn-primary" wire:click="guardarCambios">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>
