<div class="container py-4" style="max-width:600px">
    <h3 class="fw-bold mb-3 text-primary">Crear Alquiler</h3>

    <div class="card shadow-sm">
        <div class="card-body">

            {{-- Tipo ingreso --}}
            <div class="mb-3">
                <label class="form-label">Tipo Ingreso</label>
                <select wire:model.defer="tipoingreso" class="form-select">
                    <option value="">Seleccione el tipo de ingreso</option>
                    @foreach($tiposIngreso as $op)
                        <option value="{{ $op }}">{{ $op }}</option>
                    @endforeach
                </select>
                @error('tipoingreso') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Hora de entrada --}}
            <div class="mb-3">
                <label class="form-label">Hora de Entrada</label>
                <input type="datetime-local" wire:model.defer="entrada" class="form-control">
                @error('entrada') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Habitación --}}
            <div class="mb-3">
                <label class="form-label">Habitación</label>
                <select wire:model="habitacion_id" class="form-select">
                    <option value="">Seleccione una habitación</option>
                    @foreach($habitacionesLibres as $h)
                        <option value="{{ $h->id }}">
                            {{ $h->habitacion }} (Bs {{ number_format($h->preciohora,2) }}/h)
                        </option>
                    @endforeach
                </select>
                @error('habitacion_id') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Aire acondicionado --}}
            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" role="switch" id="aire"
                       wire:model.defer="aireacondicionado">
                <label class="form-check-label" for="aire">Aire Acondicionado</label>
            </div>

            {{-- Botones --}}
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('crear-alquiler') }}" class="btn btn-secondary">Cancelar</a>
                <button class="btn btn-primary" wire:click="guardar">Guardar</button>
            </div>
        </div>
    </div>
</div>
