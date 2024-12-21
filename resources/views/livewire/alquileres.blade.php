

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
                                                <th>Tipo Ingreso</th>
                                                <th>Tipo Pago</th>
                                                <th>Aire Acondicionado</th>
                                                <th>Habitacion</th>
                                                <th>Entrada</th>
                                                <th>Salida</th>
                                                <th>Horas</th>
                                                <th>Total</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($alquileres as $alquiler)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $alquiler->tipoingreso }}</td>
                                                    <td>{{ $alquiler->tipopago }}</td>
                                                    <td>{{ $alquiler->aireacondicionado ? 'Sí' : 'No' }}</td>
                                                    <td>{{ $alquiler->habitacion ? $alquiler->habitacion->habitacion : 'Sin asignar' }}</td>

                                                    <td>{{ $alquiler->entrada }}</td>
<td>{{ $alquiler->salida }}</td>
<td>{{ $alquiler->horas }}</td>

                                                    <td>{{ $alquiler->total }}</td>
                                                    <td>
                                                        <button class="btn btn-info btn-sm">Editar</button>
                                                        <button class="btn btn-danger btn-sm">Eliminar</button>
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
        
            <!-- Modal Crear Alquiler -->
            <div wire:ignore.self class="modal fade" id="createAlquilerModal" tabindex="-1" aria-labelledby="createAlquilerModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="createAlquilerModalLabel">Crear Alquiler</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <form>
                                <div class="mb-3">
                                    <label for="tipoingreso" class="form-label">Tipo Ingreso</label>
                                    <input type="text" class="form-control" id="tipoingreso" wire:model="tipoingreso">
                                </div>
                                <div class="mb-3">
                                    <label for="tipopago" class="form-label">Tipo Pago</label>
                                    <input type="text" class="form-control" id="tipopago" wire:model="tipopago">
                                </div>
                                <div class="mb-3">
                                    <label for="aireacondicionado" class="form-label">Aire Acondicionado</label>
                                    <input type="checkbox" id="aireacondicionado" wire:model="aireacondicionado">
                                </div>
                                
                                <div class="mb-3">
    <label for="entrada" class="form-label">Entrada</label>
    <input type="datetime-local" class="form-control" id="entrada" wire:model="entrada">
</div>
<div class="mb-3">
    <label for="salida" class="form-label">Salida</label>
    <input type="datetime-local" class="form-control" id="salida" wire:model="salida">
</div>
<div class="mb-3">
    <label for="habitacion_id" class="form-label">Habitación</label>
    <select class="form-control" id="habitacion_id" wire:model="habitacion_id">
        <option value="">Seleccione una habitación</option>
        @foreach($habitaciones as $habitacion)
            <option value="{{ $habitacion->id }}">{{ $habitacion->habitacion }}</option>
        @endforeach
    </select>
</div>
<div class="mb-3">
                                    <label for="total" class="form-label">Total</label>
                                    <input type="number" class="form-control" id="total" wire:model="total" readonly>
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
        </div>
        <script>
            window.addEventListener('show-create-modal', () => {
                let modalEl = document.getElementById('createAlquilerModal');
                let modal = new bootstrap.Modal(modalEl);
                modal.show();
            });
        
            // Si tienes un evento para cerrar el modal desde Livewire:
            window.addEventListener('close-modal', () => {
    let modalEl = document.getElementById('createAlquilerModal');
    let modal = new bootstrap.Modal(modalEl); // Crea una nueva instancia
    modal.hide();
});


        </script>
        
                