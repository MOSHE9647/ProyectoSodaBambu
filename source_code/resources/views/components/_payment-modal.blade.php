@vite(['resources/css/app.css', 'resources/js/app.js'])

@php
    use App\Enums\PaymentMethod;
@endphp

<form id="create-payment-form" class="d-flex flex-column flex-grow-1" style="max-width: 46rem !important;">

    <div class="d-flex gap-3 justify-content-between">
        
        <section id="method-payment-section" class="table-container w-50 flex-column rounded-3 shadow-sm overflow-hidden p-2 pb-3">
            
            {{-- Total amount --}}
            <div class="d-flex justify-content-between align-items-center gap-2 border-bottom px-3 pt-2 pb-2 mb-2">
                <span class="fs-5 fw-bold">Total a Pagar:</span>
                <span class="d-flex fw-bolder text-success align-items-center" style="font-size: 1.5rem;">
                    <x-icons.colon-icon class="me-1" />
                    {{ number_format($paymentTotal, 0, ',', ' ') }}
                </span>
            </div>

            {{-- Payment Method --}}
            <x-form.input.radio-group :groupClass="'d-flex flex-column gap-2'">
                <x-form.input.radio-button 
                    :id="'payment_method_cash'"
                    :name="'payment_method'"
                    :value="PaymentMethod::CASH->value"
                    :class="'d-flex align-items-center justify-content-between rounded-3 text-start'"
                    :checked="true"
                >
                    <div class="d-flex align-items-center p-2 rounded check-button">
                        <i class="bi bi-cash-coin fs-4 me-4"></i>
                        <span class="fs-6">Efectivo</span>
                    </div>
                    <i class="checked bi bi-check-circle fs-6 me-2"></i>
                </x-form.input.radio-button>

                <x-form.input.radio-button
                    :id="'payment_method_card'"
                    :name="'payment_method'"
                    :value="PaymentMethod::CARD->value"
                    :class="'d-flex align-items-center justify-content-between rounded-3 text-start'"
                >
                    <div class="d-flex align-items-center p-2 rounded check-button">
                        <i class="bi bi-credit-card fs-4 me-4"></i>
                        <span class="fs-6">Tarjeta</span>
                    </div>
                    <i class="checked d-none bi bi-check-circle fs-6 me-2"></i>
                </x-form.input.radio-button>

                <x-form.input.radio-button
                    :id="'payment_method_sinpe'"
                    :name="'payment_method'"
                    :value="PaymentMethod::SINPE->value"
                >
                    <div class="d-flex align-items-center p-2 rounded check-button">
                        <x-icons.sinpe-movil class="fs-4 me-4" />
                        <span class="fs-6">SINPE Móvil</span>
                    </div>
                    <i class="checked d-none bi bi-check-circle fs-6 me-2"></i>
                </x-form.input.radio-button>
            </x-form.input.radio-group>

            <div class="d-flex flex-column align-items-start gap-2 border-top pt-2 mt-3">
                {{-- Amount --}}
                <x-form.input
                    :id="'amount-to-pay'"
                    :type="'number'"
                    :step="'1'"
                    :min="'0'"
                    :class="'border-secondary w-100'"
                    :value="$paymentTotal"
                    :placeholder="'Ej: 1000'"
                    :textIconLeft="true"
                    :required="true"
                >
                    <x-slot:iconLeft>
                        <x-icons.colon-icon width="16" height="16" />
                    </x-slot:iconLeft>
                    Monto a pagar: <span class="text-danger">*</span>
                </x-form.input>

                <x-form.input
                    :id="'reference-number'"
                    :type="'numeric'"
                    :class="'border-secondary w-100'"
                    :placeholder="'Ej: 1234567890123'" 
                    :iconLeft="'bi bi-credit-card-2-back'"
                    :textIconRight="true"
                    :required="false"
                >
                    Referencia de Pago: <span class="text-danger">*</span>
                    <x-slot:iconRight>
                        <i 
                            class="bi bi-question-circle"
                            data-bs-toggle="tooltip"
                            data-bs-title="Número de referencia del pago, como el número de transacción o código de autorización. Útil para llevar un registro más detallado de los pagos realizados mediante Tarjeta o SINPE Móvil."
                        ></i>
                    </x-slot:iconRight>
                </x-form.input>
            </div>

            <x-form.button 
                :id="'add-payment-button'"
                :class="'btn-warning px-4 py-2 mt-3 w-100'"
                :type="'button'"
            >
                <div id="add-payment-form-button-text" class="d-flex flex-row align-items-center justify-content-center">
                    <i class="bi bi-plus-lg me-2"></i>
                    Agregar Pago
                </div>
            </x-form.button>

        </section>

        <section id="details-section" class="table-container w-50 d-flex flex-column rounded-3 shadow-sm overflow-hidden p-2 pb-3">

            {{-- Details Header --}}
            <div class="d-flex justify-content-center align-items-center border-bottom px-3 py-2 mb-2" style="height: 55.2px;">
                <span class="fs-5 fw-bold">Resumen de Pagos</span>
            </div>

            {{-- Details Content --}}
            <div id="payment-details" class="d-flex flex-column flex-grow-1 gap-1 overflow-auto">
                <div id="no-payments-message" class="d-none flex-column flex-grow-1 justify-content-center align-items-center text-center text-muted">
                    <i class="bi bi-receipt fs-1 mb-3"></i>
                    <p>No se han agregado pagos.<br> Agrega un pago para ver el resumen aquí.</p>
                </div>

                <div class="payment-item d-flex align-items-center justify-content-between text-start p-2">
                    <div class="d-flex flex-column align-items-start">
                        <span class="fw-bold" style="font-size: 1rem;">Efectivo</span>
                    </div>
                    <div class="d-flex align-items-end justify-content-end gap-3">
                        <span class="d-flex fw-bolder text-success align-items-center" style="font-size: 1rem;">
                            <x-icons.colon-icon class="me-1" />
                            15 000
                        </span>
                        <x-form.button 
                            :id="'remove-payment-1'"
                            :class="'btn-sm btn-outline-danger p-2'"
                            :type="'button'"
                            data-bs-toggle="tooltip"
                            data-bs-title="Eliminar este pago del resumen."
                            style="font-size: 0.75rem;"
                        >
                            <div id="remove-payment-1-text" class="d-flex flex-row align-items-center justify-content-center">
                                <i class="bi bi-trash"></i>
                            </div>
                        </x-form.button>
                    </div>
                </div>

                <div class="payment-item d-flex align-items-center justify-content-between text-start p-2">
                    <div class="d-flex flex-column align-items-start">
                        <span class="fw-bold" style="font-size: 1rem;">Tarjeta</span>
                        <span class="text-muted" style="font-size: 0.75rem;">Referencia: 1234567890123</span>
                    </div>
                    <div class="d-flex align-items-end justify-content-end gap-3">
                        <span class="d-flex fw-bolder text-success align-items-center" style="font-size: 1rem;">
                            <x-icons.colon-icon class="me-1" />
                            15 000
                        </span>
                        <x-form.button 
                            :id="'remove-payment-1'"
                            :class="'btn-sm btn-outline-danger p-2'"
                            :type="'button'"
                            data-bs-toggle="tooltip"
                            data-bs-title="Eliminar este pago del resumen."
                            style="font-size: 0.75rem;"
                        >
                            <div id="remove-payment-1-text" class="d-flex flex-row align-items-center justify-content-center">
                                <i class="bi bi-trash"></i>
                            </div>
                        </x-form.button>
                    </div>
                </div>

                <div class="payment-item d-flex align-items-center justify-content-between text-start p-2">
                    <div class="d-flex flex-column align-items-start">
                        <span class="fw-bold" style="font-size: 1rem;">SINPE Móvil</span>
                        <span class="text-muted" style="font-size: 0.75rem;">Comprobante: 1234567890123</span>
                    </div>
                    <div class="d-flex align-items-end justify-content-end gap-3">
                        <span class="d-flex fw-bolder text-success align-items-center" style="font-size: 1rem;">
                            <x-icons.colon-icon class="me-1" />
                            15 000
                        </span>
                        <x-form.button 
                            :id="'remove-payment-1'"
                            :class="'btn-sm btn-outline-danger p-2'"
                            :type="'button'"
                            data-bs-toggle="tooltip"
                            data-bs-title="Eliminar este pago del resumen."
                            style="font-size: 0.75rem;"
                        >
                            <div id="remove-payment-1-text" class="d-flex flex-row align-items-center justify-content-center">
                                <i class="bi bi-trash"></i>
                            </div>
                        </x-form.button>
                    </div>
                </div>
            </div>

            {{-- Totals --}}
            <div class="d-flex flex-column align-items-start gap-1 border-top py-3 mx-2 mt-2">
                <div class="d-flex justify-content-between align-items-center gap-2 w-100">
                    <span class="fw-bold">Total Factura:</span>
                    <span class="d-flex fw-bolder align-items-center">
                        <x-icons.colon-icon class="me-1" />
                        <span id="total-invoice" class="fs-6">{{ number_format($paymentTotal, 0, ',', ' ') }}</span>
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
                        <span id="total-change" class="fs-6">0</span>
                    </span>
                </div>
            </div>

            <div class="d-flex flex-row gap-2 pt-3 border-top w-100">
                <x-form.button 
                    :id="'payment-button-no-ticket'"
                    :spinnerId="'payment-spinner-no-ticket'"
                    :class="'btn-info px-4 py-2 w-25'"
                    :loadingMessage="'Cargando...'"
                    data-bs-toggle="tooltip"
                    data-bs-title="Procesar el pago sin generar un ticket de venta."
                >
                    <div id="payment-button-no-ticket-text" class="d-flex flex-row align-items-center justify-content-center">
                        <i class="bi bi-dash-circle"></i>
                    </div>
                </x-form.button>

                <x-form.button 
                    :id="'payment-button'"
                    :spinnerId="'payment-spinner'"
                    :class="'btn-primary px-4 py-2 w-75'"
                    :loadingMessage="'Cargando...'"
                    data-bs-toggle="tooltip"
                    data-bs-title="Procesar el pago y generar un ticket de venta."
                >
                    <div id="payment-button-text" class="d-flex flex-row align-items-center justify-content-center">
                        <i class="bi bi-receipt me-2"></i>
                        Completar Venta
                    </div>
                </x-form.button>
            </div>
        </section>
    </div>

</form>