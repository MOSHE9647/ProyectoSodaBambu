@php
    use App\Enums\MeasureUnit;

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
                        :id="'brand'"
                        :type="'text'"
                        :minlength="'1'"
                        :maxlength="'50'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('brand') ? 'is-invalid' : ''"
                        :placeholder="'Ej: Flores'"
                        :value="old('brand', $supply?->brand ?? '')"
                        :errorMessage="$errors->first('brand') ?? ''"
                        :iconLeft="'bi bi-card-text'"
                        :required="false"
                    >
                        Marca
                    </x-form.input>
                </div>

                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.dropdown 
                        :id="'measure_unit_selector'"
                        :name="'measure_amount'"
                        :type="'number'"
                        :unitName="'measure_unit'"
                        :placeholder="'Ej. 500 Kg'" 
                        :dropdownPosition="'end'"
                        :iconLeft="'bi bi-rulers'"
                        :inputClass="$errors->has('measure_amount') || $errors->has('measure_unit') ? 'is-invalid' : ''"
                        :value="old('measure_amount', $isEdit ? $supply->measure_amount : '')"
                        :unitValue="old('measure_unit', $isEdit ? $supply->measure_unit?->value : '')"
                        :unitLabel="old('measure_unit', $isEdit ? $supply->measure_unit?->label() : 'Seleccionar')" 
                        :errorMessage="$errors->first('measure_unit') ?? $errors->first('measure_amount')"
                        :required="true"
                        :min="'0'"
                        :step="'1'"
                    >
                        @slot('displayName')
                            Cantidad por Unidad de Medida <span class="text-danger">*</span>
                        @endslot
                        @foreach (MeasureUnit::cases() as $unit)
                        <li>
                            <a class="dropdown-item" href="" data-value="{{ $unit->value }}">
                                {{ $unit->label() }}
                            </a>
                        </li>
                        @endforeach
                    </x-form.dropdown>
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
                        Cantidad de Unidades <span class="text-danger">*</span>

                        <x-slot:iconRight>
                            <i 
                                class="bi bi-question-circle"
                                data-bs-toggle="tooltip"
                                data-bs-title="Cantidad a ingresar del insumo. Ej: Si ingresas 2 y en el campo anterior ingresaste 23 Kilogramos, el sistema entenderá que estás agregando 2 unidades de 23 Kilogramos de ese insumo."
                            ></i>
                        </x-slot:iconRight>
                    </x-form.input>
                </div>

                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.input
                        :id="'expiration_date'"
                        :type="'date'"
                        :min="$isEdit ? $supply->expiration_date?->format('Y-m-d') : now()->timezone('America/Costa_Rica')->format('Y-m-d')"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('expiration_date') ? 'is-invalid' : ''"
                        :value="old('expiration_date', $supply?->expiration_date?->format('Y-m-d') ?? '')"
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
                        :value="old('expiration_alert_days', $supply->expiration_alert_days ?? '7')"
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
                            {{ $supply?->expiration_alert_date?->translatedFormat('j \d\e F \d\e Y') ?? '' }}
                        </span>
                    </small>
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
                        :placeholder="'150'"
                        :required="true"
                        :min="'0'"
                        :step="'5'"
                    >
                        <x-slot:iconLeft>
                            <x-icons.colon-icon width="16" height="16" />
                        </x-slot:iconLeft>

                        Precio Unitario <span class="text-danger">*</span>

                        <x-slot:iconRight>
                            <i 
                                class="bi bi-question-circle"
                                data-bs-toggle="tooltip"
                                data-bs-title="Costo por unidad ingresada del insumo. Ej: Si el precio unitario es 150 y en el campo de cantidad de unidades ingresaste 2, el sistema entenderá que estás agregando 2 unidades de ese insumo con un costo de ₡150 cada una."
                            ></i>
                        </x-slot:iconRight>
                    </x-form.input>
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
    <script type="text/javascript">
        window.SUPPLY_FORM_DATA = {
            measureUnits: @json(
                collect(MeasureUnit::cases())->map(fn($m) => [
                    'value' => $m->value, 'label' => $m->label()
                ])
            ),
        };
    </script>

    @vite(['resources/js/models/supplies/form.js'])
@endsection