<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Eventos</title>
    <style>
        body  { font-family: Arial, sans-serif; font-size: 12px; }
        h2, p { text-align: center; margin: 0; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        thead { background-color: #f2f2f2; }

        .rojo  { background-color: #f8d7da; }  /* Total Ingresado  */
        .verde { background-color: #d4edda; }  /* Total Vendido   */
    </style>
</head>
<body>
    <h2>Reporte Consolidado – Inventario vs Ventas</h2>
    <p>Generado: {{ now('America/La_Paz')->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Artículo</th>
                <th>Stock Ingresado</th>
                <th>Vendido</th>
                <th>Precio Unitario (Bs)</th>
                <th class="rojo">Total Ingresado (Bs)</th>
                <th class="verde">Total Vendido (Bs)</th>
                <th>Diferencia (Bs)</th>
                <th>Últ. Movimiento</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($eventos as $i => $e)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $e->articulo }}</td>
                    <td>{{ $e->stock_ingresado }}</td>
                    <td>{{ $e->vendido }}</td>
                    <td>{{ number_format($e->precio_unitario, 2) }}</td>
                    <td class="rojo">{{ number_format($e->total_ingresado, 2) }}</td>
                    <td class="verde">{{ number_format($e->total_vendido, 2) }}</td>
                    <td>{{ number_format($e->diferencia, 2) }}</td>
                    <td>{{ $e->fecha }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
