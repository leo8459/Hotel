<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen Detallado de Alquileres</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        h2, h3 { margin-bottom: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        .encabezado { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Reporte de Ingresos por Alquiler</h2>
    <p><strong>Fecha del Reporte:</strong> {{ $fechaHoy }}</p>
    <p><strong>Rango:</strong> {{ $fechaInicio }} a {{ $fechaFin }}</p>

    <h3>Resumen General</h3>
    <table>
        <tr>
            <td><strong>Total Recaudado (Bs):</strong></td>
            <td>{{ number_format($totalGeneral, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Total Alquileres Pagados:</strong></td>
            <td>{{ count($alquileres) }}</td>
        </tr>
        <tr>
            <td><strong>Total Habitaciones Distintas:</strong></td>
            <td>{{ count($resumenPorHabitacion) }}</td>
        </tr>
        <tr>
            <td><strong>Total Productos Distintos:</strong></td>
            <td>{{ count($resumenProductos) }}</td>
        </tr>
    </table>

    <h3>Resumen por Producto</h3>
    <table>
        <thead class="encabezado">
            <tr>
                <th>Producto</th>
                <th>Total Consumido</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($resumenProductos as $producto => $cantidad)
                <tr>
                    <td>{{ $producto }}</td>
                    <td>{{ $cantidad }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Resumen por Habitación</h3>
    <table>
        <thead class="encabezado">
            <tr>
                <th>Habitación</th>
                <th>Total Generado (Bs)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($resumenPorHabitacion as $habitacion => $monto)
                <tr>
                    <td>{{ $habitacion }}</td>
                    <td>{{ number_format($monto, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Detalle de Alquileres</h3>
    <table>
        <thead class="encabezado">
            <tr>
                <th>Fecha</th>
                <th>Habitación</th>
                <th>Usuario</th>
                <th>Consumo</th>
                <th>Total (Bs)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($alquileres as $a)
                @php
                    $inventarios = json_decode($a->inventario_detalle, true) ?? [];
                    $consumo = collect($inventarios)->map(function ($item) {
                        $inv = \App\Models\Inventario::find($item['id']);
                        return $inv ? $inv->articulo . ' x' . $item['cantidad'] : null;
                    })->filter()->implode(', ');
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($a->updated_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ optional($a->habitacion)->habitacion }}</td>
                    <td>{{ optional($a->usuario)->name ?? 'N/A' }}</td>
                    <td>{{ $consumo ?: 'Sin consumo' }}</td>
                    <td>{{ number_format($a->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
