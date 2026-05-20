@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    {{-- Encabezado con el componente estándar del sistema --}}
    <x-header title="Historial de Ventas" subtitle="Consulta, acciones y gestión del histórico de ventas" />

    {{-- Contenedor de Filtros Personalizados --}}
    <div class="card shadow-sm border-0 mb-4 rounded-2 p-3">
        <form id="sales-filter-form" class="row align-items-end g-3">
            {{-- Filtro por Factura --}}
            <div class="col-md-3">
                <label for="search-invoice" class="form-label fw-bold small text-muted">N° Factura / Comprobante</label>
                <input type="text" id="search-invoice" class="form-control" placeholder="Ej: FAC-0001" autocomplete="off">
            </div>
            {{-- Filtro de Fecha Inicio --}}
            <div class="col-md-3">
                <label for="start-date" class="form-label fw-bold small text-muted">Fecha Inicio</label>
                <input type="date" id="start-date" class="form-control" max="{{ now()->toDateString() }}">
            </div>
            {{-- Filtro de Fecha Fin --}}
            <div class="col-md-3">
                <label for="end-date" class="form-label fw-bold small text-muted">Fecha Fin</label>
                <input type="date" id="end-date" class="form-control">
            </div>
            {{-- Botones de Acción de Filtros --}}
            <div class="col-md-3 d-flex gap-2">
                <button type="button" id="btn-filter" class="btn btn-primary w-100">
                    <i class="bi bi-filter"></i> Filtrar
                </button>
                <button type="button" id="btn-clear" class="btn btn-outline-danger w-100">
                    <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                </button>
            </div>
        </form>
    </div>

    {{-- Contenedor de la Tabla DataTable --}}
    <div class="table-container card shadow-sm border-0 p-4 rounded-2">
        <table class="table table-hover w-100 init-datatable" id="history-sales-table">
            <thead>
                <tr>
                    <th>N° Factura</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado de Pago</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Modal para Visualizar Información Detallada de la Venta --}}
<div class="modal fade" id="viewSaleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-receipt me-2"></i>Detalle de la Venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="sale-modal-content">
                </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

@push('scripts')
    @vite(['resources/js/pages/sales/history.js'])
@endpush