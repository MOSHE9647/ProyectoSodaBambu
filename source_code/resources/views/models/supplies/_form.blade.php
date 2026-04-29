@php
    // Detects if the form is being rendered inside an offcanvas
    $isOffcanvas ??= false;

    $isEdit = isset($supply);
    $formId = $isEdit ? 'edit-supply-form' : 'create-supply-form';
    $actionUrl = $action ?? route('supplies.store');
@endphp

@if(! $isOffcanvas)
    {{-- Header --}}
    <x-header 
        :title="$isEdit ? 'Editar Insumo' : 'Crear Insumo'" 
        :subtitle="$isEdit ? 'Actualice los datos del insumo' : 'Registra un nuevo insumo para el inventario'" 
    />

    {{-- Form Container --}}
    <div class="table-container rounded-2 p-4 w-75 justify-content-start">
@else
    {{-- Container for offcanvas (full width, no card styling) --}}
    <div class="container-fluid px-0">
@endif

    <form id="{{ $formId }}" action="{{ $actionUrl }}" method="POST" class="d-flex flex-column gap-2">
        @csrf
        @if($isEdit) 
            @method('PUT')
        @endif

        <section id="basic-information" class="d-flex flex-column mb-4 gap-3">
            @if(! $isOffcanvas)
                <h5 class="text-muted pb-3 border-bottom border-secondary">
                    <i class="bi bi-box-seam me-3"></i>
                    Información del Insumo
                </h5>
            @endif

            <div class="row g-3">
                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.input
                        :id="'name'"
                        :type="'text'"
                        :minlength="'1'"
                        :maxlength="'50'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('name') ? 'is-invalid' : ''"
                        :placeholder="'Ej: Harina de Trigo'"
                        :value="old('name', $supply?->name ?? '')"
                        :errorMessage="$errors->first('name') ?? ''"
                        :iconLeft="'bi bi-type'"
                        :required="true"
                    >
                        Nombre del Insumo <span class="text-danger">*</span>
                    </x-form.input>
                </div>

                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.input
                        :id="'measure_unit'"
                        :type="'text'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('measure_unit') ? 'is-invalid' : ''"
                        :placeholder="'Ej: Kilogramos'"
                        :value="old('measure_unit', $supply?->measure_unit ?? '')"
                        :errorMessage="$errors->first('measure_unit') ?? ''"
                        :iconLeft="'bi bi-rulers'"
                        :textIconRight="true"
                        :required="true"
                    >
                        Unidad de Medida <span class="text-danger">*</span>

                        <x-slot:iconRight>
                            <i 
                                class="bi bi-question-circle"
                                data-bs-toggle="tooltip"
                                data-bs-title="Unidad de medida para el insumo (Ej: kg, litros, unidades, etc.)"
                            ></i>
                        </x-slot:iconRight>
                    </x-form.input>
                </div>

                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.input
                        :id="'quantity'"
                        :type="'number'"
                        :min="'0'"
                        :step="'1'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('quantity') ? 'is-invalid' : ''"
                        :value="old('quantity', $supply?->quantity ?? '0')"
                        :errorMessage="$errors->first('quantity') ?? ''"
                        :iconLeft="'bi bi-stack'"
                        :placeholder="'0'"
                        :textIconRight="true"
                        :required="true"
                    >
                        Cantidad Inicial <span class="text-danger">*</span>

                        <x-slot:iconRight>
                            <i 
                                class="bi bi-question-circle"
                                data-bs-toggle="tooltip"
                                data-bs-title="Cantidad inicial del insumo según unidad de medida."
                            ></i>
                        </x-slot:iconRight>
                    </x-form.input>
                </div>

                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.input
                        :id="'unit_price'"
                        :type="'number'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('unit_price') ? 'is-invalid' : ''"
                        :value="old('unit_price', $supply?->unit_price ?? '0')"
                        :errorMessage="$errors->first('unit_price') ?? ''"
                        :textIconLeft="true"
                        :textIconRight="true"
                        :placeholder="'0.00'"
                        :required="true"
                        :min="'0'"
                        :step="'1'"
                    >
                        <x-slot:iconLeft>
                            <x-icons.colon-icon width="16" height="16" />
                        </x-slot:iconLeft>

                        Precio Unitario <span class="text-danger">*</span>

                        <x-slot:iconRight>
                            <i 
                                class="bi bi-question-circle"
                                data-bs-toggle="tooltip"
                                data-bs-title="Costo por unidad de medida del insumo. Este valor se usará para calcular el costo total del inventario."
                            ></i>
                        </x-slot:iconRight>
                    </x-form.input>
                </div>

                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.input
                        :id="'expiration_date'"
                        :type="'date'"
                        :min="date('Y-m-d')"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('expiration_date') ? 'is-invalid' : ''"
                        :value="old('expiration_date', $supply?->expiration_date ? $supply?->expiration_date->format('Y-m-d') : '')"
                        :errorMessage="$errors->first('expiration_date') ?? ''"
                        :iconLeft="'bi bi-calendar-event'"
                    >
                        Fecha de Vencimiento
                    </x-form.input>
                </div>

                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.input
                        :id="'expiration_alert_days'"
                        :type="'number'"
                        :min="'0'"
                        :step="'1'"
                        :max="'365'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('expiration_alert_days') ? 'is-invalid' : ''"
                        :placeholder="'7'"
                        :value="old('expiration_alert_days', $supply?->expiration_alert_days ?? '7')"
                        :errorMessage="$errors->first('expiration_alert_days') ?? ''"
                        :iconLeft="'bi bi-bell'"
                        :textIconRight="true"
                    >
                        Alertar con (días)

                        <x-slot:iconRight>
                            <i 
                                class="bi bi-question-circle"
                                data-bs-toggle="tooltip"
                                data-bs-title="Número de días antes de la fecha de vencimiento para recibir una alerta"
                            ></i>
                        </x-slot:iconRight>
                    </x-form.input>
                    <small class="form-text text-muted {{ $supply?->expiration_alert_date ? '' : 'd-none' }}" id="expiration-alert-date-container">
                        <span class="fw-bold">Fecha de alerta: </span>
                        <span id="expiration-alert-date">
                            {{ $supply?->expiration_alert_date ? $supply->expiration_alert_date->translatedFormat('j \d\e F \d\e Y') : '' }}
                        </span>
                    </small>
                </div>
            </div>
        </section>

        {{-- Form Actions --}}
        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
            @if(! $isOffcanvas)
                <a href="{{ route('supplies.index') }}" class="btn btn-outline-danger px-4">Cancelar</a>
            @endif
            
            {{-- Submit Button --}}
            <x-form.button 
                :id="'create-supply-form-button'"
                :spinnerId="'create-supply-form-spinner'"
                :class="'btn-primary px-4'"
                :loadingMessage="'Guardando...'"
            >
                <div id="create-supply-form-button-text" class="d-flex flex-row align-items-center justify-content-center">
                    <i class="bi bi-check-circle me-2"></i>
                    Guardar
                </div>
            </x-form.button>
        </div>
    </form>
</div>

@section('scripts')
    @vite(['resources/js/models/supplies/form.js'])
@endsection