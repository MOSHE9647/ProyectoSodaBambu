@php
    $subtotal = $sale->getReceiptSubtotal();
    $tax = $sale->getReceiptTaxTotal();
@endphp

<style>
    .swal2-popup { max-width: 680px !important; width: 680px !important; }
</style>

<div class="d-flex flex-column text-start" style="max-width: 600px; margin: 0 auto;">
    <div class="row g-3">

        {{-- N° Factura --}}
        <div class="col-md-6">
            <x-form.input.floating-label
                :id="'invoice_number'"
                :type="'text'"
                :readonly="true"
                :value="$sale->invoice_number"
                :iconLeft="'bi bi-receipt'"
                :placeholder="'N° Factura'"
            >
                N&deg; Factura
            </x-form.input.floating-label>
        </div>

        {{-- Fecha --}}
        <div class="col-md-6">
            <x-form.input.floating-label
                :id="'date'"
                :type="'date'"
                :readonly="true"
                :value="$sale->date->format('Y-m-d')"
                :iconLeft="'bi bi-calendar2-check'"
                :placeholder="'Fecha de Venta'"
            >
                Fecha de Venta
            </x-form.input.floating-label>
        </div>

        {{-- Estado de Pago --}}
        <div class="col-12">
            <x-form.input.floating-label
                :id="'payment_status'"
                :type="'text'"
                :readonly="true"
                :value="$sale->payments->isNotEmpty() ? 'Pagado' : 'Pendiente'"
                :iconLeft="'bi bi-info-circle'"
                :placeholder="'Estado de Pago'"
            >
                Estado de Pago
            </x-form.input.floating-label>
        </div>

        {{-- Productos --}}
        <div class="col-12">
            <div class="d-flex">
                <span class="input-group-text rounded-end-0 border-end-0">
                    <i class="bi bi-bag"></i>
                </span>
                <div class="d-flex flex-column border border-1 border-secondary-subtle rounded-start-0 rounded-2 w-100" style="padding: 0.5rem 0.75rem 0.75rem;">
                    <label class="form-label text-muted mb-2" style="transform: scale(0.85) translateX(-2.85rem);">Productos</label>
                    <table class="table table-sm table-bordered align-middle mb-0 init-datatable">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sale->saleDetails as $detail)
                                <tr>
                                    <td>{{ $detail->product->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $detail->quantity }}</td>
                                    <td class="text-end">₡ {{ number_format($detail->unit_price, 0) }}</td>
                                    <td class="text-end">₡ {{ number_format($detail->sub_total, 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Subtotal --}}
        <div class="col-md-4">
            <x-form.input.floating-label
                :id="'subtotal'"
                :type="'text'"
                :readonly="true"
                :textIconLeft="true"
                :value="number_format($subtotal, 0, ',', ' ')"
                :placeholder="'Subtotal'"
            >
                <x-slot:iconLeft>
                    <x-icons.colon-icon width="14" height="14" />
                </x-slot:iconLeft>
                Subtotal
            </x-form.input.floating-label>
        </div>

        {{-- Impuestos --}}
        <div class="col-md-4">
            <x-form.input.floating-label
                :id="'tax'"
                :type="'text'"
                :readonly="true"
                :textIconLeft="true"
                :value="number_format($tax, 0, ',', ' ')"
                :placeholder="'Impuestos'"
            >
                <x-slot:iconLeft>
                    <x-icons.colon-icon width="14" height="14" />
                </x-slot:iconLeft>
                Impuestos
            </x-form.input.floating-label>
        </div>

        {{-- Total --}}
        <div class="col-md-4">
            <x-form.input.floating-label
                :id="'total'"
                :type="'text'"
                :readonly="true"
                :textIconLeft="true"
                :value="number_format($sale->total, 0, ',', ' ')"
                :placeholder="'Total'"
            >
                <x-slot:iconLeft>
                    <x-icons.colon-icon width="14" height="14" />
                </x-slot:iconLeft>
                Total
            </x-form.input.floating-label>
        </div>

        {{-- Detalle de Pago --}}
        @if ($sale->payments->isNotEmpty())
            <div class="col-12">
                <div class="d-flex">
                    <span class="input-group-text rounded-end-0 border-end-0">
                        <i class="bi bi-credit-card"></i>
                    </span>
                    <div class="d-flex flex-column border border-1 border-secondary-subtle rounded-start-0 rounded-2 w-100" style="padding: 0.5rem 0.75rem 0.75rem;">
                        <label class="form-label text-muted mb-2" style="transform: scale(0.85) translateX(-2.85rem);">Detalle de Pago</label>
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Método</th>
                                    <th class="text-end">Monto</th>
                                    <th class="text-end">Vuelto</th>
                                    <th>Referencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sale->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->method->label() }}</td>
                                        <td class="text-end">₡ {{ number_format($payment->amount, 0) }}</td>
                                        <td class="text-end">₡ {{ number_format($payment->change_amount ?? 0, 0) }}</td>
                                        <td>{{ $payment->reference ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>