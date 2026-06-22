@extends('adminlte::page')

@section('title', 'Monitoreo')

@section('template_title')
Logs y rendimiento
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h3 class="mb-2">Centro de monitoreo</h3>
                    <p class="text-muted mb-4">
                        Aqui puedes revisar tiempos de carga, consultas lentas, peticiones lentas, uso del sistema y logs del proyecto.
                    </p>

                    <div class="row">
                        <div class="col-12 col-lg-4 mb-3">
                            <div class="card h-100 border-primary">
                                <div class="card-header bg-primary text-white">
                                    Pulse
                                </div>
                                <div class="card-body">
                                    <p class="mb-3">
                                        Revisa tiempos de carga, requests lentos, queries lentas, colas y uso del servidor.
                                    </p>
                                    <a href="{{ url('/pulse') }}" target="_blank" class="btn btn-primary">
                                        Abrir Pulse
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4 mb-3">
                            <div class="card h-100 border-secondary">
                                <div class="card-header bg-secondary text-white">
                                    Log Viewer
                                </div>
                                <div class="card-body">
                                    <p class="mb-3">
                                        Mira errores, warnings y eventos del sistema directamente desde los logs de Laravel.
                                    </p>
                                    <a href="{{ url('/log-viewer') }}" target="_blank" class="btn btn-secondary">
                                        Abrir Logs
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4 mb-3">
                            <div class="card h-100 border-info">
                                <div class="card-header bg-info text-white">
                                    Estado del monitoreo
                                </div>
                                <div class="card-body">
                                    <ul class="mb-0 pl-3">
                                        <li>Pulse activo</li>
                                        <li>Logs activos</li>
                                        <li>Captura de metricas habilitada</li>
                                        <li>Listo para revisar rendimiento</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-12 col-xl-6 mb-3">
                            <div class="card border-primary">
                                <div class="card-header bg-light">
                                    Vista rapida de Pulse
                                </div>
                                <div class="card-body p-0">
                                    <iframe
                                        src="{{ url('/pulse') }}"
                                        title="Pulse"
                                        style="width: 100%; height: 700px; border: 0;"
                                        loading="lazy">
                                    </iframe>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xl-6 mb-3">
                            <div class="card border-secondary">
                                <div class="card-header bg-light">
                                    Vista rapida de Logs
                                </div>
                                <div class="card-body p-0">
                                    <iframe
                                        src="{{ url('/log-viewer') }}"
                                        title="Log Viewer"
                                        style="width: 100%; height: 700px; border: 0;"
                                        loading="lazy">
                                    </iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('footer')
@endsection
