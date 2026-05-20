@php use Carbon\Carbon; @endphp

<div class="d-flex flex-column flex-grow-1 text-start" style="min-width: 50rem; max-width: 50rem; width: 100%;">
    {{-- Purchase Details --}}
    <div class="row g-3 mb-3">
        <div class="col-6">
            <x-form.input.floating-label 
                :id="'invoice_number'"
                :type="'text'"
                :value="$purchase->invoice_number"
                :readonly="true"
                :placeholder="'Número de Factura'"
                :iconLeft="'bi bi-receipt'"
            >
                N° Factura
            </x-form.input.floating-label>
        </div>

        <div class="col-6">
            <x-form.input.floating-label 
                :id="'payment_status'"
                :type="'text'"
                :value="$purchase->payment_status?->label()"
                :readonly="true"
                :placeholder="'Estado de Pago'"
                :iconLeft="'bi bi-credit-card'"
            >
                Estado de Pago
            </x-form.input.floating-label>
        </div>

        <div class="col-6">
            <x-form.input.floating-label
                :id="'date'"
                :type="'text'"
                :value="Carbon::parse($purchase->date)->locale('es')->translatedFormat('d \d\e F \d\e\l Y')"
                :readonly="true"
                :placeholder="'Fecha de Emisión'"
                :iconLeft="'bi bi-calendar-event'"
            >
                Fecha de Emisi&oacute;n
            </x-form.input.floating-label>
        </div>

        <div class="col-6">
            <x-form.input.floating-label
                :id="'total'"
                :type="'text'"
                :readonly="true"
                :value="format_crc($purchase->total)"
                :placeholder="'Total'"
                :iconLeft="'bi bi-cash'"
            >
                Total
            </x-form.input.floating-label>
        </div>
    </div>

    <hr class="my-3" />

    <h5 class="text-muted mb-3">Productos / Insumos Suministrados</h5>
    <div class="table-responsive border border-1 rounded-2 mb-3 pb-2" style="font-size: 1rem;">
        <table class="init-datatable table table-hover align-middle" style="min-width: 600px;">
            <thead class="table-subtle text-secondary-emphasis">
                <tr>
                    <th style="width:115px">Tipo</th>
                    <th>Nombre</th>
                    <th style="width:125px; text-align:center">Cantidad</th>
                    <th style="width:135px; text-align:right">Precio Unit. (₡)</th>
                    <th style="width:120px; text-align:right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @if($purchase?->details->isNotEmpty())
                    @foreach($purchase->details as $detail)
                        <tr>
                            @php
                                $itemTheme = match($detail->purchasable_type) {
                                    App\Models\Product::class => ['color' => 'info', 'icon' => 'bi bi-box-seam'],
                                    App\Models\Supply::class => ['color' => 'warning', 'icon' => 'bi bi-basket'],
                                    default => ['color' => 'secondary', 'icon' => 'bi bi-question-circle'],
                                };
                                $itemTypeLabel = class_basename($detail->purchasable_type) == 'Product' ? 'Producto' : 'Insumo';
                            @endphp

                            {{-- Item Type --}}
                            <td>
                                <span class="badge bg-{{ $itemTheme['color'] }} text-{{ $itemTheme['color'] }}-emphasis border border-{{ $itemTheme['color'] }} bg-{{ $itemTheme['color'] }}-subtle rounded-pill px-3 py-2">
                                    <i class="{{ $itemTheme['icon'] }} me-1"></i>
                                    {{ $itemTypeLabel }}
                                </span>
                            </td>
                            <td>{{ $detail->purchasable->name ?? 'N/A' }}</td>
                            <td class="text-center">{{ $detail->quantity }}</td>
                            <td class="text-end">{{ format_crc($detail->unit_price) }}</td>
                            <td class="text-end">{{ format_crc($detail->quantity * $detail->unit_price) }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <hr class="mt-2 mb-3" />

    {{-- Supplier Contact --}}
    <h5 class="text-muted mb-3">Contacto del Proveedor</h5>
    <div class="row g-3 mb-0">
        <div class="col-12">
            <x-form.input.floating-label
                :id="'supplier_name'"
                :type="'text'"
                :readonly="true"
                :value="$purchase->supplier->name ?? 'N/A'"
                :placeholder="'Proveedor'"
                :iconLeft="'bi bi-truck'"
            >
                Proveedor
            </x-form.input.floating-label>
        </div>

        <div class="col-6">
            <x-form.input.floating-label
                :id="'supplier_phone'"
                :type="'text'"
                :readonly="true"
                :value="$purchase->supplier->phone ?? 'N/A'"
                :iconLeft="'bi bi-telephone'"
                :placeholder="'Teléfono'"
            >
                Teléfono
            </x-form.input.floating-label>
        </div>

        <div class="col-6">
            <x-form.input.floating-label
                :id="'supplier_email'"
                :type="'email'"
                :readonly="true"
                :value="$purchase->supplier->email ?? 'N/A'"
                :iconLeft="'bi bi-envelope'"
                :placeholder="'Correo Electrónico'"
            >
                Correo Electrónico
            </x-form.input.floating-label>
        </div>
    </div>
</div>