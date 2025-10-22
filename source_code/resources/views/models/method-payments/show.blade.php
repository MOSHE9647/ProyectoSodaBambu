<div class="d-flex flex-column text-start">
    {{-- Basic Information --}}
    <div class="row g-3 mb-3">
        <div class="col-6">
            <x-form.input.floating-label
                :id="'amount'"
                :type="'text'"
                :readonly="true"
                :value="'₡ ' . number_format($payment->amount, 2)"
                :placeholder="'Monto'"
                :iconLeft="'bi bi-cash-coin'"
            >
                Monto
            </x-form.input.floating-label>
        </div>
        <div class="col-6">
            <x-form.input.floating-label
                :id="'type_payment'"
                :type="'text'"
                :readonly="true"
                :value="\App\Http\Controllers\MethodPaymentController::getPaymentTypeLabel($payment->type_payment)"
                :placeholder="'Tipo de Pago'"
                :iconLeft="'bi bi-wallet2'"
            >
                Tipo de Pago
            </x-form.input.floating-label>
        </div>
    </div>

    {{-- Conditional Fields --}}
    @if($payment->type_payment == 'sinpe' && $payment->sinpePayment)
        <hr class="my-4"/>
        <div class="row g-3">
            <div class="col-12">
                <x-form.input.floating-label
                    :id="'voucher'"
                    :type="'text'"
                    :readonly="true"
                    :value="$payment->sinpePayment->voucher"
                    :placeholder="'Comprobante'"
                    :iconLeft="'bi bi-receipt'"
                >
                    Comprobante
                </x-form.input.floating-label>
            </div>
        </div>
    @endif

    @if($payment->type_payment == 'card' && $payment->cardPayment)
        <hr class="my-4"/>
        <div class="row g-3">
            <div class="col-12">
                <x-form.input.floating-label
                    :id="'reference'"
                    :type="'text'"
                    :readonly="true"
                    :value="$payment->cardPayment->reference"
                    :placeholder="'Referencia'"
                    :iconLeft="'bi bi-arrow-left-right'"
                >
                    Referencia
                </x-form.input.floating-label>
            </div>
        </div>
    @endif

    @if($payment->type_payment == 'cash' && $payment->cashPayment)
        <hr class="my-4"/>
        <div class="row g-3">
            <div class="col-12">
                <x-form.input.floating-label
                    :id="'changeAmount'"
                    :type="'text'"
                    :readonly="true"
                    :value="'₡ ' . number_format($payment->cashPayment->changeAmount, 2)"
                    :placeholder="'Monto de Cambio'"
                    :iconLeft="'bi bi-currency-exchange'"
                >
                    Monto de Cambio
                </x-form.input.floating-label>
            </div>
        </div>
    @endif
</div>