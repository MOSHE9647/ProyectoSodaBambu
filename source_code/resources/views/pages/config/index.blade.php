@extends('layouts.app')

@section('content')
<div class="container p-0">
    {{-- Page Header --}}
    <x-header title="Centro de Ayuda" subtitle="Recursos y guías para sacar el máximo provecho del sistema" />

    <div class="table-container rounded-2 p-4">
        {{-- ── Videotutoriales ── --}}
        <div>
            <h5 class="text-muted pb-3 border-bottom border-secondary">
                <i class="bi bi-play-btn-fill me-3"></i>
                Videotutoriales
            </h5>

            <div class="row g-3 mt-1">

                @php
                    $videos = [
                        ['icon' => 'bi-display',          'title' => 'Introducción al Sistema',    'text' => 'Recorrido general por los módulos y funciones principales del sistema.'],
                        ['icon' => 'bi-cart4',            'title' => 'Gestión de Ventas',          'text' => 'Cómo crear, editar y administrar ventas paso a paso.'],
                        ['icon' => 'bi-boxes',            'title' => 'Control de Inventario',      'text' => 'Manejo de stock y registro de movimientos de productos.'],
                        ['icon' => 'bi-person-lines-fill','title' => 'Módulo de Contrataciones',   'text' => 'Alta de empleados, contratos y gestión documental.'],
                        ['icon' => 'bi-graph-up-arrow',   'title' => 'Generación de Reportes',     'text' => 'Cómo filtrar, generar y exportar reportes desde el sistema.'],
                        ['icon' => 'bi-shield-lock-fill', 'title' => 'Usuarios y Permisos',        'text' => 'Crear usuarios, asignar roles y gestionar accesos al sistema.'],
                    ];
                @endphp

                @foreach ($videos as $video)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="border border-secondary rounded-2 overflow-hidden h-100 d-flex flex-column help-card">

                        {{-- Thumbnail --}}
                        <div class="video-thumb d-flex align-items-center justify-content-center position-relative">
                            <i class="bi {{ $video['icon'] }} text-secondary video-thumb-icon"></i>
                            <div class="play-circle d-flex align-items-center justify-content-center rounded-circle position-absolute">
                                <i class="bi bi-play-fill fs-5 ps-1" style="color: var(--bs-success, #4caf50);"></i>
                            </div>
                            <span class="position-absolute bottom-0 end-0 mb-2 me-2 badge bg-dark bg-opacity-75 text-secondary fw-normal small">
                                Próximamente
                            </span>
                        </div>

                        {{-- Info --}}
                        <div class="p-3 d-flex flex-column gap-1 flex-grow-1">
                            <span class="fw-semibold text-light small">{{ $video['title'] }}</span>
                            <p class="text-muted small mb-0">{{ $video['text'] }}</p>
                        </div>

                    </div>
                </div>
                @endforeach

            </div>
        </div>

    </div>
</div>

<style>
    /* Hover lift */
    .help-card {
        transition: border-color .18s ease, transform .18s ease;
    }
    .help-card:hover {
        border-color: rgba(76, 175, 80, .45) !important;
        transform: translateY(-2px);
    }

    /* Manual icon badge */
    .help-icon-wrap {
        width: 40px;
        height: 40px;
        background-color: rgba(76, 175, 80, .1);
        color: #4caf50;
    }

    /* Video thumbnail */
    .video-thumb {
        aspect-ratio: 16 / 9;
        background-color: rgba(255, 255, 255, .03);
        border-bottom: 1px solid rgba(255, 255, 255, .07);
    }
    .video-thumb-icon {
        font-size: 3rem;
        opacity: .12;
    }
    .play-circle {
        width: 48px;
        height: 48px;
        background-color: rgba(76, 175, 80, .1);
        border: 2px solid rgba(76, 175, 80, .35);
    }
</style>
@endsection

@section('scripts')
    {{-- Additional Scripts for Configuration Page --}}
    @vite(['resources/js/pages/config/main.js'])

    {{-- Success Toast Notification --}}
    @if (session('success'))
        <script type="module">
            SwalToast.fire({
                icon: SwalNotificationTypes.SUCCESS,
                title: @json(session('success'))
            });
        </script>
    @endif
@endsection