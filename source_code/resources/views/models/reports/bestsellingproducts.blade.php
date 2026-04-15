@extends('layouts.app')

@section('content')
    <x-header title="Registro de Ventas" subtitle="Analice el rendimiento de ventas y productos" />

    <style>
        .report-switch-btn {
            min-height: 2.5rem;
        }

        .report-top-btn-secondary {
            color: var(--bs-body-color);
            border-color: var(--table-container-border);
            background-color: transparent;
        }

        .report-top-btn-secondary:hover {
            color: var(--bs-body-color);
            border-color: var(--bambu-logo-bg);
            background-color: rgba(var(--bs-body-color-rgb), 0.06);
        }
    </style>

    <div class="container-fluid px-0">
        <div class="card-container rounded-2 p-2 mb-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <a href="{{ route('reports', array_merge(request()->except('section'), ['section' => 'sales'])) }}" class="btn report-switch-btn w-100 fw-semibold {{ ($activeSection ?? 'products') === 'sales' ? 'btn-primary' : 'report-top-btn-secondary' }}">
                        <i class="bi bi-currency-dollar me-1"></i>
                        Reporte de Ventas
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="{{ route('reports', array_merge(request()->except('section'), ['section' => 'products'])) }}" class="btn report-switch-btn w-100 fw-semibold {{ ($activeSection ?? 'products') === 'products' ? 'btn-primary' : 'report-top-btn-secondary' }}">
                        <i class="bi bi-box-seam me-1"></i>
                        Productos Más Vendidos
                    </a>
                </div>
            </div>
        </div>

        <div class="table-container rounded-2 p-4 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Productos Más Vendidos</h5>
                <button type="button" class="btn btn-success btn-sm fw-semibold" disabled>
                    <i class="bi bi-download me-1"></i>
                    EXPORTAR A EXCEL
                </button>
            </div>

            <table class="table table-hover rounded-2 mb-0">
                <thead>
                    <tr>
                        <th scope="col">PRODUCTO</th>
                        <th scope="col">CANTIDAD VENDIDA</th>
                        <th scope="col">INGRESOS</th>
                        <th scope="col">% DEL TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No hay datos de productos vendidos disponibles todavía.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card-container rounded-2 p-4">
            <h5 class="fw-bold mb-3">Gráfico de Distribución de Productos</h5>
            <div class="border rounded-3 d-flex align-items-center justify-content-center flex-column text-muted" style="min-height: 220px; background: rgba(var(--bs-body-bg-rgb), 0.35);">
                <i class="bi bi-graph-up-arrow fs-2 mb-2"></i>
                <span>Gráfico interactivo disponible próximamente</span>
            </div>
        </div>
    </div>
@endsection
