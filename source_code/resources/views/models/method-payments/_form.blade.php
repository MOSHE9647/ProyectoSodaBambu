<div class="container p-0">
    {{-- Page Header --}}
    <x-header
        title="{{ isset($payment) ? 'Editar Método de Pago' : 'Crear Método de Pago' }}"
        subtitle="{{ isset($payment) ? 'Modifica la información del método de pago existente' : 'Registre un nuevo método de pago' }}"
    />

    {{-- Form Container --}}
    <div class="table-container rounded-2 p-4 w-75 justify-content-start">
        <form
            id="{{ isset($payment) ? 'edit-method-payment-form' : 'create-method-payment-form' }}"
            action="{{ $action }}" method="POST" class="d-flex flex-column gap-2"
        >
            {{-- CSRF Token --}}
            @csrf
            @if(isset($payment))
                @method('PUT')
            @endif

            {{-- SECTION 1: Basic Information --}}
            <section id="basic-information" class="d-flex flex-column mb-4 gap-3">
                <h5 class="text-muted pb-3 border-bottom border-secondary">
                    <i class="bi bi-credit-card me-3"></i>
                    Información Básica
                </h5>

                <div class="row g-3">
                    {{-- Amount --}}
                    <div class="col-6">
                        <x-form.input
                            :id="'amount'"
                            :type="'number'"
                            :class="'border-secondary'"
                            :inputClass="$errors->has('amount') ? 'is-invalid' : ''"
                            :placeholder="'Ej: 10000.00'"
                            :step="'0.01'"
                            :min="'0'"
                            :value="old('amount', optional($payment)->amount ?? '')"
                            :errorMessage="$errors->first('amount') ?? ''"
                            :iconLeft="'bi bi-cash-coin'"
                            :required="true"
                        >
                            Monto <span class="text-danger">*</span>
                        </x-form.input>
                    </div>

                    {{-- Payment Type --}}
                    <div class="col-6">
                        <x-form.select
                            :id="'type_payment'"
                            :class="'border-secondary'"
                            :selectClass="$errors->has('type_payment') ? 'is-invalid' : ''"
                            :errorMessage="$errors->first('type_payment') ?? ''"
                            :iconLeft="'bi bi-wallet2'"
                            :required="true"
                        >
                            Tipo de Pago <span class="text-danger">*</span>
                            <x-slot:options>
                                <option value="-1">Seleccionar tipo...</option>
                                <option value="sinpe" {{ old('type_payment', optional($payment)->type_payment ?? '') == 'sinpe' ? 'selected' : '' }}>SINPE</option>
                                <option value="card" {{ old('type_payment', optional($payment)->type_payment ?? '') == 'card' ? 'selected' : '' }}>Tarjeta</option>
                                <option value="cash" {{ old('type_payment', optional($payment)->type_payment ?? '') == 'cash' ? 'selected' : '' }}>Efectivo</option>
                            </x-slot:options>
                        </x-form.select>
                    </div>
                </div>
            </section>

            {{-- SECTION 2: Conditional Fields --}}
            <section
                id="conditional-fields"
                class="flex-column mb-4 gap-3"
            >
                {{-- SINPE Fields --}}
                <div id="sinpe-fields" style="display: {{ old('type_payment', optional($payment)->type_payment ?? '') == 'sinpe' ? 'block' : 'none' }};">
                    <h5 class="text-muted pt-2 pb-3 border-bottom border-secondary">
                        <i class="bi bi-phone me-3"></i>
                        Información SINPE
                    </h5>
                    <div class="row g-3 mt-2">
                        <div class="col-12">
                            <x-form.input
                                :id="'voucher'"
                                :type="'text'"
                                :class="'border-secondary'"
                                :inputClass="$errors->has('voucher') ? 'is-invalid' : ''"
                                :errorMessage="$errors->first('voucher') ?? ''"
                                :placeholder="'Número de comprobante'"
                                :value="old('voucher', optional($payment)->sinpePayment->voucher ?? '')"
                                :iconLeft="'bi bi-receipt'"
                                :required="old('type_payment', optional($payment)->type_payment ?? '') == 'sinpe'"
                            >
                                Comprobante
                            </x-form.input>
                        </div>
                    </div>
                </div>

                {{-- Card Fields --}}
                <div id="card-fields" style="display: {{ old('type_payment', optional($payment)->type_payment ?? '') == 'card' ? 'block' : 'none' }};">
                    <h5 class="text-muted pt-2 pb-3 border-bottom border-secondary">
                        <i class="bi bi-credit-card-2-front me-3"></i>
                        Información de Tarjeta
                    </h5>
                    <div class="row g-3 mt-2">
                        <div class="col-12">
                            <x-form.input
                                :id="'reference'"
                                :type="'text'"
                                :class="'border-secondary'"
                                :inputClass="$errors->has('reference') ? 'is-invalid' : ''"
                                :errorMessage="$errors->first('reference') ?? ''"
                                :placeholder="'Número de referencia'"
                                :value="old('reference', optional($payment)->cardPayment->reference ?? '')"
                                :iconLeft="'bi bi-arrow-left-right'"
                                :required="old('type_payment', optional($payment)->type_payment ?? '') == 'card'"
                            >
                                Referencia
                            </x-form.input>
                        </div>
                    </div>
                </div>

                {{-- Cash Fields --}}
                <div id="cash-fields" style="display: {{ old('type_payment', optional($payment)->type_payment ?? '') == 'cash' ? 'block' : 'none' }};">
                    <h5 class="text-muted pt-2 pb-3 border-bottom border-secondary">
                        <i class="bi bi-cash-stack me-3"></i>
                        Información de Efectivo
                    </h5>
                    <div class="row g-3 mt-2">
                        <div class="col-12">
                            <x-form.input
                                :id="'changeAmount'"
                                :type="'number'"
                                :class="'border-secondary'"
                                :inputClass="$errors->has('changeAmount') ? 'is-invalid' : ''"
                                :errorMessage="$errors->first('changeAmount') ?? ''"
                                :placeholder="'Ej: 500.00'"
                                :step="'0.01'"
                                :min="'0'"
                                :value="old('changeAmount', optional($payment)->cashPayment->changeAmount ?? '')"
                                :iconLeft="'bi bi-currency-exchange'"
                                :required="old('type_payment', optional($payment)->type_payment ?? '') == 'cash'"
                            >
                                Monto de Cambio
                            </x-form.input>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Form Actions --}}
            <div class="d-flex justify-content-end gap-2">
                {{-- Cancel Button --}}
                <a href="{{ route('method-payments.index') }}" class="btn btn-outline-danger px-4">
                    Cancelar
                </a>

                {{-- Submit Button --}}
                <x-form.submit
                    :id="isset($payment) ? 'edit-method-payment-form-button' : 'create-method-payment-form-button'"
                    :spinnerId="isset($payment) ? 'edit-method-payment-form-spinner' : 'create-method-payment-form-spinner'"
                    :class="'btn-primary px-4'"
                    :loadingMessage="isset($payment) ? 'Actualizando...' : 'Guardando...'"
                >
                    <div
                        id="{{ isset($payment) ? 'edit-method-payment-form-button-text' : 'create-method-payment-form-button-text' }}"
                        class="d-flex flex-row align-items-center justify-content-center"
                    >
                        <i class="bi bi-credit-card me-2"></i>
                        {{ isset($payment) ? 'Actualizar' : 'Guardar' }}
                    </div>
                </x-form.submit>
            </div>
        </form>
    </div>
</div>

@section('scripts')
    <script type="text/javascript">
        // Show/hide conditional fields based on selected payment type
        const typePaymentSelect = document.getElementById('type_payment');
        typePaymentSelect.addEventListener('change', function () {
            const sinpeFields = document.getElementById('sinpe-fields');
            const cardFields = document.getElementById('card-fields');
            const cashFields = document.getElementById('cash-fields');

            // Hide all conditional fields
            sinpeFields.style.display = 'none';
            cardFields.style.display = 'none';
            cashFields.style.display = 'none';

            // Show the selected one
            switch (this.value) {
                case 'sinpe':
                    sinpeFields.style.display = 'block';
                    break;
                case 'card':
                    cardFields.style.display = 'block';
                    break;
                case 'cash':
                    cashFields.style.display = 'block';
                    break;
            }
        });

        // Trigger change event on page load to set initial state
        if (typePaymentSelect) {
            typePaymentSelect.dispatchEvent(new Event('change'));
        }
    </script>
    @vite(['resources/js/models/method-payments/form.js'])
@endsection