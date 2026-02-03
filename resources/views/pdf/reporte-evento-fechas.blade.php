<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Movimientos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #444; padding: 4px; }
        th { background: #222; color: #fff; }
        .muted { color: #666; }
    </style>
</head>
<body>

<h2>Reporte de Movimientos de Inventario</h2>
<p class="muted">
    Rango: <b>{{ $inicioTxt }}</b> a <b>{{ $finTxt }}</b>
    @if(!empty($search)) | Filtro: <b>{{ $search }}</b> @endif
</p>
<p class="muted">Generado: {{ now('America/La_Paz')->format('d/m/Y H:i') }}</p>

<h3>Totales por Artículo</h3>
<table>
    <thead>
    <tr>
        <th>Artículo</th>
        <th>Stock Ingresado</th>
        <th>Total Ingresado (Bs)</th>
        <th>Vendido</th>
        <th>Total Vendido (Bs)</th>
        <th>Saldo Stock</th>
        <th>Stock Disponible</th>
        <th>Freezers Stock</th>
        <th>Stock Total</th>
        <th>Precio Unit. Prom. (Bs)</th>
        <th>Último Mov.</th>
    </tr>
    </thead>
    <tbody>
    @foreach($resumen as $r)
        <tr>
            <td>{{ $r->articulo }}</td>
            <td>{{ $r->stock_ingresado }}</td>
            <td>Bs {{ number_format($r->total_ingresado, 2) }}</td>
            <td>{{ $r->vendido }}</td>
        <td>Bs {{ number_format($r->total_vendido, 2) }}</td>
        <td><b>{{ $r->saldo_stock }}</b></td>
        <td>{{ $r->stock_disponible }}</td>
        <td>
            <span style="display:inline-block;padding:2px 6px;border-radius:999px;background:#e6f4ff;color:#0b5ed7;font-weight:700;border:1px solid rgba(13,110,253,.25);">
                {{ $r->freezer_stock }}
            </span>
            @if (!empty($r->freezer_detalle))
                <div class="muted" style="font-weight:600;margin-top:3px;">
                    @foreach ($r->freezer_detalle as $fz)
                        <span style="display:inline-block;margin:2px 4px 0 0;padding:2px 6px;border:1px solid #dee2e6;border-radius:999px;background:#f8f9fa;color:#495057;">
                            Hab {{ $fz['hab'] }}
                            <span style="display:inline-block;margin-left:4px;padding:0 5px;border-radius:999px;background:#0b5ed7;color:#fff;">{{ $fz['qty'] }}</span>
                        </span>
                    @endforeach
                </div>
            @endif
        </td>
        <td>{{ $r->stock_total }}</td>
        <td>Bs {{ number_format($r->precio_unit, 2) }}</td>
        <td>{{ $r->ultimo_mov_fmt }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<h3>Detalle (más nuevo primero)</h3>
<table>
    <thead>
    <tr>
        <th>#</th>
        <th>Fecha</th>
        <th>Artículo</th>
        <th>Usuario</th>
        <th>Tipo</th>
        <th>Tipo Venta</th>
        <th>Ingreso</th>
        <th>Venta</th>
        <th>Total Ingreso</th>
        <th>Total Venta</th>
        <th>Saldo</th>
    </tr>
    </thead>
    <tbody>
    @foreach($eventos as $i => $e)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ \Carbon\Carbon::parse($e->created_at)->format('d/m/Y H:i') }}</td>
            <td>{{ $e->articulo }}</td>
            <td>{{ $e->usuario_nombre ?? '—' }}</td>
            <td>{{ $e->stock > 0 ? 'INGRESO' : 'VENTA' }}</td>
            <td>{{ $e->tipo_venta ?? '—' }}</td>
            <td>{{ $e->stock }}</td>
            <td>{{ $e->vendido }}</td>
            <td>Bs {{ number_format($e->stock > 0 ? $e->precio : 0, 2) }}</td>
            <td>Bs {{ number_format($e->vendido > 0 ? $e->precio_vendido : 0, 2) }}</td>
            <td><b>{{ $e->saldo_stock }}</b></td>
        </tr>
    @endforeach
    </tbody>
</table>

</body>
</html>
