@extends('layouts.app')

@section('content')
<div class="container p-0">
    <x-header title="Gestión de Compras" subtitle="Administre las compras realizadas a proveedores" />

    <div class="table-container rounded-2 p-4">
        <table id="purchases-table" class="table table-hover rounded-2">
            <thead>
                <tr>
                    <th>N° Factura</th>
                    <th>Proveedor</th>
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

{{-- Modal: productos/insumos del proveedor --}}
<div class="modal fade" id="supplierItemsModal" tabindex="-1" aria-labelledby="supplierItemsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="supplierItemsModalLabel">
                    <i class="bi bi-truck me-2"></i>
                    Productos/Insumos de <span id="modal-supplier-name" class="fw-bold"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="supplier-items-loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Cargando...</p>
                </div>
                <div id="supplier-items-content" class="d-none">
                    <p class="text-muted mb-3">Ítems que este proveedor ha suministrado en compras registradas:</p>
                    <table class="table table-sm table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Tipo</th>
                                <th>Nombre</th>
                                <th>Veces suministrado</th>
                            </tr>
                        </thead>
                        <tbody id="supplier-items-tbody"></tbody>
                    </table>
                </div>
                <div id="supplier-items-empty" class="d-none text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-2"></i>
                    <p class="mt-2">Este proveedor no tiene compras registradas.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @vite(['resources/js/models/purchases/main.js'])
@endsection