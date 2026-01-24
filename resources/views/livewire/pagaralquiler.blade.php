<div class="container-fluid py-3">
    <h3>Pagar Habitación</h3>

    @if (session()->has('message'))
        <x-adminlte-alert theme="success" dismissable>{{ session('message') }}</x-adminlte-alert>
    @endif

    @if (session()->has('error'))
        <x-adminlte-alert theme="danger" dismissable>{{ session('error') }}</x-adminlte-alert>
    @endif

    <div class="card">
        <div class="card-body">

            <x-adminlte-input
                name="horaSalida"
                label="Hora de Salida"
                type="datetime-local"
                igroup-size="md"
                wire:model.defer="horaSalida"
                disabled />

            <x-adminlte-select
                name="tarifaSeleccionada"
                label="Tipo de Tarifa"
                wire:change="cambiarTarifa($event.target.value)">
                <option value="HORAS">Por Horas</option>
                <option value="NOCTURNA">Tarifa Nocturna</option>
            </x-adminlte-select>

            <x-adminlte-select name="tipopago" label="Tipo Pago" wire:model.defer="tipopago">
                <option value="">Seleccione el tipo de pago</option>
                <option value="EFECTIVO">EFECTIVO</option>
                <option value="QR">QR</option>
                <option value="TARJETA">TARJETA</option>
            </x-adminlte-select>
            @error('tipopago') <small class="text-danger">{{ $message }}</small> @enderror

            <x-adminlte-select name="inv" label="Agregar Inventario"
                wire:model="selectedInventarioId"
                wire:change="addInventario">
                <option value="">Seleccione o busque un inventario</option>
                @foreach ($inventariosDisponibles as $inv)
                    <option value="{{ $inv->id }}">
                        {{ $inv->articulo }} - Precio: {{ $inv->precio }} (Stock: {{ $inv->stock }})
                    </option>
                @endforeach
            </x-adminlte-select>

            <label class="mt-2 fw-bold">Lista de Inventarios Consumidos</label>
            <ul class="list-unstyled">
                @foreach ($selectedInventarios as $id => $item)
                    <li class="mb-2">
                        <strong>{{ $item['articulo'] }}</strong>
                        (Precio: {{ $item['precio'] }})
                        <br>

                        <small class="text-muted">
                            Stock actual: {{ $item['stock_actual'] }}
                            | Reservado: {{ $item['reservado'] }}
                            | Máximo permitido: {{ $item['max_permitido'] }}
                        </small>

                        <div class="mt-1">
                            Cant:
                            <input type="number"
                                min="1"
                                max="{{ $item['max_permitido'] }}"
                                style="width:90px"
                                wire:model.lazy="selectedInventarios.{{ $id }}.cantidad">

                            <button class="btn btn-danger btn-xs"
                                wire:click="removeInventario({{ $id }})">
                                Eliminar
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>

            <x-adminlte-input name="totalHoras" label="Total por Horas" :value="'Bs '.$totalHoras" disabled />
            <x-adminlte-input name="totalInventario" label="Total de Inventario" :value="'Bs '.$totalInventario" disabled />
            <x-adminlte-input name="totalGeneral" label="Total General" :value="'Bs '.$totalGeneral" disabled />

            <div class="d-flex justify-content-end">
                <a href="{{ route('crear-alquiler') }}" class="btn btn-secondary me-2">Cancelar</a>
                <button class="btn btn-primary" wire:click="pay">Guardar</button>
            </div>
        </div>
    </div>
</div>
