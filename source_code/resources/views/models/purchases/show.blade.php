@php use Carbon\Carbon; @endphp

<div class="d-flex flex-column text-start">
    {{-- Datos de la compra --}}
    <div class="row g-3 mb-3">
        <div class="col-6">
            <x-form.input.floating-label id="invoice_number" type="text" readonly="true" :value="$purchase->invoice_number"
                placeholder="Número de Factura" iconLeft="bi bi-receipt">N° Factura</x-form.input.floating-label>
        </div>
        <div class="col-6">
            <x-form.input.floating-label id="date" type="text" readonly="true" :value="Carbon::parse($purchase->date)->locale('es')->translatedFormat('d \d\e F \d\e\l Y')"
                placeholder="Fecha" iconLeft="bi bi-calendar">Fecha</x-form.input.floating-label>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6">
            <x-form.input.floating-label id="supplier_name" type="text" readonly="true" :value="$purchase->supplier->name ?? 'N/A'"
                placeholder="Proveedor" iconLeft="bi bi-truck">Proveedor</x-form.input.floating-label>
        </div>
        <div class="col-6">
            <x-form.input.floating-label id="payment_status" type="text" readonly="true" :value="$purchase->payment_status->label() ?? $purchase->payment_status"
                placeholder="Estado de Pago" iconLeft="bi bi-credit-card">Estado de Pago</x-form.input.floating-label>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6">
            <x-form.input.floating-label id="total" type="text" readonly="true" :value="'₡' . number_format($purchase->total, 2)"
                placeholder="Total" iconLeft="bi bi-cash">Total</x-form.input.floating-label>
        </div>
    </div>

    <hr class="my-3" />

    {{-- Tabla de productos comprados (mismos estilos que la tabla principal) --}}
    <h5 class="text-muted mb-3">
        <i class="bi bi-box-seam me-2"></i> Productos comprados
    </h5>

    <div class="table-container rounded-2 p-3">
        <table class="table table-hover rounded-2 w-100" id="purchase-details-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Producto/Insumo</th>
                    <th class="text-end">Cantidad</th>
                    <th class="text-end">Precio Unit.</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchase->details as $detail)
                    @php
                        $isProduct  = class_basename($detail->purchasable_type) === 'Product';
                        $badgeClass = $isProduct ? 'bg-success' : 'bg-info text-dark';
                        $typeLabel  = $isProduct ? 'Producto' : 'Insumo';
                    @endphp
                    <tr>
                        <td>
                            <span class="badge {{ $badgeClass }}">{{ $typeLabel }}</span>
                        </td>
                        <td>{{ $detail->purchasable->name ?? 'N/A' }}</td>
                        <td class="text-end">
                            {{ number_format($detail->quantity ?? 1, 4, '.', '') }}
                        </td>
                        <td class="text-end">
                            ₡{{ number_format($detail->unit_price ?? 0, 2) }}
                        </td>
                        <td class="text-end fw-semibold">
                            ₡{{ number_format($detail->subtotal, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="4" class="text-end">Total de la compra:</td>
                    <td class="text-end text-success fs-6">
                        ₡{{ number_format($purchase->total, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <hr class="my-3" />

    {{-- Contacto del proveedor --}}
    <h5 class="text-muted mb-3">Contacto del Proveedor</h5>
    <div class="row g-3 mb-0">
        <div class="col-6">
            <x-form.input.floating-label id="supplier_phone" type="text" readonly="true" :value="$purchase->supplier->phone ?? 'N/A'"
                iconLeft="bi bi-telephone" placeholder="Teléfono">Teléfono</x-form.input.floating-label>
        </div>
        <div class="col-6">
            <x-form.input.floating-label id="supplier_email" type="email" readonly="true" :value="$purchase->supplier->email ?? 'N/A'"
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