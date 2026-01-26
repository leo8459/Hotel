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
                @if (session()->has('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <!-- Tabla para dispositivos medianos y grandes -->
                <div class="d-none d-md-block">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Habitación</th>
                                <th>Tipo</th>
                                <th>Precio por Hora</th>
                                <th>Precio Extra</th>
                                <th>Tarifa Nocturna</th>
                                <th>Freezer</th>

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
                                 <td>
                                    @php $fz = $habitacion->freezer_stock ?? []; @endphp
                                    @if(count($fz))
                                    {{ count($fz) }} items
                                    @else
                                    —
                                    @endif
                                </td>
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

                <!-- Cards para dispositivos pequeños -->
                <div class="d-md-none">
                    @foreach ($habitaciones as $habitacion)
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Habitación: {{ $habitacion->habitacion }}</h5>
                            <p class="card-text">
                                <strong>Tipo:</strong> {{ $habitacion->tipo }}<br>
                                <strong>Precio por Hora:</strong> {{ $habitacion->preciohora }}<br>
                                <strong>Precio Extra:</strong> {{ $habitacion->precio_extra }}<br>
                                <strong>Tarifa Nocturna:</strong> {{ $habitacion->tarifa_opcion1 }}<br>
                            </p>
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-info btn-sm" wire:click="openEditModal({{ $habitacion->id }})">Editar</button>
                                <button class="btn btn-danger btn-sm" wire:click="delete({{ $habitacion->id }})">Eliminar</button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    <div class="mt-3">
                        {{ $habitaciones->links() }}
                    </div>
                </div>

            </div>
        </section>

        <!-- Modal Crear Habitación -->
        <div wire:ignore.self class="modal fade" id="createAlquilerModal" tabindex="-1" aria-labelledby="createAlquilerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createAlquilerModalLabel">Crear Habitación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body">
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Habitación</label>
                                <input type="text" class="form-control" wire:model="habitacion">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tipo</label>
                                <input type="text" class="form-control" wire:model="tipo">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Precio por Hora</label>
                                <input type="number" class="form-control" wire:model="preciohora">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Precio Extra</label>
                                <input type="number" class="form-control" wire:model="precio_extra">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tarifa Horario Nocturno</label>
                                <input type="number" class="form-control" wire:model="tarifa_opcion1">
                            </div>

                            {{-- ✅ FREEZER --}}
                            <hr>
                            <h5><b>Freezer (stock por habitación)</b></h5>

                            <div class="row g-2">
                                <div class="col-md-8">
                                    <select class="form-control" wire:model="freezerInventarioId">
                                        <option value="">-- Seleccionar inventario --</option>
                                        @foreach($inventariosDisponibles as $inv)
                                        <option value="{{ $inv->id }}">{{ $inv->articulo }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <button type="button" class="btn btn-success w-100" wire:click="addFreezerItem">
                                        + Agregar
                                    </button>
                                </div>
                            </div>

                            @if(!empty($freezer))
                            <div class="mt-3">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Artículo</th>
                                            <th width="120">Cantidad</th>
                                            <th width="80">Quitar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($freezer as $id => $qty)
                                        @php $inv = $inventariosDisponibles->firstWhere('id', (int)$id); @endphp
                                        <tr>
                                            <td>{{ $inv?->articulo ?? 'Sin nombre' }}</td>
                                            <td>
                                                <input type="number" min="0"
                                                    class="form-control form-control-sm"
                                                    wire:model="freezer.{{ $id }}">
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-danger btn-sm" type="button"
                                                    wire:click="removeFreezerItem({{ $id }})">
                                                    X
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif

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
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editAlquilerModalLabel">Editar Habitación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body">
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Habitación</label>
                                <input type="text" class="form-control" wire:model="habitacion">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tipo</label>
                                <input type="text" class="form-control" wire:model="tipo">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Precio por Hora</label>
                                <input type="number" class="form-control" wire:model="preciohora">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Precio Extra</label>
                                <input type="number" class="form-control" wire:model="precio_extra">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tarifa Nocturna</label>
                                <input type="number" class="form-control" wire:model="tarifa_opcion1">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tarifa Opción 2</label>
                                <input type="number" class="form-control" wire:model="tarifa_opcion2">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tarifa Opción 3</label>
                                <input type="number" class="form-control" wire:model="tarifa_opcion3">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tarifa Opción 4</label>
                                <input type="number" class="form-control" wire:model="tarifa_opcion4">
                            </div>

                            {{-- ✅ FREEZER --}}
                            <hr>
                            <h5><b>Freezer (stock por habitación)</b></h5>

                            <div class="row g-2">
                                <div class="col-md-8">
                                    <select class="form-control" wire:model="freezerInventarioId">
                                        <option value="">-- Seleccionar inventario --</option>
                                        @foreach($inventariosDisponibles as $inv)
                                        <option value="{{ $inv->id }}">{{ $inv->articulo }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <button type="button" class="btn btn-success w-100" wire:click="addFreezerItem">
                                        + Agregar
                                    </button>
                                </div>
                            </div>

                            @if(!empty($freezer))
                            <div class="mt-3">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Artículo</th>
                                            <th width="120">Cantidad</th>
                                            <th width="80">Quitar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($freezer as $id => $qty)
                                        @php $inv = $inventariosDisponibles->firstWhere('id', (int)$id); @endphp
                                        <tr>
                                            <td>{{ $inv?->articulo ?? 'Sin nombre' }}</td>
                                            <td>
                                                <input type="number" min="0"
                                                    class="form-control form-control-sm"
                                                    wire:model="freezer.{{ $id }}">
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-danger btn-sm" type="button"
                                                    wire:click="removeFreezerItem({{ $id }})">
                                                    X
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif

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
        bootstrap.Modal.getInstance(modalCreate)?.hide();
        bootstrap.Modal.getInstance(modalEdit)?.hide();
    });
</script>