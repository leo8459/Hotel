<div class="container py-4" style="max-width:480px">
    <h2 class="fw-bold mb-4 text-primary">
        Cambiar estado – Habitación {{ $habitacion->habitacion }}
    </h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-semibold">Nuevo estado</label>
                <select wire:model.defer="nuevoEstado" class="form-select">
                    <option value="">-- Selecciona un estado --</option>
                    @foreach ($estadosDisponibles as $estado)
                        <option value="{{ $estado }}">{{ $estado }}</option>
                    @endforeach
                </select>
                @error('nuevoEstado') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <button class="btn btn-primary" wire:click="guardar">
                Guardar <i class="bi bi-check-lg ms-1"></i>
            </button>

            <a href="{{ route('crear-alquiler') }}" class="btn btn-secondary ms-2">
                Cancelar
            </a>
        </div>
    </div>
</div>
