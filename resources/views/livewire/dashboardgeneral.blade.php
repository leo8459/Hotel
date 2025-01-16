<div class="dashboard">
    <style>
        .dashboard {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            align-items: flex-start; /* Alinea todo a la izquierda */
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
            text-align: left; /* Alinea los títulos a la izquierda */
        }

        .horizontal-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: flex-start; /* Alinea las tarjetas a la izquierda */
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
            align-items: flex-start; /* Alinea el contenido de las tarjetas a la izquierda */
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

        .icon-generated {
            background: linear-gradient(45deg, #3F51B5, #2196F3);
        }

        .icon-alquilado {
            background: linear-gradient(45deg, #FF5722, #FFC107);
        }

        .icon-libre {
            background: linear-gradient(45deg, #4CAF50, #8BC34A);
        }

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
            width: 150px;
        }

        .room-card:hover {
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
    </style>

    <!-- Total Generado -->
    <div class="card-container">
        <div class="card-content">
            <p class="card-title">Total Generado</p>
            <p class="card-value">Bs{{ number_format($totalGenerado, 2) }}</p>
        </div>
        <div class="card-icon icon-generated">💰</div>
    </div>

    <!-- Habitaciones Alquiladas -->
    <div class="section-container">
        <div class="section-title">Habitaciones Alquiladas ({{ count($habitacionesAlquiladas) }})</div>
        <div class="horizontal-container">
            @forelse ($habitacionesAlquiladas as $habitacion)
                <div class="room-card">
                    <div class="room-icon icon-alquilado">🏠</div>
                    <div class="card-title">Habitación {{ $habitacion->habitacion }}</div>
                </div>
            @empty
                <p>No hay habitaciones alquiladas.</p>
            @endforelse
        </div>
    </div>

    <!-- Habitaciones Libres -->
    <div class="section-container">
        <div class="section-title">Habitaciones Libres ({{ count($habitacionesLibres) }})</div>
        <div class="horizontal-container">
            @forelse ($habitacionesLibres as $habitacion)
                <div class="room-card">
                    <div class="room-icon icon-libre">🛏️</div>
                    <div class="card-title">Habitación {{ $habitacion->habitacion }}</div>
                </div>
            @empty
                <p>No hay habitaciones libres.</p>
            @endforelse
        </div>
    </div>
</div>
