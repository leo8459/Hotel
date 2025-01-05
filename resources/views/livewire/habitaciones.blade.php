<section class="content">
    <div class="container-fluid">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Gestión de Habitaciones</h1>
                    </div>
                    <div class="col-sm-6">
                        <button type="button" class="btn btn-success float-right" wire:click="openCreateModal">
                            Crear Habitación
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
                                            <th>Precio por Hora</th>
                                            <th>Precio Extra</th>
                                            <th>Tarifa 1</th>
                                            <th>Tarifa 2</th>
                                            <th>Tarifa 3</th>
                                            <th>Tarifa 4</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($habitaciones as $habitacion)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $habitacion->habitacion }}</td>
                                                <td>{{ $habitacion->tipo }}</td>
                                                <td>{{ $habitacion->preciohora }}</td>
                                                <td>{{ $habitacion->precio_extra }}</td>
                                                <td>{{ $habitacion->tarifa_opcion1 }}</td>
                                                <td>{{ $habitacion->tarifa_opcion2 }}</td>
                                                <td>{{ $habitacion->tarifa_opcion3 }}</td>
                                                <td>{{ $habitacion->tarifa_opcion4 }}</td>
                                                <td>
                                                    <button class="btn btn-info btn-sm" wire:click="openEditModal({{ $habitacion->id }})">Editar</button>
                                                    <button class="btn btn-danger btn-sm" wire:click="delete({{ $habitacion->id }})">Eliminar</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="mt-3">
                                    {{ $habitaciones->links() }}
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
                            <div class="mb-3">
                                <label for="preciohora" class="form-label">Precio por Hora</label>
                                <input type="number" class="form-control" id="preciohora" wire:model="preciohora">
                            </div>
                            <div class="mb-3">
                                <label for="precio_extra" class="form-label">Precio Extra</label>
                                <input type="number" class="form-control" id="precio_extra" wire:model="precio_extra">
                            </div>
                            <div class="mb-3">
                                <label for="tarifa_opcion1" class="form-label">Tarifa Opción 1</label>
                                <input type="number" class="form-control" id="tarifa_opcion1" wire:model="tarifa_opcion1">
                            </div>
                            <div class="mb-3">
                                <label for="tarifa_opcion2" class="form-label">Tarifa Opción 2</label>
                                <input type="number" class="form-control" id="tarifa_opcion2" wire:model="tarifa_opcion2">
                            </div>
                            <div class="mb-3">
                                <label for="tarifa_opcion3" class="form-label">Tarifa Opción 3</label>
                                <input type="number" class="form-control" id="tarifa_opcion3" wire:model="tarifa_opcion3">
                            </div>
                            <div class="mb-3">
                                <label for="tarifa_opcion4" class="form-label">Tarifa Opción 4</label>
                                <input type="number" class="form-control" id="tarifa_opcion4" wire:model="tarifa_opcion4">
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
                            <div class="mb-3">
                                <label for="preciohora" class="form-label">Precio por Hora</label>
                                <input type="number" class="form-control" id="preciohora" wire:model="preciohora">
                            </div>
                            <div class="mb-3">
                                <label for="precio_extra" class="form-label">Precio Extra</label>
                                <input type="number" class="form-control" id="precio_extra" wire:model="precio_extra">
                            </div>
                            <div class="mb-3">
                                <label for="tarifa_opcion1" class="form-label">Tarifa Opción 1</label>
                                <input type="number" class="form-control" id="tarifa_opcion1" wire:model="tarifa_opcion1">
                            </div>
                            <div class="mb-3">
                                <label for="tarifa_opcion2" class="form-label">Tarifa Opción 2</label>
                                <input type="number" class="form-control" id="tarifa_opcion2" wire:model="tarifa_opcion2">
                            </div>
                            <div class="mb-3">
                                <label for="tarifa_opcion3" class="form-label">Tarifa Opción 3</label>
                                <input type="number" class="form-control" id="tarifa_opcion3" wire:model="tarifa_opcion3">
                            </div>
                            <div class="mb-3">
                                <label for="tarifa_opcion4" class="form-label">Tarifa Opción 4</label>
                                <input type="number" class="form-control" id="tarifa_opcion4" wire:model="tarifa_opcion4">
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
</section>
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
        let modalCreate = document.getElementById('createAlquilerModal');
        let modalEdit = document.getElementById('editAlquilerModal');
        new bootstrap.Modal(modalCreate).hide();
        new bootstrap.Modal(modalEdit).hide();
    });
</script>
