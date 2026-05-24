@php use Carbon\Carbon; @endphp

<div class="d-flex flex-column flex-grow-1 text-start" style="min-width: 50rem; max-width: 50rem; width: 100%;">

    {{-- Sale Information --}}
    <div class="row g-3 mb-3">
        <div class="col-6">
            <x-form.input.floating-label 
                :id="'invoice_number'"
                :type="'text'"
                :value="$sale->invoice_number"
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
                :value="$sale->payment_status?->label()"
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
                :value="Carbon::parse($sale->date)->timezone('America/Costa_Rica')->locale('es')->translatedFormat('d \d\e F \d\e\l Y')"
                :readonly="true"
                :placeholder="'Fecha de Emisión'"
                :iconLeft="'bi bi-calendar-event'"
            >
                Fecha de Emisi&oacute;n
            </x-form.input.floating-label>
        </div>

        <div class="col-6">
            <x-form.input.floating-label
                :id="'user'"
                :type="'text'"
                :readonly="true"
                :value="$sale->user->name"
                :placeholder="'Cajero'"
                :iconLeft="'bi bi-person'"
            >
                Cajero
            </x-form.input.floating-label>
        </div>
    </div>

    <hr class="mb-3 mt-2" />

    {{-- Products Table --}}
    <h5 class="text-muted mb-3">Productos / Platillos Vendidos</h5>
    <div class="table-responsive border border-1 rounded-2 mb-3 pb-2" style="font-size: 1rem;">
        <table class="init-datatable table table-hover align-middle" style="min-width: 600px;">
            <thead>
                <tr>
                    <th scope="col">Producto / Platillo</th>
                    <th scope="col" class="text-center">Cantidad</th>
                    <th scope="col" class="text-end">Precio Unit. (₡)</th>
                    <th scope="col" class="text-end">Impuesto (₡)</th>
                    <th scope="col" class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @if ($sale?->saleDetails->isNotEmpty())
                    @foreach ($sale->saleDetails as $detail)
                        <tr>
                            <td>{{ $detail->product->name ?? 'N/A' }}</td>
                            <td class="text-center">{{ $detail->quantity }}</td>
                            <td class="text-end">{{ format_crc($detail->unit_price) }}</td>
                            <td class="text-end">{{ format_crc($detail->sub_total * ($detail->applied_tax / 100)) }}</td>
                            <td class="text-end">{{ format_crc($detail->quantity * $detail->unit_price * (1 + $detail->applied_tax / 100)) }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-4">
            <x-form.input.floating-label
                :id="'subtotal'"
                :type="'text'"
                :readonly="true"
                :value="format_crc($sale->getReceiptSubtotal())"
                :placeholder="'Subtotal'"
            >
                Subtotal
            </x-form.input.floating-label>
        </div>
        <div class="col-4">
            <x-form.input.floating-label
                :id="'taxes'"
                :type="'text'"
                :inputClass="'fw-bold text-info'"
                :readonly="true"
                :value="format_crc($sale->getReceiptTaxTotal())"
                :placeholder="'Impuestos'"
            >
                Impuestos
            </x-form.input.floating-label>
        </div>
        <div class="col-4">
            <x-form.input.floating-label
                :id="'total'"
                :type="'text'"
                :readonly="true"
                :inputClass="'fw-bold'"
                :value="format_crc($sale->total)"
                :placeholder="'Total'"
                style="color: var(--bambu-logo-bg);"
            >
                Total
            </x-form.input.floating-label>
        </div>
    </div>
    
    <hr class="mb-3 mt-2" />

    {{-- Payments Table --}}
    <h5 class="text-muted mb-3">Pagos Realizados</h5>
    <div class="table-responsive border border-1 rounded-2 mb-3 pb-2" style="font-size: 1rem;">
        <table class="init-datatable table table-hover align-middle" style="min-width: 600px;">
            <thead>
                <tr>
                    <th scope="col" style="width: 25%;">M&eacute;todo de Pago</th>
                    <th scope="col" style="width: 25%;" class="text-end">Monto Recibido</th>
                    <th scope="col" style="width: 25%;" class="text-end">Vuelto</th>
                    <th scope="col" style="width: 25%;" class="text-end">N° Referencia</th>
                </tr>
            </thead>
            <tbody>
                @if ($sale?->payments->isNotEmpty())
                    @foreach ($sale->payments as $payment)
                        <tr>
                            <td>{{ $payment->method?->label() ?? 'N/A' }}</td>
                            <td class="text-end">{{ format_crc($payment->amount) }}</td>
                            <td class="text-end">
                                @if ($payment->method === App\Enums\PaymentMethod::CASH)
                                    @if ($payment->change_amount !== null && $payment->change_amount > 0)
                                        <span class="fw-bold" style="color: var(--bambu-logo-bg);">{{ format_crc($payment->change_amount) }}</span>
                                    @else
                                        <span class="text-muted fst-italic">Sin Vuelto</span>
                                    @endif
                                @else
                                    <span class="text-muted fst-italic">No Aplica</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if (! blank($payment->reference))
                                    <span class="text-truncate d-inline-block" style="max-width: 150px;" title="{{ $payment->reference }}">
                                        {{ $payment->reference }}
                                    </span>
                                @else
                                    <span class="text-muted fst-italic">
                                        {{ $payment->method === App\Enums\PaymentMethod::CASH ? 'No Aplica' : 'No Especificado' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>