<section class="content">
    <div class="container-fluid">

        {{-- HEADER --}}
        <section class="content-header">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Gestión de Artículos</h1>
                </div>
                <div class="col-sm-6 text-end">
                    <button type="button" class="btn btn-success" wire:click="openCreateModal">
                        Crear Artículo
                    </button>
                </div>
            </div>
        </section>

        {{-- ALERTAS --}}
        @if (session()->has('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        {{-- TABLA (pantallas grandes) --}}
        <div class="d-none d-md-block">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Artículo</th>
                    <th>Precio Compra</th>
                    <th>Precio Venta</th>
                    <th>Total Compra</th>
                    <th>Stock Disponible</th>
                    <th>Freezers Stock</th>
                    <th>Stock Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($articulos as $articulo)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $articulo->articulo }}</td>
                        <td>{{ number_format($articulo->precio_entrada, 2) }}</td>
                        <td>{{ number_format($articulo->precio, 2) }}</td>
                        <td>{{ number_format($articulo->total_compra, 2) }}</td>
                        @php
                            $freezerStock = (int)($freezerTotales[$articulo->id] ?? 0);
                            $stockDisponible = max(0, (int)$articulo->stock - $freezerStock);
                            $stockTotal = $stockDisponible + $freezerStock;
                        @endphp
                        <td>{{ $stockDisponible }}</td>
                        <td>{{ $freezerStock }}</td>
                        <td>{{ $stockTotal }}</td>
                        <td>
                            <span class="badge {{ $articulo->estado ? 'bg-success' : 'bg-danger' }}">
                                {{ $articulo->estado ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-info btn-sm" wire:click="openEditModal({{ $articulo->id }})">
                                Editar
                            </button>
                            <button class="btn btn-warning btn-sm" wire:click="openSalidaModal({{ $articulo->id }})">
                                Salida
                            </button>
                            <button class="btn btn-danger btn-sm" wire:click="delete({{ $articulo->id }})">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="mt-3">
                {{ $articulos->links() }}
            </div>
        </div>

        {{-- CARDS (pantallas pequeñas) --}}
        <div class="d-md-none">
            @foreach ($articulos as $articulo)
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">{{ $articulo->articulo }}</h5>

                        <p class="card-text">
                            <strong>Precio Compra:</strong> {{ number_format($articulo->precio_entrada, 2) }}<br>
                            <strong>Precio Venta:</strong> {{ number_format($articulo->precio, 2) }}<br>
                            @php
                                $freezerStock = (int)($freezerTotales[$articulo->id] ?? 0);
                                $stockDisponible = max(0, (int)$articulo->stock - $freezerStock);
                                $stockTotal = $stockDisponible + $freezerStock;
                            @endphp
                            <strong>Stock Disponible:</strong> {{ $stockDisponible }}<br>
                            <strong>Freezers Stock:</strong> {{ $freezerStock }}<br>
                            <strong>Stock Total:</strong> {{ $stockTotal }}<br>
                            <strong>Estado:</strong>
                            <span class="badge {{ $articulo->estado ? 'bg-success' : 'bg-danger' }}">
                                {{ $articulo->estado ? 'Activo' : 'Inactivo' }}
                            </span>
                        </p>

                        <div class="d-flex justify-content-between">
                            <button class="btn btn-info btn-sm" wire:click="openEditModal({{ $articulo->id }})">
                                Editar
                            </button>
                            <button class="btn btn-warning btn-sm" wire:click="openSalidaModal({{ $articulo->id }})">
                                Salida
                            </button>
                            <button class="btn btn-danger btn-sm" wire:click="delete({{ $articulo->id }})">
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="mt-3">
                {{ $articulos->links() }}
            </div>
        </div>

        {{-- MODAL CREAR --}}
        <div wire:ignore.self class="modal fade" id="createArticuloModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Crear Artículo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Artículo</label>
                                <input type="text" class="form-control" wire:model="articulo">
                                @error('articulo') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Precio Compra</label>
                                <input type="number" class="form-control" wire:model="precio_entrada" step="0.01">
                                @error('precio_entrada') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Precio Venta</label>
                                <input type="number" class="form-control" wire:model="precio" step="0.01">
                                @error('precio') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Stock</label>
                                <input type="number" class="form-control" wire:model="stock">
                                @error('stock') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            {{-- ✅ Totales automáticos --}}
                            <div class="mb-3">
                                <label class="form-label">Total Compra</label>
                                <input type="number" class="form-control" wire:model="total_compra" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Total Venta</label>
                                <input type="number" class="form-control" wire:model="total_venta" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-control" wire:model="estado">
                                    <option value="">Seleccione</option>
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                                @error('estado') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button class="btn btn-primary" wire:click="store">Guardar</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL EDITAR --}}
        <div wire:ignore.self class="modal fade" id="editArticuloModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Artículo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Artículo</label>
                                <input type="text" class="form-control" wire:model="articulo">
                                @error('articulo') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Precio Compra</label>
                                <input type="number" class="form-control" wire:model="precio_entrada" step="0.01">
                                @error('precio_entrada') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Precio Venta</label>
                                <input type="number" class="form-control" wire:model="precio" step="0.01">
                                @error('precio') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Stock a Añadir</label>
                                <input type="number" class="form-control" wire:model="stock">
                                @error('stock') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            {{-- ✅ Totales automáticos (sobre lo añadido) --}}
                            <div class="mb-3">
                                <label class="form-label">Total Compra</label>
                                <input type="number" class="form-control" wire:model="total_compra" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Total Venta</label>
                                <input type="number" class="form-control" wire:model="total_venta" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-control" wire:model="estado">
                                    <option value="">Seleccione</option>
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                                @error('estado') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button class="btn btn-primary" wire:click="update">Guardar</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL SALIDA --}}
        <div wire:ignore.self class="modal fade" id="salidaArticuloModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Registrar Salida</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Cantidad</label>
                            <input type="number" class="form-control" wire:model="salidaCantidad" min="1">
                            @error('salidaCantidad') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button class="btn btn-warning" wire:click="registrarSalida">Registrar salida</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- JS MODALES --}}
    <script>
        window.addEventListener('show-create-modal', () => {
            new bootstrap.Modal(document.getElementById('createArticuloModal')).show();
        });

        window.addEventListener('show-edit-modal', () => {
            new bootstrap.Modal(document.getElementById('editArticuloModal')).show();
        });

        window.addEventListener('show-salida-modal', () => {
            new bootstrap.Modal(document.getElementById('salidaArticuloModal')).show();
        });

        window.addEventListener('close-modal', () => {
            bootstrap.Modal.getInstance(document.getElementById('createArticuloModal'))?.hide();
            bootstrap.Modal.getInstance(document.getElementById('editArticuloModal'))?.hide();
            bootstrap.Modal.getInstance(document.getElementById('salidaArticuloModal'))?.hide();
        });
    </script>
</section>
