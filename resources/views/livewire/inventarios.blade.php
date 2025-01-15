<section class="content">
    <div class="container-fluid">
        <section class="content-header">
            <div class="container-fluid">
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
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                @if (session()->has('message'))
                    <div class="alert alert-success">{{ session('message') }}</div>
                @endif

                <!-- Tabla para pantallas medianas y grandes -->
                <div class="d-none d-md-block">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Artículo</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($articulos as $articulo)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $articulo->articulo }}</td>
                                    <td>{{ $articulo->precio }}</td>
                                    <td>{{ $articulo->stock }}</td>
                                    <td>
                                        <span class="badge {{ $articulo->estado ? 'bg-success' : 'bg-danger' }}">
                                            {{ $articulo->estado ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-info btn-sm" wire:click="openEditModal({{ $articulo->id }})">Editar</button>
                                        <button class="btn btn-danger btn-sm" wire:click="delete({{ $articulo->id }})">Eliminar</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-3">
                        {{ $articulos->links() }}
                    </div>
                </div>

                <!-- Tarjetas para dispositivos pequeños -->
                <div class="d-md-none">
                    @foreach ($articulos as $articulo)
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Artículo: {{ $articulo->articulo }}</h5>
                                <p class="card-text">
                                    <strong>Precio:</strong> {{ $articulo->precio }}<br>
                                    <strong>Stock:</strong> {{ $articulo->stock }}<br>
                                    <strong>Estado:</strong>
                                    <span class="badge {{ $articulo->estado ? 'bg-success' : 'bg-danger' }}">
                                        {{ $articulo->estado ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </p>
                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-info btn-sm" wire:click="openEditModal({{ $articulo->id }})">Editar</button>
                                    <button class="btn btn-danger btn-sm" wire:click="delete({{ $articulo->id }})">Eliminar</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div class="mt-3">
                        {{ $articulos->links() }}
                    </div>
                </div>
            </div>
        </section>

        <!-- Modal Crear Artículo -->
        <div wire:ignore.self class="modal fade" id="createArticuloModal" tabindex="-1" aria-labelledby="createArticuloModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createArticuloModalLabel">Crear Artículo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="mb-3">
                                <label for="articulo" class="form-label">Artículo</label>
                                <input type="text" class="form-control" id="articulo" wire:model="articulo">
                                @error('articulo') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="precio" class="form-label">Precio</label>
                                <input type="number" class="form-control" id="precio" wire:model="precio" step="0.01">
                                @error('precio') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="stock" wire:model="stock">
                                @error('stock') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-control" id="estado" wire:model="estado">
                                    <option value="">Seleccione el estado</option>
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                                @error('estado') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" wire:click="store">Guardar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Editar Artículo -->
        <div wire:ignore.self class="modal fade" id="editArticuloModal" tabindex="-1" aria-labelledby="editArticuloModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editArticuloModalLabel">Editar Artículo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="mb-3">
                                <label for="articulo" class="form-label">Artículo</label>
                                <input type="text" class="form-control" id="articulo" wire:model="articulo">
                                @error('articulo') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="precio" class="form-label">Precio</label>
                                <input type="number" class="form-control" id="precio" wire:model="precio" step="0.01">
                                @error('precio') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="stock" wire:model="stock">
                                @error('stock') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-control" id="estado" wire:model="estado">
                                    <option value="">Seleccione el estado</option>
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                                @error('estado') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" wire:click="update">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

    <script>
        window.addEventListener('show-create-modal', () => {
            let modalEl = document.getElementById('createArticuloModal');
            let modal = new bootstrap.Modal(modalEl);
            modal.show();
        });

        window.addEventListener('show-edit-modal', () => {
            let modalEl = document.getElementById('editArticuloModal');
            let modal = new bootstrap.Modal(modalEl);
            modal.show();
        });

        window.addEventListener('close-modal', () => {
            let createModalEl = document.getElementById('createArticuloModal');
            let editModalEl = document.getElementById('editArticuloModal');
            let createModal = new bootstrap.Modal(createModalEl);
            let editModal = new bootstrap.Modal(editModalEl);
            createModal.hide();
            editModal.hide();
        });
    </script>
</section>
