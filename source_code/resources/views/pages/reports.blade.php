@extends('layouts.app')

@section('content')
    <x-header title="Registro de Ventas" subtitle="Analice el rendimiento de ventas y productos" />

    <div class="container-fluid px-0">
        <div class="card-container rounded-2 p-2 mb-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <button type="button" class="btn btn-success w-100 fw-semibold" disabled>
                        <i class="bi bi-currency-dollar me-1"></i>
                        Reporte de Ventas
                    </button>
                </div>
                <div class="col-md-6">
                    <button type="button" class="btn btn-light border w-100 text-muted" disabled>
                        <i class="bi bi-box-seam me-1"></i>
                        Productos Más Vendidos
                    </button>
                </div>
            </div>
        </div>

        <div class="card-container rounded-2 p-4 mb-3">
            <h6 class="fw-bold mb-3">Filtros de Fecha</h6>
            <div class="d-flex flex-wrap gap-4">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="date_filter" id="today-filter">
                    <label class="form-check-label" for="today-filter">Hoy</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="date_filter" id="week-filter">
                    <label class="form-check-label" for="week-filter">Esta Semana</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="date_filter" id="month-filter" checked>
                    <label class="form-check-label" for="month-filter">Este Mes</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="date_filter" id="custom-filter">
                    <label class="form-check-label" for="custom-filter">Personalizado</label>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <x-stat-card title="Ingresos Totales" icon="cash-coin" color-theme="green" hideTrend="true" value="">
                    @slot('value')
                        <div class="d-flex flex-column gap-1">
                            <h4 class="fw-bold mb-0">
                                <x-icons.colon-icon width="18" height="18" />
                                6.180.000
                            </h4>
                            <small class="text-muted">12/03 - 16/03/2025</small>
                        </div>
                    @endslot
                </x-stat-card>
            </div>

            <div class="col-md-4">
                <x-stat-card title="Órdenes Totales" icon="receipt" color-theme="yellow" currency="false" hideTrend="true" value="">
                    @slot('value')
                        <div class="d-flex flex-column gap-1">
                            <h4 class="fw-bold mb-0">223</h4>
                            <small class="text-muted">12/03 - 16/03/2025</small>
                        </div>
                    @endslot
                </x-stat-card>
            </div>

            <div class="col-md-4">
                <x-stat-card
                    title="Promedio Diario"
                    icon="graph-up-arrow"
                    color-theme="green"
                    trend="+8.5 %"
                    trend-context="vs periodo anterior"
                    trend-direction="up"
                    value="1.236.000"
                />
            </div>
        </div>

        <div class="table-container rounded-2 p-4 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Reportes de Ventas Diarias</h5>
                <button type="button" class="btn btn-success btn-sm fw-semibold" disabled>
                    <i class="bi bi-download me-1"></i>
                    EXPORTAR A EXCEL
                </button>
            </div>

            <table class="table table-hover rounded-2 mb-0">
                <thead>
                    <tr>
                        <th scope="col">FECHA</th>
                        <th scope="col">ORDENES</th>
                        <th scope="col">INGRESOS</th>
                        <th scope="col">TICKET PROMEDIO</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="card-container rounded-2 p-4">
            <h5 class="fw-bold mb-3">Gráfico de tendencia de Ventas</h5>
            <div class="border rounded-3 d-flex align-items-center justify-content-center flex-column text-muted" style="min-height: 220px; background: rgba(var(--bs-body-bg-rgb), 0.35);">
                <i class="bi bi-graph-up-arrow fs-2 mb-2"></i>
                <span>Gráfico interactivo disponible próximamente</span>
            </div>
        </div>
    </div>
@endsection
