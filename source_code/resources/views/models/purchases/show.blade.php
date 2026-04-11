@php use Carbon\Carbon; @endphp

<div class="d-flex flex-column text-start">
    {{-- Datos de la compra --}}
    <div class="row g-3 mb-3">
        <div class="col-6">
            <x-form.input.floating-label id="invoice_number" type="text" readonly="true" :value="$purchase->invoice_number"
                placeholder="Número de Factura" iconLeft="bi bi-receipt">N° Factura</x-form.input.floating-label>
        </div>
        <div class="col-6">
            <x-form.input.floating-label id="date" type="text" readonly="true"
                :value="Carbon::parse($purchase->date)->locale('es')->translatedFormat('d \d\e F \d\e\l Y')"
                placeholder="Fecha" iconLeft="bi bi-calendar">Fecha</x-form.input.floating-label>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6">
            <x-form.input.floating-label id="supplier_name" type="text" readonly="true"
                :value="$purchase->supplier->name ?? 'N/A'"
                placeholder="Proveedor" iconLeft="bi bi-truck">Proveedor</x-form.input.floating-label>
        </div>
        <div class="col-6">
            <x-form.input.floating-label id="payment_status" type="text" readonly="true"
                :value="$purchase->payment_status->label() ?? $purchase->payment_status"
                placeholder="Estado de Pago" iconLeft="bi bi-credit-card">Estado de Pago</x-form.input.floating-label>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6">
            <x-form.input.floating-label id="total" type="text" readonly="true"
                :value="'₡' . number_format($purchase->total, 2)"
                placeholder="Total" iconLeft="bi bi-cash">Total</x-form.input.floating-label>
        </div>
    </div>

    <hr class="my-3" />

    <h5 class="text-muted mb-3">Productos comprados</h5>

    {{-- Tabla de detalles con estilos de la tabla principal + DataTable --}}
    <div class="table-container rounded-2 p-3 mb-2">
        <table id="purchase-details-table" class="table table-hover rounded-2 w-100">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Producto/Insumo</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Subtotal</th>
                    <th>Fecha vencimiento</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchase->details as $detail)
                    @php
                        $isProduct = class_basename($detail->purchasable_type) === 'Product';
                        $typeLabel = $isProduct ? 'Producto' : 'Insumo';
                        $badgeClass = $isProduct ? 'bg-success' : 'bg-info text-dark';
                    @endphp
                    <tr>
                        <td>
                            <span class="badge {{ $badgeClass }}">
                                {{ $typeLabel }}
                            </span>
                        </td>
                        <td>{{ $detail->purchasable->name ?? 'N/A' }}</td>
                        <td>{{ $detail->quantity }}</td>
                        <td>₡{{ number_format($detail->unit_price, 2) }}</td>
                        <td>₡{{ number_format($detail->subtotal, 2) }}</td>
                        <td>{{ $detail->expiration_date ? $detail->expiration_date->format('d/m/Y') : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <hr class="my-3" />

    {{-- Contacto del proveedor --}}
    <h5 class="text-muted mb-3">Contacto del Proveedor</h5>
    <div class="row g-3 mb-0">
        <div class="col-6">
            <x-form.input.floating-label id="supplier_phone" type="text" readonly="true"
                :value="$purchase->supplier->phone ?? 'N/A'"
                iconLeft="bi bi-telephone" placeholder="Teléfono">Teléfono</x-form.input.floating-label>
        </div>
        <div class="col-6">
            <x-form.input.floating-label id="supplier_email" type="email" readonly="true"
                :value="$purchase->supplier->email ?? 'N/A'"
                iconLeft="bi bi-envelope" placeholder="Correo">Correo Electrónico</x-form.input.floating-label>
        </div>
    </div>

    <hr class="my-3" />

    {{-- Fechas --}}
    <div class="row g-3 mb-0">
        <div class="col-6">
            <x-form.input.floating-label id="created_at" type="text" readonly="true"
                :value="Carbon::parse($purchase->created_at)->locale('es')->translatedFormat('d \d\e F \d\e\l Y H:i')"
                iconLeft="bi bi-calendar-plus" placeholder="Fecha de Creación">Fecha de Creación</x-form.input.floating-label>
        </div>
        @if ($purchase->updated_at && $purchase->updated_at != $purchase->created_at)
            <div class="col-6">
                <x-form.input.floating-label id="updated_at" type="text" readonly="true"
                    :value="Carbon::parse($purchase->updated_at)->locale('es')->translatedFormat('d \d\e F \d\e\l Y H:i')"
                    iconLeft="bi bi-calendar-check" placeholder="Fecha de Actualización">Última Actualización</x-form.input.floating-label>
            </div>
        @endif
    </div>
</div>

{{-- DataTable para la tabla de detalles de la compra --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Solo inicializar si la tabla existe y no fue ya inicializada
        if ($.fn.DataTable.isDataTable('#purchase-details-table')) return;

        $('#purchase-details-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ ítems',
                infoEmpty: 'Sin resultados',
                zeroRecords: 'No se encontraron productos para esta búsqueda',
                paginate: {
                    first:    'Primero',
                    last:     'Último',
                    next:     'Siguiente',
                    previous: 'Anterior'
                }
            },
            pageLength: 5,
            lengthMenu: [5, 10, 25],
            order: [[0, 'asc']],
            columnDefs: [
                // Columna Tipo: ordenar por texto del badge
                { type: 'string', targets: 0 },
                // Columnas numéricas con símbolo ₡
                { type: 'num-fmt', targets: [3, 4] },
            ],
            dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6"f>>rt<"row mt-2"<"col-sm-6"i><"col-sm-6"p>>',
        });
    });
</script>