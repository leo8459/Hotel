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

    {{-- ===========================
         Alquileres cobrados
       =========================== --}}
    <h3>Alquileres cobrados:</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Habitación</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Tipo de Pago</th> {{-- ➕ NUEVO --}}
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalesHabitaciones = [];
                $resumenPagos = []; // tipo => ['cantidad' => n, 'total' => Bs]
            @endphp

            @foreach($alquileres as $index => $alquiler)
                @php
                    $habitacion = $alquiler->habitacion->habitacion ?? 'SIN DATO';
                    $totalesHabitaciones[$habitacion] = ($totalesHabitaciones[$habitacion] ?? 0) + (float) ($alquiler->total ?? 0);

                    $tipoPago = strtoupper($alquiler->tipopago ?? 'SIN DATO');
                    $resumenPagos[$tipoPago]['cantidad'] = ($resumenPagos[$tipoPago]['cantidad'] ?? 0) + 1;
                    $resumenPagos[$tipoPago]['total']    = ($resumenPagos[$tipoPago]['total'] ?? 0) + (float) ($alquiler->total ?? 0);
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $habitacion }}</td>
                    <td>{{ $alquiler->entrada }}</td>
                    <td>{{ $alquiler->salida }}</td>
                    <td>{{ $tipoPago }}</td> {{-- ➕ NUEVO --}}
                    <td>Bs {{ number_format((float) ($alquiler->total ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ===========================
         Total por Habitación
       =========================== --}}
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
                    <td>Bs {{ number_format((float) $total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ===========================
         Productos Consumidos
       =========================== --}}
    <h3>Productos Consumidos:</h3>
    @php $productos = []; @endphp
    @foreach($alquileres as $alq)
        @php
            $detalles = json_decode($alq->inventario_detalle, true) ?? [];
            foreach ($detalles as $item) {
                $nombre = strtoupper($item['articulo']);
                $cantidad = (int) $item['cantidad'];
                $precio = (float) $item['precio'];
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
                    <td>Bs {{ number_format((float) $info['total'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ===========================
         Resumen por Tipo de Pago
       =========================== --}}
    <h3>Resumen por Tipo de Pago:</h3>
    <table>
        <thead>
            <tr>
                <th>Tipo de Pago</th>
                <th>Cantidad</th>
                <th>Total Generado</th>
                <th>Promedio por Operación</th>
                <th>% del Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalGeneralNum = (float) ($totalGenerado ?? 0);
            @endphp
            @foreach($resumenPagos as $tipo => $data)
                @php
                    $cant   = (int) ($data['cantidad'] ?? 0);
                    $total  = (float) ($data['total'] ?? 0);
                    $prom   = $cant > 0 ? $total / $cant : 0;
                    $pct    = $totalGeneralNum > 0 ? ($total * 100 / $totalGeneralNum) : 0;
                @endphp
                <tr>
                    <td>{{ $tipo }}</td>
                    <td>{{ $cant }}</td>
                    <td>Bs {{ number_format($total, 2) }}</td>
                    <td>Bs {{ number_format($prom, 2) }}</td>
                    <td>{{ number_format($pct, 2) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Total General: Bs {{ number_format((float) $totalGenerado, 2) }}</h3>
</body>
</html>
