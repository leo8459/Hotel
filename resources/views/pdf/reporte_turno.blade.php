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
            @foreach($alquileres as $index => $alquiler)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $alquiler->habitacion->habitacion }}</td>
                    <td>{{ $alquiler->entrada }}</td>
                    <td>{{ $alquiler->salida }}</td>
                    <td>Bs {{ number_format($alquiler->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <h3>Total Generado: Bs {{ number_format($totalGenerado, 2) }}</h3>
</body>
</html>
