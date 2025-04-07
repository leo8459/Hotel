<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boleta de Pago</title>
    <style>
        /* Agrega tu CSS en línea o estilos simples */
        body {
            font-family: sans-serif;
            font-size: 14px;
        }
        .header, .footer {
            text-align: center;
        }
        .table-inventario {
            width: 100%;
            border-collapse: collapse;
        }
        .table-inventario th, .table-inventario td {
            border: 1px solid #ccc;
            padding: 5px;
        }
        .totales {
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>Boleta de Pago</h2>
        <p>Fecha de pago: {{ $fechaPago }}</p>
        <p>Alquiler #{{ $alquiler->id }}</p>
    </div>

    <hr>

    <p><strong>Tipo de Ingreso:</strong> {{ $alquiler->tipoingreso }}</p>
    <p><strong>Tipo de Pago:</strong> {{ $alquiler->tipopago }}</p>
    <p><strong>Habitación:</strong> 
        {{ optional($alquiler->habitacion)->habitacion ?? 'Sin asignar' }}
    </p>
    <p><strong>Entrada:</strong> {{ $alquiler->entrada }}</p>
    <p><strong>Salida:</strong> {{ $alquiler->salida }}</p>
    
    <hr>

    <h4>Detalle de Consumos</h4>
    @php
        // $detalleInventario es un array con [id, articulo?, cantidad, ...]
    @endphp

    @if (count($detalleInventario) > 0)
        <table class="table-inventario">
            <thead>
                <tr>
                    <th>Artículo</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($detalleInventario as $item)
                    @php
                        $inv = \App\Models\Inventario::find($item['id']);
                        $nombre   = $inv->articulo ?? 'Sin nombre';
                        $cantidad = $item['cantidad'] ?? 0;
                        $precio   = $inv->precio ?? 0;
                        $subtotal = $precio * $cantidad;
                    @endphp
                    <tr>
                        <td>{{ $nombre }}</td>
                        <td>{{ $cantidad }}</td>
                        <td>Bs {{ number_format($precio, 2) }}</td>
                        <td>Bs {{ number_format($subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Sin consumos de productos.</p>
    @endif

    <div class="totales">
        <p><strong>Total Consumido (Productos):</strong> Bs {{ number_format($totalInventario, 2) }}</p>
        <p><strong>Total Habitacion (Horas + Aire):</strong> Bs {{ number_format($totalHabitacion, 2) }}</p>
        <hr>
        <h3>Total Pagado: Bs {{ number_format($alquiler->total, 2) }}</h3>
    </div>

    <hr>

    <div class="footer">
        <p>¡Gracias por su preferencia!</p>
    </div>

</body>
</html>
