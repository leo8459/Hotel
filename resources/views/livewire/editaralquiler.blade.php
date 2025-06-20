<div class="container py-4">
    <h2 class="fw-bold mb-4 text-primary">Editar Alquiler</h2>

    <div class="card">
        <div class="card-body">
            <p><strong>ID:</strong> {{ $alquiler->id }}</p>
            <p><strong>Tipo ingreso:</strong> {{ $alquiler->tipoingreso }}</p>
            <p><strong>Habitación:</strong> {{ $alquiler->habitacion_id }}</p>
            <p><strong>Entrada:</strong> {{ $alquiler->entrada }}</p>
            <p><strong>Estado:</strong> {{ $alquiler->estado }}</p>
        </div>
    </div>
</div>
