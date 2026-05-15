@php
    use App\Enums\PaymentMethod;

    // Define payment methods with labels and icons
    $paymentMethods = [
        [ 
            'id' => 'cash_payment', 
            'value' => PaymentMethod::CASH->value, 
            'label' => 'Efectivo', 
            'icon_is_svg' => false, 
            'icon' => 'bi bi-cash-coin', 
            'checked' => true 
        ],
        [ 
            'id' => 'card_payment',
            'value' => PaymentMethod::CARD->value,
            'label' => 'Tarjeta',
            'icon_is_svg' => false,
            'icon' => 'bi bi-credit-card',
            'checked' => false
        ],
        [ 
            'id' => 'sinpe_payment',
            'value' => PaymentMethod::SINPE->value,
            'label' => 'SINPE Móvil',
            'icon_is_svg' => true,
            'icon' => 'icons.sinpe-movil',
            'checked' => false
        ],
    ];
@endphp

<div class="d-flex gap-3 justify-content-between" style="min-height: 32.2rem;">
    <section id="method-payment-section" class="table-container w-50 d-flex flex-column rounded-3 shadow-sm overflow-hidden p-2 pb-3">
        {{-- Total amount --}}
        <div class="d-flex justify-content-between align-items-center gap-2 border-bottom px-3 pt-2 pb-2 mb-2">
            <span class="fs-5 fw-bold">Total a Pagar:</span>
            <span class="d-flex fw-bolder text-success align-items-center" style="font-size: 1.5rem;">
                <x-icons.colon-icon class="me-1" />
                {{ format_crc($paymentTotal, false) }}
            </span>
        </div>

        {{-- Payment Method --}}
        <x-form.input.radio-group :groupClass="'d-flex flex-column gap-2'" :labelClass="'d-none'">
            @foreach ($paymentMethods as $method)
                <x-form.input.radio-button
                    :id="$method['id']"
                    :name="'payment_method'"
                    :value="$method['value']"
                    :class="'d-flex align-items-center justify-content-between rounded-3 text-start'"
                    :checked="$method['checked']"
                >
                    <div class="d-flex align-items-center p-2 rounded check-button">
                        @if($method['icon_is_svg'])
                            <x-dynamic-component :component="$method['icon']" class="fs-4 me-4" />
                        @else
                            <i class="{{ $method['icon'] }} fs-4 me-4"></i>
                        @endif
                        <span class="fs-6">{{ $method['label'] }}</span>
                    </div>
                    @if($method['checked'])
                        <i class="checked bi bi-check-circle fs-6 me-2"></i>
                    @endif
                </x-form.input.radio-button>
            @endforeach
        </x-form.input.radio-group>

        <div class="d-flex flex-column flex-grow-1 align-items-start justify-content-between gap-3 border-top pt-2 mt-2">
            {{-- Amount To Pay --}}
            <x-form.input :id="'amount_to_pay'" :type="'number'" :step="'5'" :min="'0'" :class="'border-secondary pt-2 w-100'" :value="$paymentTotal" :placeholder="'Ej: 1000'" :textIconLeft="true" :required="true" focusOnShow>
                <x-slot:iconLeft>
                    <x-icons.colon-icon width="16" height="16" />
                </x-slot:iconLeft>
                Monto a pagar: <span class="text-danger">*</span>
            </x-form.input>

            {{-- Reference Number --}}
            <x-form.input :id="'reference_number'" :type="'numeric'" :class="'border-secondary w-100'" :placeholder="'Ej: 612314431345'" :iconLeft="'bi bi-credit-card-2-back'" :textIconRight="true" :required="false" :disabled="true" :readonly="true">
                Referencia de Pago:
                <x-slot:iconRight>
                    <i class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-title="Número de referencia del pago, como el número de transacción o código de autorización. Útil para llevar un registro más detallado de los pagos realizados mediante Tarjeta o SINPE Móvil."></i>
                </x-slot:iconRight>
            </x-form.input>
        </div>

        {{-- Add Payment Button --}}
        <x-form.button :id="'add-payment-button'" :class="'btn-warning px-4 py-2 mt-3 w-100'" :type="'button'">
            <div id="add-payment-form-button-text" class="d-flex flex-row align-items-center justify-content-center">
                <i class="bi bi-plus-circle me-2"></i>
                Agregar Pago
            </div>
        </x-form.button>
    </section>

    <form id="payment-details-form" class="table-container w-50 d-flex flex-column rounded-3 shadow-sm overflow-hidden p-2 pb-3" style="margin-block-end: 0;">
        {{-- Details Header --}}
        <div class="d-flex justify-content-center align-items-center border-bottom px-3 py-2 mb-2" style="height: 55.2px;">
            <span class="fs-5 fw-bold">Resumen de Pagos</span>
        </div>

        {{-- Details Content --}}
        <div id="payment-details" class="d-flex flex-column flex-grow-1 gap-2 border-bottom pb-2">

            @php
                // Example payments - replace with dynamic data in real implementation
                $amountPerPayment = round(($paymentTotal / 3) / 5) * 5; // Round to nearest 5 for better UX
                $examplePayments = [
                    [ 'label' => 'Efectivo', 'type' => 'cash', 'amount' => $amountPerPayment ],
                    [ 'label' => 'Tarjeta', 'type' => 'card', 'amount' => $amountPerPayment, 'reference' => '123456789012' ],
                    [ 'label' => 'SINPE Móvil', 'type' => 'sinpe', 'amount' => $amountPerPayment, 'reference' => '123456789012' ],
                ];
                $payments = collect([]);
            @endphp

            @forelse($payments as $payment)
                <div class="payment-item d-flex align-items-center justify-content-between text-start border border-1 border-secondary-subtle rounded-3 p-2" style="background-color: rgba(0, 0, 0, 0.05);" data-payment-type="{{ $payment['type'] }}">
                    <div class="d-flex flex-column align-items-start">
                        <span class="fw-bold" style="font-size: 1rem;">{{ $payment['label'] }}</span>
                        @isset($payment['reference'])
                        <span class="text-muted" style="font-size: 0.75rem;">
                            Referencia: <span class="payment-item-reference">{{ $payment['reference'] }}</span>
                        </span>
                        @endisset
                    </div>
                    <div class="d-flex align-items-center justify-content-end gap-3">
                        <span class="d-flex fw-bolder text-success align-items-center justify-content-center" style="font-size: 1rem;">
                            <x-icons.colon-icon class="me-1" />
                            <span class="payment-item-amount">{{ format_crc($payment['amount'], false) }}</span>
                        </span>
                        <x-form.button :type="'button'" :class="'remove-payment-btn btn-sm btn-outline-danger pt-2'" data-bs-toggle="tooltip" data-bs-title="Eliminar este pago del resumen." style="font-size: 0.75rem;">
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="bi bi-trash"></i>
                            </div>
                        </x-form.button>
                    </div>
                </div>
            @empty
                <div id="no-payments-message" class="d-flex flex-column flex-grow-1 justify-content-center align-items-center py-3 text-center text-muted">
                    <i class="bi bi-receipt fs-1 mb-3"></i>
                    <span class="fs-6">No se han agregado pagos.<br> Agrega un pago para ver el resumen aquí.</span>
                </div>
            @endforelse
        </div>

        {{-- Totals --}}
        <div class="d-flex flex-column align-items-start gap-1 py-3 mx-2">
            <div class="d-flex justify-content-between align-items-center gap-2 w-100">
                <span class="fw-bold">Total Factura:</span>
                <span class="d-flex fw-bolder align-items-center">
                    <x-icons.colon-icon class="me-1" />
                    <span class="fs-6">{{ format_crc($paymentTotal, false) }}</span>
                </span>
            </div>

            <div class="d-flex justify-content-between align-items-center gap-2 w-100">
                <span class="fw-bold">Total Pagado:</span>
                <span class="d-flex fw-bolder align-items-center">
                    <x-icons.colon-icon class="me-1" />
                    <span id="total-paid" class="fs-6">0</span>
                </span>
            </div>

            <div class="d-flex justify-content-between align-items-center gap-2 w-100">
                <span class="fw-bold">Vuelto:</span>
                <span class="d-flex fw-bolder text-success align-items-center">
                    <x-icons.colon-icon class="me-1" />
                    <span id="change_amount" class="fs-6">0</span>
                </span>
            </div>
        </div>

        {{-- Actions --}}
        <div class="d-flex flex-column gap-2 border-top">
            <div class="d-flex justify-content-between align-items-center gap-2 px-1 pt-3 pb-2 w-100">
                <div class="d-flex flex-shrink-0 justify-content-center align-items-center border border-1 border-success bg-success-subtle rounded-3 pt-1" style="width: 36px; height: 36px; transition: all 0.3s">
                    <i class="bi bi-printer text-success fs-6"></i>
                </div>
                <span class="fw-semibold" style="flex: 1; transition: color 0.3s;">
                    Imprimir Ticket
                </span>
                <x-form.input.switch-button :id="'print_receipt_switch'" :name="'print_receipt'" :class="'switch'" :style="'width: 2.375rem; height: 1.3rem;'" :labelClass="'d-none'" :containerClass="'ms-2'" checked />
            </div>

            <x-form.button :id="'payment-button'" :spinnerId="'payment-spinner'" :class="'btn-primary px-4 py-2 w-100'" :loadingMessage="'Cargando...'" data-bs-toggle="tooltip" data-bs-title="Procesar el pago y generar un ticket de venta.">
                <div id="payment-button-text" class="d-flex flex-row align-items-center justify-content-center">
                    <i class="bi bi-receipt me-2"></i>
                    Completar Venta
                </div>
            </x-form.button>
        </div>
    </form>
</div>