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
                                <input type="text" wire:model="searchTerm" class="form-control"
                                    placeholder="Buscar...">
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Tipo Ingreso</th>
                                            <th>Tipo Pago</th>
                                            <th>Aire Acondicionado</th>
                                            <th>Habitación</th>
                                            <th>Consumo</th>
                                            <th>Entrada</th>
                                            <th>Salida</th>
                                            <th>Horas</th>
                                            <th>Total</th>
                                            <th>Estado</th> <!-- Nueva columna -->
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
                                                <td>{{ $alquiler->habitacion ? $alquiler->habitacion->habitacion : 'Sin asignar' }}
                                                </td>
                                                <td>{{ $alquiler->inventario ? $alquiler->inventario->articulo : 'Sin asignar' }}</td>

                                                <td>{{ $alquiler->entrada }}</td>
                                                <td>{{ $alquiler->salida }}</td>
                                                <td>
                                                    {{ $alquiler->horas }} horas 
                                                    @if($alquiler->horas > 0 || $alquiler->minutos > 0)
                                                        y {{ floor((strtotime($alquiler->salida) - strtotime($alquiler->entrada)) % 3600 / 60) }} minutos
                                                    @endif
                                                </td>
                                                                                                <td>{{ $alquiler->total }}</td>
                                                <td>
                                                    <span class="badge {{ $alquiler->estado == 'pagado' ? 'bg-success' : 'bg-warning' }}">
                                                        {{ ucfirst($alquiler->estado) }}
                                                    </span>
                                                </td>
                                                                                                <td>
                                                    <button class="btn btn-primary btn-sm" wire:click="openPayModal({{ $alquiler->id }})">Pagar Habitación</button>
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


<!-- Modal para pagar habitación -->
<div wire:ignore.self class="modal fade" id="payAlquilerModal" tabindex="-1" aria-labelledby="payAlquilerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="payAlquilerModalLabel">Pagar Habitación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="horaSalida" class="form-label">Hora de Salida</label>
                        <input type="datetime-local" class="form-control" id="horaSalida" wire:model="horaSalida" readonly>
                    </div>
                    @if ($isPaying)
    <div class="mb-3">
        <label for="tipopago" class="form-label">Tipo Pago</label>
        <select class="form-control" id="tipopago" wire:model="tipopago">
            <option value="">Seleccione el tipo de pago</option>
            <option value="EFECTIVO">EFECTIVO</option>
            <option value="QR">QR</option>
            <option value="TARJETA">TARJETA</option>
        </select>
        @error('tipopago') <span class="text-danger">{{ $message }}</span> @enderror
    </div>
@endif

                    <div class="mb-3">
                        <label for="tiempoTranscurrido" class="form-label">Tiempo Transcurrido</label>
                        <input type="text" 
                               class="form-control" 
                               id="tiempoTranscurrido" 
                               readonly 
                               value="{{ isset($selectedAlquiler) && $selectedAlquiler && $horaSalida ? 
                                        floor((strtotime($horaSalida) - strtotime($selectedAlquiler->entrada)) / 3600) . ' horas y ' . 
                                        floor(((strtotime($horaSalida) - strtotime($selectedAlquiler->entrada)) % 3600) / 60) . ' minutos' 
                                        : 'N/A' }}">
                    </div>
                    
                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" wire:click="pay">Guardar</button>
            </div>
        </div>
    </div>
</div>

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
                        <select class="form-control" id="tipoingreso" wire:model="tipoingreso">
                            <option value="">Seleccione el tipo de ingreso</option>
                            <option value="A PIE">A PIE</option>
                            <option value="AUTOMOVIL">AUTOMOVIL</option>
                            <option value="MOTO">MOTO</option>
                            <option value="OTRO">OTRO</option>
                        </select>
                        @error('tipoingreso') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    
                  
                    
                    <div class="mb-3">
                        <label for="aireacondicionado" class="form-label">Aire Acondicionado</label>
                        <input type="checkbox" id="aireacondicionado" wire:model="aireacondicionado">
                    </div>
                    <div class="mb-3">
                        <label for="entrada" class="form-label">Hora de Entrada</label>
                        <input type="datetime-local" class="form-control" id="entrada" wire:model="entrada" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="habitacion_id" class="form-label">Habitación</label>
                        <select class="form-control" id="habitacion_id" wire:model="habitacion_id">
                            <option value="">Seleccione una habitación</option>
                            @foreach($habitaciones as $habitacion)
                                <option value="{{ $habitacion->id }}">{{ $habitacion->habitacion }}</option>
                            @endforeach
                        </select>
                        @error('habitacion_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-3">
    <label for="inventarios" class="form-label">Inventarios</label>
    @foreach ($inventarios as $index => $inventario)
        <div class="d-flex align-items-center mb-2">
            <!-- Checkbox para seleccionar el inventario -->
            <input 
                type="checkbox" 
                id="inventario_{{ $inventario->id }}" 
                wire:click="toggleInventario({{ $inventario->id }}, {{ $index }})"
                class="form-check-input me-2">
            
            <label for="inventario_{{ $inventario->id }}" class="form-label me-3">
                {{ $inventario->articulo }} (Stock: {{ $inventario->stock }})
            </label>

            <!-- Campo de cantidad -->
            <input 
                type="number" 
                wire:model="selectedInventarios.{{ $inventario->id }}.cantidad"
                placeholder="Cantidad"
                min="1"
                max="{{ $inventario->stock }}"
                class="form-control"
                style="width: 100px;"
                {{ !isset($selectedInventarios[$inventario->id]) ? 'disabled' : '' }}>
        </div>
    @endforeach
    @error('selectedInventarios.*.cantidad') <span class="text-danger">{{ $message }}</span> @enderror
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


        let intervalId;

    window.addEventListener('show-pay-modal', () => {
        const horaSalidaInput = document.getElementById('horaSalida');

        // Función para actualizar el tiempo automáticamente
        function updateHoraSalida() {
            const now = new Date();
            const formattedDateTime = now.toISOString().slice(0, 16); // Formato para datetime-local
            horaSalidaInput.value = formattedDateTime;
            @this.set('horaSalida', formattedDateTime); // Actualizar el valor en Livewire
        }

        // Inicia el intervalo para actualizar cada segundo
        updateHoraSalida();
        intervalId = setInterval(updateHoraSalida, 1000);

        // Mostrar el modal
        let modalEl = document.getElementById('payAlquilerModal');
        let modal = new bootstrap.Modal(modalEl);
        modal.show();
    });

    window.addEventListener('close-modal', () => {
        // Detiene el intervalo cuando se cierra el modal
        clearInterval(intervalId);

        // Cierra el modal
        let modalEl = document.getElementById('payAlquilerModal');
        let modal = new bootstrap.Modal(modalEl);
        modal.hide();
    });
    </script>
