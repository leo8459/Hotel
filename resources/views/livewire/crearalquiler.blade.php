{{-- ░░░ Inyectar CSS y JS vía CDN – una sola vez ░░░ --}}
@once
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    @endpush
@endonce


{{-- ░░░ Elemento raíz único para Livewire ░░░ --}}
<div>

    <div class="container py-4">

        <h2 class="fw-bold mb-4 text-primary">Estado de Habitaciones</h2>

        <div class="row g-4">

            @foreach ($habitaciones as $hab)
                <div class="col-12 col-sm-6 col-lg-3">

                    {{-- Casita --}}
                    <div  wire:click="alquilar({{ $hab->id }})"
                          class="house-card {{ $hab->color }} text-white shadow"
                          style="cursor:pointer">

                        {{-- Badge de estado --}}
                        <span class="badge rounded-pill position-absolute top-0 end-0 m-2
                                     {{ $hab->estado ? 'bg-light text-success' : 'bg-light text-danger' }}">
                            {{ $hab->estado_texto }}
                        </span>

                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center px-3 mt-3">

                            <i class="bi bi-house-fill display-5 mb-2 opacity-75"></i>

                            <h4 class="fw-bold">{{ $hab->habitacion }}</h4>
                            <small class="fst-italic">{{ ucfirst($hab->tipo) }}</small>

                            <hr class="border-white opacity-50 w-100">

                            <div class="small">
                                <div><i class="bi bi-clock me-1"></i>Hora: Bs {{ number_format($hab->preciohora, 2) }}</div>
                                <div><i class="bi bi-cash-coin me-1"></i>Extra: Bs {{ number_format($hab->precio_extra, 2) }}</div>

                                @if ($hab->tarifa_opcion1)
                                    <span class="badge bg-warning text-dark mt-1 d-inline-block">
                                        Opción 1: Bs {{ number_format($hab->tarifa_opcion1, 2) }}
                                    </span>
                                @endif
                            </div>

                        </div>
                    </div>
                    {{-- /Casita --}}
                </div>
            @endforeach

        </div>

        {{-- Leyenda --}}
        <div class="mt-4">
            <span class="legend-box bg-success me-2"></span> Libre
            <span class="legend-box bg-danger ms-4 me-2"></span> Ocupada
        </div>

    </div>

    {{-- ░░░ CSS del componente (dentro del mismo root) ░░░ --}}
    <style>
        .house-card{
            position:relative;
            min-height:260px;
            border-radius:0 0 1rem 1rem;
            overflow:hidden;
            transition:.15s transform, .15s box-shadow;
        }
        .house-card::before{                /* techo */
            content:'';
            position:absolute;
            top:-60px; left:0; width:100%; height:60px;
            background:inherit;
            clip-path:polygon(50% 0, 100% 100%, 0 100%);
        }
        .house-card::after{                 /* sombra alero */
            content:'';
            position:absolute;
            top:0; left:0; width:100%; height:6px;
            background:rgba(0,0,0,.15);
            filter:blur(4px);
        }
        .house-card.bg-success:hover{       /* solo libres */
            transform:translateY(-4px) scale(1.02);
            box-shadow:0 .5rem 1rem rgba(0,0,0,.25);
        }
        .legend-box{
            display:inline-block;
            width:18px; height:18px;
            border-radius:4px;
        }
    </style>

</div>
