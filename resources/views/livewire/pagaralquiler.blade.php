<div class="container-fluid py-3">
    <h3>Pagar Habitación</h3>

    @if (session()->has('message'))
        <x-adminlte-alert theme="success" dismissable>{{ session('message') }}</x-adminlte-alert>
    @endif

    <div class="card">
        <div class="card-body">

            {{-- Hora salida --}}
            <x-adminlte-input
                name="horaSalida"
                label="Hora de Salida"
                type="datetime-local"
                igroup-size="md"
                wire:model.defer="horaSalida"
                disabled />

            {{-- Tipo pago --}}
            <x-adminlte-select name="tipopago" label="Tipo Pago" wire:model.defer="tipopago">
                <option value="">Seleccione el tipo de pago</option>
                <option value="EFECTIVO">EFECTIVO</option>
                <option value="QR">QR</option>
                <option value="TARJETA">TARJETA</option>
            </x-adminlte-select>
            @error('tipopago') <small class="text-danger">{{ $message }}</small> @enderror

            {{-- Selector inventario --}}
            <x-adminlte-select name="inv" label="Agregar Inventario" wire:model="selectedInventarioId" wire:change="addInventario">
                <option value="">Seleccione o busque un inventario</option>
                @foreach ($inventariosDisponibles as $inv)
                    <option value="{{ $inv->id }}">
                        {{ $inv->articulo }} - Precio: {{ $inv->precio }} (Stock: {{ $inv->stock }})
                    </option>
                @endforeach
            </x-adminlte-select>

            {{-- Lista inventario --}}
            <label class="mt-2 fw-bold">Lista de Inventarios Consumidos</label>
            <ul class="list-unstyled">
                @foreach ($selectedInventarios as $id => $item)
                    <li class="mb-1">
                        <strong>{{ $item['articulo'] }}</strong>
                        (Precio: {{ $item['precio'] }})
                        —
                        Cant:
                        <input type="number"
                               min="1"
                               max="{{ $item['stock'] }}"
                               style="width:70px"
                               wire:model.lazy="selectedInventarios.{{ $id }}.cantidad">
                        <button class="btn btn-danger btn-xs"
                                wire:click="removeInventario({{ $id }})">
                            Eliminar
                        </button>
                    </li>
                @endforeach
            </ul>

            {{-- Totales --}}
            <x-adminlte-input name="totalHoras" label="Total por Horas (con Aire)" :value="'Bs '.$totalHoras" disabled/>
            <x-adminlte-input name="totalInventario" label="Total de Inventario" :value="'Bs '.$totalInventario" disabled/>
            <x-adminlte-input name="totalGeneral" label="Total General" :value="'Bs '.$totalGeneral" disabled/>

            {{-- Botones --}}
            <div class="d-flex justify-content-end">
<a href="{{ route('crear-alquiler') }}" class="btn btn-secondary me-2">
    Cancelar
</a>
                <button class="btn btn-primary" wire:click="pay">Guardar</button>
            </div>
        </div>
    </div>
</div>
