<section class="content">
    <div class="container-fluid">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Gestión de Alquileres</h1>
                    </div>
                    <div class="col-sm-6">
                        <button type="button" class="btn btn-success float-right" wire:click="openCreateModal">
                            Crear Alquiler
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

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <input type="text" wire:model="searchTerm" class="form-control" placeholder="Buscar...">
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Habitación</th>
                                            <th>Tipo</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($alquileres as $alquilere)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $alquilere->habitacion }}</td>
                                                <td>{{ $alquilere->tipo }}</td>
                                                <td>
                                                    <button class="btn btn-info btn-sm" wire:click="openEditModal({{ $alquilere->id }})">Editar</button>
                                                    <button class="btn btn-danger btn-sm" wire:click="delete({{ $alquilere->id }})">Eliminar</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="mt-3">
                                    {{ $alquileres->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Modal Crear Habitación -->
        <div wire:ignore.self class="modal fade" id="createAlquilerModal" tabindex="-1" aria-labelledby="createAlquilerModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createAlquilerModalLabel">Crear Habitación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="mb-3">
                                <label for="habitacion" class="form-label">Habitación</label>
                                <input type="text" class="form-control" id="habitacion" wire:model="habitacion">
                            </div>
                            <div class="mb-3">
                                <label for="tipo" class="form-label">Tipo</label>
                                <input type="text" class="form-control" id="tipo" wire:model="tipo">
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

        <!-- Modal Editar Habitación -->
        <div wire:ignore.self class="modal fade" id="editAlquilerModal" tabindex="-1" aria-labelledby="editAlquilerModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editAlquilerModalLabel">Editar Habitación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="mb-3">
                                <label for="habitacion" class="form-label">Habitación</label>
                                <input type="text" class="form-control" id="habitacion" wire:model="habitacion">
                            </div>
                            <div class="mb-3">
                                <label for="tipo" class="form-label">Tipo</label>
                                <input type="text" class="form-control" id="tipo" wire:model="tipo">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" wire:click="update">Guardar Cambios</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.addEventListener('show-create-modal', () => {
            let modalEl = document.getElementById('createAlquilerModal');
            let modal = new bootstrap.Modal(modalEl);
            modal.show();
        });

        window.addEventListener('show-edit-modal', () => {
            let modalEl = document.getElementById('editAlquilerModal');
            let modal = new bootstrap.Modal(modalEl);
            modal.show();
        });

        window.addEventListener('close-modal', () => {
            let modalElCreate = document.getElementById('createAlquilerModal');
            let modalElEdit = document.getElementById('editAlquilerModal');
            let modalCreate = new bootstrap.Modal(modalElCreate);
            let modalEdit = new bootstrap.Modal(modalElEdit);
            modalCreate.hide();
            modalEdit.hide();
        });
    </script>
</section>
