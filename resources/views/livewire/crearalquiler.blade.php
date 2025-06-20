<div>
    <div class="container py-4">
        <h2 class="fw-bold mb-4 text-secondary">HABITACIONES</h2>

        @if (session()->has('estadoActualizado'))
            <div class="alert alert-success">{{ session('estadoActualizado') }}</div>
        @endif

        <div class="row g-4">
            @foreach ($habitaciones as $hab)
                @php
                    $badgeClass = match ($hab->estado_texto) {
                        'Disponible' => 'bg-light text-success',
                        'En uso' => 'bg-light text-danger',
                        'Pagado' => 'bg-light text-primary',
                        'En limpieza' => 'bg-light text-warning',
                        'Mantenimiento' => 'bg-light text-secondary',
                        default => 'bg-light text-secondary',
                    };
                @endphp
                @php
                    $alquiler = \App\Models\Alquiler::where('habitacion_id', $hab->id)
                        ->where('estado', 'alquilado')
                        ->latest()
                        ->first();
                @endphp


                <div class="col-12 col-sm-6 col-lg-3">
                    <div wire:click="alquilar({{ $hab->id }})" class="house-card {{ $hab->color }} shadow"
                        style="cursor:pointer;">

                        <span class="badge rounded-pill position-absolute top-0 end-0 m-2 {{ $badgeClass }}">
                            {{ $hab->estado_texto }}
                        </span>

                        <div
                            class="card-body d-flex flex-column justify-content-center align-items-center text-center px-3 mt-3">
                            <i class="bi bi-house-fill display-5 mb-2 opacity-75"></i>
                            <h4 class="fw-bold">{{ $hab->habitacion }}</h4>
                            <small class="fst-italic">{{ ucfirst($hab->tipo) }}</small>

                            <hr class="border-white opacity-50 w-100">
                            <div class="small">
                                <div><i class="bi bi-clock me-1"></i>Hora: Bs {{ number_format($hab->preciohora, 2) }}
                                </div>
                                <div><i class="bi bi-cash-coin me-1"></i>Extra: Bs
                                    {{ number_format($hab->precio_extra, 2) }}</div>
                                @if ($hab->tarifa_opcion1)
                                    <span class="badge bg-warning text-dark mt-1 d-inline-block">
                                        Opción 1: Bs {{ number_format($hab->tarifa_opcion1, 2) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="text-center py-2 bg-light">
                            <a href="{{ route('habitacion.estado', $hab->id) }}"
                                class="btn btn-outline-primary btn-sm fw-bold">
                                Estado
                            </a>

                        </div>
                        <div class="hotel-footer bg-light text-center py-2">
                            {{-- reemplazar "Estado" por "Alquilar" --}}
                            <a href="{{ route('alquiler.crear', $hab->id) }}"
                                class="btn btn-outline-light text-dark btn-sm fw-bold">
                                Alquilar
                            </a>
                            {{-- Nuevo botón para editar alquiler --}}
                            @if ($alquiler)
                                <a href="{{ route('editar-alquiler', $alquiler->id) }}"
                                    class="btn btn-outline-warning btn-sm fw-bold mt-1">
                                    Editar alquiler
                                </a>
                            @endif
                            @if ($alquiler)
                                <a href="{{ route('pagar-alquiler', $alquiler->id) }}"
                                    class="btn btn-outline-warning btn-sm fw-bold mt-1">
                                    Pagar alquiler
                                </a>
                            @endif

                        </div>

                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <style>
        .house-card {
            position: relative;
            min-height: 260px;
            border-radius: 0 0 1rem 1rem;
            overflow: hidden;
            transition: .15s transform, .15s box-shadow;
        }

        .house-card::before {
            content: '';
            position: absolute;
            top: -60px;
            left: 0;
            width: 100%;
            height: 60px;
            background: inherit;
            clip-path: polygon(50% 0, 100% 100%, 0 100%);
        }

        .house-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: rgba(0, 0, 0, .15);
            filter: blur(4px);
        }

        .house-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .25);
        }
    </style>
</div>
