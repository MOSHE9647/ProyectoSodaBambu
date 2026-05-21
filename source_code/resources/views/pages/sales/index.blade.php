@extends('layouts.app')

@section('content')

    {{-- Header --}}
    <div class="d-flex flex-row align-items-center justify-content-between gap-2">
        <x-header title="Historial de Ventas" subtitle="Consulta, acciones y gestión del histórico de ventas" />
    </div>

    {{-- Main Content --}}
    <div class="d-flex flex-column flex-grow-1">
       
        {{-- Bloque de Filtros --}}
        <div class="card shadow-sm border-0 p-4 rounded-2">
            <h5 class="fw-bold mb-3 d-flex align-items-center justify-content-between">
                <span>Filtrar Búsqueda</span>
                <i class="bi bi-funnel text-muted"></i>
            </h5>
            <hr class="mt-0 mb-3">
           
            <form id="filter-sales-form" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="search-invoice" class="form-label fw-bold small text-muted mb-1">N° Factura / Comprobante</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="search-invoice" name="search-invoice" class="form-control border-start-0 ps-0" placeholder="Ej: FAC-0001" autocomplete="off">
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="start-date" class="form-label fw-bold small text-muted mb-1">Fecha Inicio</label>
                    <input type="date" id="start-date" name="start-date" class="form-control">
                </div>

                <div class="col-md-3">
                    <label for="end-date" class="form-label fw-bold small text-muted mb-1">Fecha Fin</label>
                    <input type="date" id="end-date" name="end-date" class="form-control">
                </div>

                <div class="col-md-2 d-flex gap-2" style="height: 38px;">
                    <button type="button" id="btn-filter" class="btn btn-success flex-grow-1 d-flex align-items-center justify-content-center h-100" title="Filtrar historial">
                        <i class="bi bi-filter me-1"></i> Filtrar
                    </button>
                    <button type="button" id="btn-clear" class="btn btn-outline-danger d-flex align-items-center justify-content-center h-100 px-3" title="Limpiar filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </form>
        </div>

        {{-- Bloque de la Tabla - Idéntico en jerarquía y envoltura flex a tu módulo de Ventas e Insumos --}}
        <div class="table-container rounded-2 p-4 shadow-sm">
            <table id="history-sales-table" class="table table-hover align-middle rounded-2" style="width: 100%">
                <thead>
                    <tr>
                        <th>N° Factura</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado de Pago</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Controlado dinámicamente por DataTables sin congelar la renderización --}}
                </tbody>
            </table>
        </div>

    </div>

    {{-- MODAL: Detalle de la Venta --}}
    <div class="modal fade" id="viewSaleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
                        <i class="bi bi-receipt-cutoff text-primary me-2"></i> Detalle Completo de Venta
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="sale-modal-content">
                    {{-- Contenido dinámico --}}
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    @vite(['resources/js/pages/sales/history.js'])
@endsection