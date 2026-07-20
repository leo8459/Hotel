<div class="dashboard" wire:poll.15s>
    <style>
        .dashboard {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            align-items: flex-start;
        }

        .section-container {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 100%;
            max-width: 1200px;
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 10px;
            text-align: left;
        }

        .horizontal-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: flex-start;
        }

        .card-container {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            width: 300px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .card-content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .card-title {
            font-size: 1rem;
            font-weight: bold;
            color: #555;
            margin: 0;
        }

        .card-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .icon-generated { background: linear-gradient(45deg, #3F51B5, #2196F3); }
        .icon-uso { background: linear-gradient(45deg, #FF5722, #FFC107); }
        .icon-disponible { background: linear-gradient(45deg, #4CAF50, #8BC34A); }
        .icon-limpieza { background: linear-gradient(45deg, #2196F3, #03A9F4); }
        .icon-mantenimiento { background: linear-gradient(45deg, #9E9E9E, #616161); }
        .icon-pagado { background: linear-gradient(45deg, #FFD54F, #FFB300); }

        .room-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 15px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 170px;
            cursor: pointer;
        }

        .room-card:hover,
        .room-card.is-active {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .room-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 8px;
        }

        .room-status {
            font-size: 0.85rem;
            color: #777;
            margin-top: 4px;
        }

        .action-panel {
            margin-top: 10px;
            width: 100%;
            border-top: 1px solid #f0f0f0;
            padding-top: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .action-panel .btn {
            width: 100%;
        }
    </style>

    @if (session()->has('message'))
        <div class="alert alert-success w-100" style="max-width: 1200px;">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger w-100" style="max-width: 1200px;">
            {{ session('error') }}
        </div>
    @endif

    <div class="card-container">
        <div class="card-content">
            <p class="card-title">Total Generado</p>
            <p class="card-value">Bs{{ number_format($totalGenerado, 2) }}</p>
        </div>
        <div class="card-icon icon-generated">$</div>
    </div>

    @foreach ($sections as $section)
        <div class="section-container">
            <div class="section-title">{{ $section['title'] }} ({{ count($section['rooms']) }})</div>
            <div class="horizontal-container">
                @forelse ($section['rooms'] as $habitacion)
                    @php
                        $alquiler = $alquileresActivos[$habitacion->id] ?? null;
                        $bloqueado = in_array($habitacion->estado_texto, ['En uso', 'En limpieza', 'Mantenimiento', 'Pagado']);
                        $selected = $selectedHabitacionId === $habitacion->id;
                    @endphp

                    <div class="room-card {{ $selected ? 'is-active' : '' }}"
                         wire:click="toggleHabitacion({{ $habitacion->id }})">
                        <div class="room-icon {{ $section['iconClass'] }}">{{ $section['icon'] }}</div>
                        <div class="card-title">Hab. {{ $habitacion->habitacion }}</div>
                        <div class="room-status">{{ $habitacion->estado_texto }}</div>

                        @if ($selected)
                            <div class="action-panel">
                                @if (!$turnoActivo)
                                    <button class="btn btn-secondary btn-sm" disabled>
                                        Inicia tu turno
                                    </button>
                                @else
                                    <a href="{{ route('habitacion.estado', $habitacion->id) }}"
                                       class="btn btn-outline-primary btn-sm"
                                       onclick="event.stopPropagation();">
                                        Estado
                                    </a>

                                    @if (!$bloqueado)
                                    <a href="{{ route('alquiler.crear', $habitacion->id) }}"
                                       class="btn btn-success btn-sm"
                                       onclick="event.stopPropagation();">
                                        Alquilar
                                    </a>
                                    @else
                                        <button class="btn btn-secondary btn-sm" disabled>
                                            No disponible
                                        </button>
                                    @endif

                                    @if ($alquiler)
                                        <a href="{{ route('editar-alquiler', $alquiler->id) }}"
                                           class="btn btn-warning btn-sm"
                                           onclick="event.stopPropagation();">
                                            Anadir producto
                                        </a>

                                        <a href="{{ route('pagar-alquiler', $alquiler->id) }}"
                                           class="btn btn-success btn-sm"
                                           onclick="event.stopPropagation();">
                                            Pagar alquiler
                                        </a>
                                    @endif

                                    @if ($alquiler && $habitacion->estado_texto === 'En uso')
                                        <button type="button"
                                                class="btn btn-danger btn-sm"
                                                wire:click.stop="cancelarAlquiler({{ $alquiler->id }})">
                                            Cancelar alquiler
                                        </button>
                                    @endif
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <p>{{ $section['empty'] }}</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
