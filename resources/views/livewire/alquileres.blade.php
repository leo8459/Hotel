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
                        <div class="d-flex justify-content-between mb-3">
                            <button class="btn btn-success" wire:click="iniciarTrabajo">Iniciar Turno</button>
                            <button class="btn btn-danger" wire:click="finalizarTrabajo">Finalizar Turno</button>
                        </div>

                        @if (session()->has('message'))
                            <div class="alert alert-success">{{ session('message') }}</div>
                        @endif


                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Fecha Inicio</label>
                        <input type="date" class="form-control" wire:model="fechaInicio">
                    </div>
                    <div class="col-md-3">
                        <label>Fecha Fin</label>
                        <input type="date" class="form-control" wire:model="fechaFin">
                    </div>
                    <div class="col-md-3 align-self-end">
                        <button class="btn btn-success" wire:click="exportarResumenPorFechas">
                            Exportar Resumen
                        </button>
                    </div>
                </div>

            </div>
        </section>


        @if (session()->has('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <input type="text" wire:model="searchTerm" class="form-control" placeholder="Buscar...">
                    </div>
                    <div class="d-none d-md-block table-responsive">
                        <!-- Tabla visible en dispositivos medianos y grandes -->
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tipo Ingreso</th>
                                    <th>Tipo Pago</th>
                                    <th>Aire Acond.</th>
                                    <th>Habitación</th>
                                    <th>Consumo</th>
                                    <th>Entrada</th>
                                    <th>Salida</th>
                                    <th>Tiempo Transcurrido</th>
                                    <th>Horas</th>
                                    <th>Tarifa Sel.</th>

                                    <!-- NUEVAS COLUMNAS -->
                                    <th>Total Habitación</th>
                                    <th>Total Productos</th>

                                    <th>Total Final</th>
                                    <th>Estado</th>
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
                                        <td>{{ optional($alquiler->habitacion)->habitacion }}</td>
                                        <td> <!-- Detalle inventario -->
                                            @php
                                                // Devuelve un array asociativo: [inventario_id => ['articulo'=>..., 'precio'=>..., 'cantidad'=>...], ...]
                                                $detalleInventario = json_decode($alquiler->inventario_detalle, true);
                                            @endphp

                                            @if (is_array($detalleInventario) && !empty($detalleInventario))
                                                <ul class="mb-0">
                                                    @foreach ($detalleInventario as $inventarioId => $item)
                                                        @php
                                                            // Intenta traer el nombre actualizado desde la BD; si no existe, usa el que viene en el JSON
                                                            $inventario = \App\Models\Inventario::find($inventarioId);
                                                            $nombreArticulo =
                                                                $inventario?->articulo ??
                                                                ($item['articulo'] ?? 'Artículo sin nombre');
                                                        @endphp
                                                        <li>
                                                            {{ $nombreArticulo }}: {{ $item['cantidad'] }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                Sin consumo
                                            @endif
                                        </td>

                                        <td>{{ $alquiler->entrada }}</td>
                                        <td>{{ $alquiler->salida }}</td>
                                        <td>{{ $alquiler->tiempo_transcurrido }}</td>
                                        <td>{{ $alquiler->horas }}</td>
                                        <td>{{ $alquiler->tarifa_seleccionada }}</td>

                                        <!-- MOSTRAR LOS NUEVOS CAMPOS CALCULADOS -->
                                        <td>{{ $alquiler->total_habitacion }}</td> <!-- Horas + Aire -->
                                        <td>{{ $alquiler->total_inventario }}</td> <!-- Productos -->

                                        <td>{{ $alquiler->total }}</td> <!-- Suma final -->
                                        <td>
                                            <span
                                                class="badge {{ $alquiler->estado == 'pagado' ? 'bg-success' : 'bg-warning' }}">
                                                {{ ucfirst($alquiler->estado) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($alquiler->estado !== 'pagado')
                                                <!-- Botón para pagar -->
                                                <button class="btn btn-primary btn-sm"
                                                    wire:click="openPayModal({{ $alquiler->id }})">
                                                    Pagar Habitación
                                                </button>
                                                <button class="btn btn-info btn-sm"
                                                    wire:click="openEditModal({{ $alquiler->id }})">
                                                    Editar
                                                </button>
                                            @else
                                                <!-- Nuevo botón para reimprimir boleta -->
                                                <button class="btn btn-info btn-sm"
                                                    wire:click="reimprimirBoleta({{ $alquiler->id }})">
                                                    Reimprimir Boleta
                                                </button>
                                            @endif

                                            <!-- Botón eliminar (si lo tienes) -->
                                            <button class="btn btn-danger btn-sm">
                                                Eliminar
                                            </button>
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>

                        </table>
                    </div>

                    <div class="d-md-none">
                        <!-- Cards visibles en dispositivos pequeños -->
                        @foreach ($alquileres as $alquiler)
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Alquiler #{{ $loop->iteration }}</h5>
                                    <p class="card-text">
                                        <strong>Tipo Ingreso:</strong> {{ $alquiler->tipoingreso }}<br>
                                        <strong>Tipo Pago:</strong> {{ $alquiler->tipopago }}<br>
                                        <strong>Aire Acondicionado:</strong>
                                        {{ $alquiler->aireacondicionado ? 'Sí' : 'No' }}<br>
                                        <strong>Habitación:</strong>
                                        {{ $alquiler->habitacion ? $alquiler->habitacion->habitacion : 'Sin asignar' }}<br>
                                        <strong>Consumo:</strong>
                                        {{-- Dentro del <p class="card-text"> Consumo: --}}
                                        @php
                                            $detalleInventario = json_decode($alquiler->inventario_detalle, true);
                                        @endphp

                                        @if (is_array($detalleInventario) && !empty($detalleInventario))
                                            <ul>
                                                @foreach ($detalleInventario as $inventarioId => $item)
                                                    @php
                                                        // En registros nuevos $item['id'] existe; en los viejos usamos la clave
                                                        $id = $item['id'] ?? $inventarioId;

                                                        $inventario = \App\Models\Inventario::find($id);
                                                        $nombre =
                                                            $inventario?->articulo ??
                                                            ($item['articulo'] ?? 'Artículo sin nombre');
                                                        $cantidad = $item['cantidad'] ?? 0;
                                                    @endphp
                                                    <li>{{ $nombre }}: {{ $cantidad }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            Sin consumo registrado
                                        @endif

                                        <br> <strong>Entrada:</strong> {{ $alquiler->entrada }}<br>
                                        <strong>Salida:</strong> {{ $alquiler->salida }}<br>
                                        <strong>Tiempo Transcurrido:</strong>
                                        {{ $alquiler->tiempo_transcurrido }}<br>
                                        <strong>Horas:</strong> {{ $alquiler->horas }}<br>
                                        <strong>Tarifa Seleccionada:</strong>
                                        {{ $alquiler->tarifa_seleccionada }}<br>
                                        <strong>Total:</strong> {{ $alquiler->total }}<br>
                                        <strong>Estado:</strong>
                                        <span
                                            class="badge {{ $alquiler->estado == 'pagado' ? 'bg-success' : 'bg-warning' }}">
                                            {{ ucfirst($alquiler->estado) }}
                                        </span>
                                    </p>
                                    <div class="d-flex justify-content-between">
                                        @if ($alquiler->estado !== 'pagado')
                                            <button class="btn btn-primary btn-sm"
                                                wire:click="openPayModal({{ $alquiler->id }})">Pagar</button>
                                            <button class="btn btn-info btn-sm"
                                                wire:click="openEditModal({{ $alquiler->id }})">Editar</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>


                </div>
            </div>
        </div>
    </div>


    <!-- Modal para pagar habitación -->
    <div wire:ignore.self class="modal fade" id="payAlquilerModal" tabindex="-1"
        aria-labelledby="payAlquilerModalLabel" aria-hidden="true">
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
                            <input type="datetime-local" class="form-control" id="horaSalida" wire:model="horaSalida"
                                readonly>
                        </div>

                        <div class="mb-3">
                            <label for="tipopago" class="form-label">Tipo Pago</label>
                            <select class="form-control" id="tipopago" wire:model="tipopago">
                                <option value="">Seleccione el tipo de pago</option>
                                <option value="EFECTIVO">EFECTIVO</option>
                                <option value="QR">QR</option>
                                <option value="TARJETA">TARJETA</option>
                            </select>
                            @error('tipopago')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="inventarios" class="form-label">Agregar Inventario</label>
                            <select id="inventarios" class="form-control" wire:model="selectedInventarioId"
                                wire:change="addInventario">
                                <option value="">Seleccione o busque un inventario</option>
                                @foreach ($inventarios as $inventario)
                                    <option value="{{ $inventario->id }}">
                                        {{ $inventario->articulo }} - Precio: {{ $inventario->precio }} (Stock:
                                        {{ $inventario->stock }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="inventario-list" class="form-label">Lista de Inventarios Consumidos</label>
                            <ul>
                                @foreach ($selectedInventarios as $id => $item)
                                    <li>
                                        {{-- Muestra el artículo y su precio --}}
                                        {{ $item['articulo'] ?? 'Sin nombre' }} (Precio: {{ $item['precio'] ?? 0 }})
                                        - Cantidad:
                                        <input type="number"
                                            wire:model="selectedInventarios.{{ $id }}.cantidad"
                                            min="1" max="{{ $item['stock'] ?? 0 }}">

                                        <button type="button" class="btn btn-danger btn-sm"
                                            wire:click="removeInventario({{ $id }})">
                                            Eliminar
                                        </button>
                                    </li>
                                @endforeach

                            </ul>
                        </div>

                        <div class="mb-3">
                            <label for="tarifaSeleccionada" class="form-label">Seleccione Tarifa</label>
                            <select class="form-control" id="tarifaSeleccionada" wire:model="tarifaSeleccionada">
                                <option value="">Seleccione una Tarifa Nocturna</option>
                                @if ($esHorarioNocturno)
                                    <option value="tarifa_opcion1">Tarifa Opción 1:
                                        {{ $selectedAlquiler->habitacion->tarifa_opcion1 ?? 'N/A' }}</option>
                                    <option value="tarifa_opcion2">Tarifa Opción 2:
                                        {{ $selectedAlquiler->habitacion->tarifa_opcion2 ?? 'N/A' }}</option>
                                    <option value="tarifa_opcion3">Tarifa Opción 3:
                                        {{ $selectedAlquiler->habitacion->tarifa_opcion3 ?? 'N/A' }}</option>
                                    <option value="tarifa_opcion4">Tarifa Opción 4:
                                        {{ $selectedAlquiler->habitacion->tarifa_opcion4 ?? 'N/A' }}</option>
                                @endif
                            </select>
                            @error('tarifaSeleccionada')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="tiempoTranscurrido" class="form-label">Tiempo Transcurrido</label>
                            <input type="text" class="form-control" id="tiempoTranscurrido" readonly
                                value="@if (isset($selectedAlquiler) && $selectedAlquiler && $horaSalida) {{ floor((strtotime($horaSalida) - strtotime($selectedAlquiler->entrada)) / 3600) }} horas y
                                        {{ floor(((strtotime($horaSalida) - strtotime($selectedAlquiler->entrada)) % 3600) / 60) }} minutos
                                    @else
                                        N/A @endif">
                        </div>

                        <!-- en el modal de pago -->
                        <div class="mb-3">
                            <label for="totalHoras" class="form-label">Total por Horas (con Aire)</label>
                            <input type="text" id="totalHoras" class="form-control"
                                value="Bs {{ $totalHoras }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="totalInventario" class="form-label">Total de Inventario</label>
                            <input type="text" id="totalInventario" class="form-control"
                                value="Bs {{ $totalInventario }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="totalGeneral" class="form-label">Total General</label>
                            <input type="text" id="totalGeneral" class="form-control"
                                value="Bs {{ $totalGeneral }}" readonly>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click="closeCreateModal">Cerrar</button>
                    <button type="button" class="btn btn-primary" wire:click="pay">Guardar</button>
                </div>
            </div>
        </div>
    </div>



    <!-- Modal Crear Alquiler -->
    <div wire:ignore.self class="modal fade" id="createAlquilerModal" tabindex="-1"
        aria-labelledby="createAlquilerModalLabel" aria-hidden="true">
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
                            @error('tipoingreso')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>



                        <!-- <div class="mb-3">
                        <label for="aireacondicionado" class="form-label">Aire Acondicionado</label>
                        <input type="checkbox" id="aireacondicionado" wire:model="aireacondicionado">
                    </div> -->
                        <div class="mb-3">
                            <label for="entrada" class="form-label">Hora de Entrada</label>
                            <input type="datetime-local" class="form-control" id="entrada" wire:model="entrada">
                        </div>

                        <div class="mb-3">
                            <label for="habitacion_id" class="form-label">Habitación</label>
                            <select class="form-control" id="habitacion_id" wire:model="habitacion_id">
                                <option value="">Seleccione una habitación</option>
                                @foreach ($habitaciones as $habitacion)
                                    <option value="{{ $habitacion->id }}">{{ $habitacion->habitacion }}</option>
                                @endforeach
                            </select>
                            @error('habitacion_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>





                        <div class="mb-3">
                            <label for="total" class="form-label">Total</label>
                            <input type="number" class="form-control" id="total" wire:model="total" readonly>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click="closeCreateModal">Cerrar</button>
                    <button type="button" class="btn btn-primary" wire:click="store">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Alquiler -->
    <div wire:ignore.self class="modal fade" id="editAlquilerModal" tabindex="-1"
        aria-labelledby="editAlquilerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAlquilerModalLabel">Editar Alquiler</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form>

                        <div class="mb-3">
                            <label for="aireacondicionado" class="form-label">Aire Acondicionado</label>
                            <input type="checkbox" id="aireacondicionado" wire:model="aireacondicionado"
                                class="form-check-input">
                        </div>

                        <div class="mb-3">
                            <label for="aireInicio" class="form-label">Hora de Inicio del Aire
                                Acondicionado</label>
                            <input type="datetime-local" id="aireInicio" class="form-control"
                                wire:model="aireInicio" readonly>
                        </div>





                        <!-- Inventarios Consumidos -->
                        <div class="mb-3">
                            <label for="inventarios" class="form-label">Agregar Inventario</label>
                            <select id="inventarios" class="form-control" wire:model="selectedInventarioId"
                                wire:change="addInventario">
                                <option value="">Seleccione o busque un inventario</option>
                                @foreach ($inventarios as $inventario)
                                    <option value="{{ $inventario->id }}">
                                        {{ $inventario->articulo }} - Precio: {{ $inventario->precio }} (Stock:
                                        {{ $inventario->stock }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="inventario-list" class="form-label">Lista de Inventarios
                                Consumidos</label>
                            <ul>
                                @foreach ($selectedInventarios as $id => $item)
                                    <li>
                                        {{-- Muestra el artículo y su precio --}}
                                        {{ $item['articulo'] ?? 'Sin nombre' }} (Precio: {{ $item['precio'] ?? 0 }})
                                        - Cantidad:
                                        <input type="number"
                                            wire:model="selectedInventarios.{{ $id }}.cantidad"
                                            min="1" max="{{ $item['stock'] ?? 0 }}">

                                        <button type="button" class="btn btn-danger btn-sm"
                                            wire:click="removeInventario({{ $id }})">
                                            Eliminar
                                        </button>
                                    </li>
                                @endforeach

                            </ul>
                        </div>


                        <!-- Total -->
                        <div class="mb-3">
                            <label for="totalInventario" class="form-label">Total de Artículos</label>
                            <input type="number" class="form-control" id="totalInventario" wire:model="total"
                                readonly>
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
        window.addEventListener('show-edit-modal', () => {
            let modalEl = document.getElementById('editAlquilerModal');
            let modal = new bootstrap.Modal(modalEl);
            modal.show();
        });

        window.addEventListener('close-modal', () => {
            let modalEl = document.getElementById('editAlquilerModal');
            let modal = new bootstrap.Modal(modalEl);
            modal.hide();
        });
    </script>

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

            // Función para actualizar el tiempo automáticamente en la zona horaria de La Paz
            function updateHoraSalida() {
                const now = new Date();
                const utcOffset = -4 * 60 * 60 * 1000; // UTC -4:00 para La Paz
                const localTime = new Date(now.getTime() + utcOffset);
                const formattedDateTime = localTime.toISOString().slice(0, 16); // Formato para datetime-local
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
        document.addEventListener('livewire:load', () => {
            Livewire.on('close-modal', () => {
                let editModal = document.getElementById('editAlquilerModal');
                let payModal = document.getElementById('payAlquilerModal');

                if (editModal) {
                    let modalInstance = bootstrap.Modal.getInstance(editModal);
                    if (modalInstance) modalInstance.hide();
                }

                if (payModal) {
                    let modalInstance = bootstrap.Modal.getInstance(payModal);
                    if (modalInstance) modalInstance.hide();
                }
            });
        });
        document.addEventListener('livewire:load', () => {
            Livewire.on('close-modal', () => {
                const modals = ['payAlquilerModal', 'editAlquilerModal'];
                modals.forEach(modalId => {
                    const modalEl = document.getElementById(modalId);
                    if (modalEl) {
                        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap
                            .Modal(modalEl);
                        modalInstance.hide();
                    }
                });
            });
        });
    </script>
