<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Turno</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h3 { margin-top: 30px; }
    </style>
</head>
<body>
    <h2>Reporte de Turno</h2>
    <p><strong>Usuario:</strong> {{ $usuario->name }}</p>
    <p><strong>Inicio de Turno:</strong> {{ $usuario->hora_entrada_trabajo }}</p>
    <p><strong>Fin de Turno:</strong> {{ $usuario->hora_salida_trabajo }}</p>

    <h3>Alquileres cobrados:</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Habitación</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @php $totalesHabitaciones = []; @endphp
            @foreach($alquileres as $index => $alquiler)
                @php
                    $habitacion = $alquiler->habitacion->habitacion;
                    $totalesHabitaciones[$habitacion] = ($totalesHabitaciones[$habitacion] ?? 0) + $alquiler->total;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $habitacion }}</td>
                    <td>{{ $alquiler->entrada }}</td>
                    <td>{{ $alquiler->salida }}</td>
                    <td>Bs {{ number_format($alquiler->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Total por Habitación:</h3>
    <table>
        <thead>
            <tr>
                <th>Habitación</th>
                <th>Total Generado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($totalesHabitaciones as $hab => $total)
                <tr>
                    <td>{{ $hab }}</td>
                    <td>Bs {{ number_format($total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Productos Consumidos:</h3>
    @php $productos = []; @endphp
    @foreach($alquileres as $alq)
        @php
            $detalles = json_decode($alq->inventario_detalle, true) ?? [];
            foreach ($detalles as $item) {
                $nombre = strtoupper($item['articulo']);
                $cantidad = $item['cantidad'];
                $precio = $item['precio'];
                $productos[$nombre]['cantidad'] = ($productos[$nombre]['cantidad'] ?? 0) + $cantidad;
                $productos[$nombre]['total'] = ($productos[$nombre]['total'] ?? 0) + ($cantidad * $precio);
            }
        @endphp
    @endforeach

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad Total</th>
                <th>Total Generado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productos as $producto => $info)
                <tr>
                    <td>{{ $producto }}</td>
                    <td>{{ $info['cantidad'] }}</td>
                    <td>Bs {{ number_format($info['total'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Total General: Bs {{ number_format($totalGenerado, 2) }}</h3>
</body>
</html>
