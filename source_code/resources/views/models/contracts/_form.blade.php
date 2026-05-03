@php
    use App\Enums\MealTime;
    use App\Enums\PaymentMethod;
    use App\Enums\PaymentStatus;
    use App\Enums\WeekDay;

    $isEditing = isset($contract);

    $pageTitle = $isEditing ? 'Editar Contrato' : 'Nuevo Contrato';
    $pageSubtitle = $isEditing ? 'Modifica la información del contrato existente' : 'Registra un nuevo contrato con un cliente';

    $formId = $isEditing ? 'edit-contract-form' : 'create-contract-form';
    $actionUrl = $isEditing ? route('contracts.update', $contract) : route('contracts.store');

    $paymentStatuses = [PaymentStatus::PENDING, PaymentStatus::PAID];
    $paymentMethods = PaymentMethod::cases();
    $mealTimes = MealTime::cases();
    $weekDays = WeekDay::cases();
@endphp

<x-header title="{{ $pageTitle }}" subtitle="{{ $pageSubtitle }}" />

<form id="{{ $formId }}" action="{{ $actionUrl }}" method="POST" class="container-fluid px-0 pb-0">

    @csrf

    @if($isEditing)
        @method('PUT')
    @endif

    <div class="row g-3 align-items-start">

        {{-- Left Column --}}
        <section class="col-lg-8">
    
            {{-- Client Information --}}
            <div id="client-information" class="card-container justify-content-start rounded-2 p-4 mb-3">
    
                {{-- Client Information Header --}}
                <div class="d-flex flex-column pb-3 mb-3 border-bottom border-secondary">
                    <span class="d-flex align-items-center gap-3 fs-5 fw-bold">
                        <i class="bi bi-person-check"></i>
                        Contratante
                    </span>
                    <small class="text-muted d-block">
                        Seleccione el cliente asociado a este contrato.
                    </small>
                </div>
        
                {{-- Client Selection --}}
                <div class="d-flex flex-column justify-content-start align-items-start gap-2">
                    <x-form.select
                        :id="'client_id'"
                        :class="'border-secondary w-100'"
                        :inputClass="$errors->has('client_id') ? 'is-invalid' : ''"
                        :placeholder="'Seleccione un cliente'"
                        :value="old('client_id', $contract?->client_id ?? '')"
                        :errorMessage="$errors->first('client_id') ?? ''"
                        :iconLeft="'bi bi-person-check'"
                        :required="true"
                    >
                        Buscar cliente <span class="text-danger">*</span>
        
                        <x-slot:options>
                            <option value="-1">Seleccione un cliente</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id', $contract?->client_id ?? '') == $client->id ? 'selected' : '' }}>
                                    {{ $client->full_name }}
                                </option>
                            @endforeach
                        </x-slot:options>
        
                        <x-slot:buttonIconRight>
                            <button
                                id="add-client-btn"
                                type="button"
                                class="btn btn-sm btn-offcanvas btn-outline-primary rounded-end-2"
                                data-bs-toggle="tooltip"
                                data-bs-title="Agregar nuevo cliente"
                                data-type="client"
                            >
                                <div class="add-client-spinner d-none flex-row align-items-center justify-content-center mx-2">
                                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                </div>
                                
                                <div class="add-client-button-text d-flex flex-row align-items-center justify-content-center gap-1">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    <span>Nuevo</span>
                                </div>
                            </button>
                        </x-slot:buttonIconRight>
                    </x-form.select>
        
                    <small class="text-muted d-block">
                        Selecciona un cliente existente o crea uno nuevo.
                    </small>
                </div>
    
            </div>

            {{-- Contract Information --}}
            <div id="contract-information" class="card-container justify-content-start rounded-2 p-4 mb-3">

                {{-- Contract Information Header --}}
                <div class="d-flex flex-column pb-3 mb-3 border-bottom border-secondary">
                    <span class="d-flex align-items-center gap-3 fs-5 fw-bold">
                        <i class="bi bi-building"></i>
                        Datos del Contrato
                    </span>
                    <small class="text-muted d-block">
                        Complete la información del contrato, incluyendo empresa, fechas y valor del contrato.
                    </small>
                </div>

                <div class="row g-3">
                    {{-- Business Name --}}
                    <div class="col-md-8">
                        <x-form.input 
                            :id="'business_name'"
                            :type="'text'" 
                            :min="2"
                            :max="100"
                            :class="'border-secondary w-auto'"
                            :inputClass="$errors->has('business_name') ? 'is-invalid' : ''"
                            :placeholder="'Ej: Constructora XYZ'"
                            :value="old('business_name', $contract?->business_name ?? '')"
                            :errorMessage="$errors->first('business_name') ?? ''"
                            :iconLeft="'bi bi-building'"
                            :required="true"
                        >
                            Nombre de la Empresa <span class="text-danger">*</span>
                        </x-form.input>
                    </div>
        
                    {{-- Portions per Day --}}
                    <div class="col-md-4">
                        <x-form.input 
                            :id="'portions_per_day'"
                            :type="'number'"
                            :min="1"
                            :class="'border-secondary w-auto'"
                            :inputClass="$errors->has('portions_per_day') ? 'is-invalid' : ''"
                            :placeholder="'Ej: 30'"
                            :value="old('portions_per_day', $contract?->portions_per_day ?? 1)"
                            :errorMessage="$errors->first('portions_per_day') ?? ''"
                            :iconLeft="'bi bi-people'"
                            :textIconRight="true"
                            :required="true"
                        >
                            Porciones por Día <span class="text-danger">*</span>

                            <x-slot:iconRight>
                                <i 
                                    class="bi bi-question-circle"
                                    data-bs-toggle="tooltip"
                                    data-bs-title="Número de porciones que se servirán cada día. Esto ayuda a calcular el valor total del contrato basado en los detalles agregados."
                                ></i>
                            </x-slot:iconRight>
                        </x-form.input>
                    </div>

                    {{-- Start Date --}}
                    <div class="col-md-6">
                        <x-form.input 
                            :id="'start_date'"
                            :type="'date'" 
                            :class="'border-secondary w-auto'"
                            :min="Carbon\Carbon::now()->timezone('America/Costa_Rica')->format('Y-m-d')"
                            :inputClass="$errors->has('start_date') ? 'is-invalid' : ''"
                            :value="old('start_date', $isEditing ? $contract->start_date->format('Y-m-d') : '')"
                            :errorMessage="$errors->first('start_date') ?? ''"
                            :iconLeft="'bi bi-calendar-check'"
                            :required="true"
                        >
                            Fecha de Inicio <span class="text-danger">*</span>
                        </x-form.input>
                    </div>

                    {{-- End Date --}}
                    <div class="col-md-6">
                        <x-form.input 
                            :id="'end_date'"
                            :type="'date'" 
                            :class="'border-secondary w-auto'"
                            :inputClass="$errors->has('end_date') ? 'is-invalid' : ''"
                            :value="old('end_date', $isEditing ? $contract->end_date->format('Y-m-d') : '')"
                            :errorMessage="$errors->first('end_date') ?? ''"
                            :iconLeft="'bi bi-calendar-x'"
                            :required="true"
                        >
                            Fecha de Finalización <span class="text-danger">*</span>
                        </x-form.input>
                    </div>

                    {{-- Days to Serve --}}
                    <div class="col-md-12">
                        <label class="form-label">Días de servicio</label>
                        <div class="d-flex justify-content-start align-items-baseline gap-2 flex-wrap">
                            {{-- Days to Serve Checkboxes --}}
                            <div id="days_to_serve" class="check-button d-flex flex-wrap gap-2 ps-0 mb-2 {{ $errors->has('days_to_serve') ? 'is-invalid' : '' }}">
                                @foreach ($weekDays as $day)
                                    <input
                                        id="day-{{ $day->value }}"
                                        name="days_to_serve[]"
                                        type="checkbox"
                                        class="btn btn-check"
                                        autocomplete="off"
                                        value="{{ $day->value }}"
                                        {{ in_array($day->value, old('days_to_serve', $contract?->days_to_serve ?? [])) ? 'checked' : '' }}
                                    >
                                    <label class="d-flex justify-content-between align-items-center btn btn-outline-secondary text-muted btn-sm" for="day-{{ $day->value }}">
                                        <i class="bi bi-calendar3 me-2"></i>
                                        {{ $day->shortLabel() }}
                                    </label>
                                @endforeach
                            </div>
                            
                            {{-- Deselect All Days --}}
                            <button type="button" id="deselect-all-days" class="btn btn-outline-danger btn-sm rounded-2" style="padding: 0.375rem 0.75rem;">
                                <i class="bi bi-x-lg me-1"></i>
                                Limpiar
                            </button>
                        </div>
                        
                        {{-- Error Message --}}
                        <div id="days_to_serve-error" class="invalid-feedback d-block">
                            <strong>
                                @if ($errors->has('days_to_serve'))
                                    {{ $errors->first('days_to_serve') }}
                                @endif
                            </strong>
                        </div>

                        {{-- Help Text --}}
                        <small class="text-muted d-block">
                            Los días seleccionados determinan qué detalles se pueden agregar.
                        </small>
                    </div>

                    {{-- Contract Value --}}
                    <div class="col-md-5">
                        <x-form.input 
                            :id="'total_value'"
                            :type="'number'"
                            :min="0"
                            :step="1"
                            :class="'border-secondary w-auto'"
                            :inputClass="$errors->has('total_value') ? 'is-invalid' : ''"
                            :placeholder="'Ej: 1500.00'"
                            :value="old('total_value', $contract?->total_value ?? 0)"
                            :errorMessage="$errors->first('total_value') ?? ''"
                            :textIconLeft="true"
                            :required="true"
                        >
                            <x-slot:iconLeft>
                                <x-icons.colon-icon width="14" height="14" />
                            </x-slot:iconLeft>

                            Valor Total del Contrato <span class="text-danger">*</span>

                            <x-slot:buttonIconRight>
                                <button id="calculate-contract-value-btn" type="button" class="btn btn-sm btn-outline-primary rounded-end-2" data-bs-toggle="tooltip" title="Calcular automáticamente basado en los detalles agregados">
                                    <div class="calculate-contract-value-spinner d-none mx-2">
                                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                    </div>
                                    <div class="calculate-contract-value-button-text d-flex align-items-center gap-1">
                                        <i class="bi bi-calculator mx-1"></i>
                                    </div>
                                </button>
                            </x-slot:buttonIconRight>
                        </x-form.input>

                        <small class="text-muted d-block mt-2">
                            Se puede calcular o ingresar manualmente.
                        </small>
                    </div>
                </div>

            </div>

            {{-- Contract Information --}}
            <div id="contract-details" class="card-container justify-content-start rounded-2 p-4">
                
                {{-- Contract Details Information Header --}}
                <div class="d-flex justify-content-between align-items-center gap-2 pb-3 mb-3 border-bottom border-secondary">
                    <div class="d-flex flex-column">
                        <span class="d-flex align-items-center gap-3 fs-5 fw-bold">
                            <i class="bi bi-list-ul"></i>
                            Detalles del Contrato
                        </span>
                        <small class="text-muted d-block">
                            Agregue detalles específicos para cada día de servicio seleccionado.
                        </small>
                    </div>

                    {{-- Added Items Badge & Add Row Button --}}
                    <div class="d-flex flex-column justify-content-between align-items-end gap-2">
                        <span class="badge border rounded-pill text-info-emphasis bg-info-subtle px-3 ms-2">
                            <span id="added-items-badge">{{ $contract?->details?->count() ?? 0 }}</span> item/s
                        </span>

                        <button id="btn-add-row" class="btn btn-sm btn-outline-primary rounded-2" type="button" data-bs-toggle="tooltip" data-bs-title="Agregar nuevo detalle al contrato">
                            <i class="bi bi-plus-lg me-1"></i>
                            Agregar fila
                        </button>
                    </div>
                </div>

                <div class="table-responsive border border-1 border-bottom-0 rounded-2 rounded-bottom-0">
                    <table id="contract-details-table" class="table table-hover align-middle mb-0" style="min-width: 600px;">

                        <thead class="table-subtle text-secondary-emphasis">
                            <tr>
                                <th style="width:30%">Producto</th>
                                <th style="width:22%">Tiempo de Comida</th>
                                <th style="width:22%">Fecha de Servicio</th>
                                <th style="width:18%">Precio Unit.</th>
                                <th style="width:8%">Acc.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($contract?->details?->isNotEmpty())
                                @foreach ($contract->details as $contractDetail)
                                    @php $productPrice = 0; @endphp
                                    <tr data-contract-detail-id="{{ $contractDetail->id }}">
                                        <td>
                                            {{-- Product Selection --}}
                                            <x-form.select
                                                :name="'product_id'"
                                                :class="'border-secondary w-auto text-start'"
                                                :selectClass="$errors->has('product_id') ? 'is-invalid' : ''"
                                                :errorMessage="$errors->first('product_id') ?? ''"
                                                :style="'font-size: 0.9rem;'"
                                                :labelClass="'d-none'"
                                                :inputSm="true"
                                            >
                                                <x-slot:options>
                                                    <option value="-1">Seleccione un producto</option>
                                                    @foreach($products as $product)
                                                        @php 
                                                            if ($product->id == $contractDetail->product_id) {
                                                                $productPrice = $product->sale_price;
                                                            }
                                                        @endphp
                                                        <option value="{{ $product->id }}" data-price="{{ $product->sale_price }}" {{ old('product_id', $contractDetail->product_id) == $product->id ? 'selected' : '' }}>
                                                            {{ $product->name }}
                                                        </option>
                                                    @endforeach
                                                </x-slot:options>
                                            </x-form.select>
                                        </td>
                                        <td>
                                            {{-- Meal Time Selection --}}
                                            <x-form.select
                                                :name="'meal_time'"
                                                :class="'border-secondary w-auto text-start'"
                                                :selectClass="$errors->has('meal_time') ? 'is-invalid' : ''"
                                                :errorMessage="$errors->first('meal_time') ?? ''"
                                                :style="'font-size: 0.9rem;'"
                                                :labelClass="'d-none'"
                                                :inputSm="true"
                                            >
                                                <x-slot:options>
                                                    <option value="-1">Seleccione un tiempo de comida</option>
                                                    @foreach ($mealTimes as $mealTime)
                                                        <option value="{{ $mealTime->value }}" {{ old('meal_time', $contractDetail->meal_time) == $mealTime->value ? 'selected' : '' }}>
                                                            {{ $mealTime->label() }}
                                                        </option>
                                                    @endforeach
                                                </x-slot:options>
                                            </x-form.select>
                                        </td>
                                        <td>
                                            {{-- Serve Date --}}
                                            <x-form.input 
                                                :name="'serve_date'"
                                                :type="'date'" 
                                                :class="'border-secondary w-auto'"
                                                :min="Carbon\Carbon::now()->timezone('America/Costa_Rica')->format('Y-m-d')"
                                                :inputClass="$errors->has('serve_date') ? 'is-invalid' : ''"
                                                :value="old('serve_date', $contractDetail->serve_date->format('Y-m-d'))"
                                                :errorMessage="$errors->first('serve_date') ?? ''"
                                                :labelClass="'d-none'"
                                                :inputSm="true"
                                            />
                                        </td>
                                        <td>
                                            {{-- Unit Price --}}
                                            <x-form.input 
                                                :name="'unit_price'"
                                                :type="'number'"
                                                :min="0"
                                                :step="0.01"
                                                :class="'border-secondary w-auto'"
                                                :inputClass="$errors->has('unit_price') ? 'quantity-input is-invalid' : 'quantity-input'"
                                                :placeholder="'Ej: 500.00'"
                                                :value="old('unit_price', $productPrice)"
                                                :errorMessage="$errors->first('unit_price') ?? ''"
                                                :textIconLeft="true"
                                                :labelClass="'d-none'"
                                                :inputSm="true"
                                                disabled
                                                readonly
                                            >
                                                <x-slot:iconLeft>
                                                    <x-icons.colon-icon width="12" height="12" />
                                                </x-slot:iconLeft>
                                            </x-form.input>
                                        </td>
                                        <td>
                                            {{-- Delete Button --}}
                                            <button type="button" class="action-btn btn btn-sm btn-outline-danger rounded-2" data-bs-toggle="tooltip" data-bs-title="Eliminar este detalle del contrato">
                                                <i class="bi bi-trash3 pointer-events-none"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            {{-- Empty Row --}}
                            <tr id="empty-row" class="{{ $contract?->details?->isNotEmpty() ? 'd-none' : '' }}">
                                <td colspan="5" class="text-center">
                                    <div class="empty-state text-secondary pt-2 pb-3">
                                        <i class="bi bi-inbox fs-1"></i>
                                        <p class="mb-1">No hay detalles agregados aún</p>
                                        <small>Agregue detalles específicos para cada día de servicio seleccionado.</small>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="accordion accordion-flush accordion-bambu" id="notes-accordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button py-2 collapsed" style="font-size: 0.83rem;" type="button" data-bs-toggle="collapse" data-bs-target="#colNote" aria-expanded="false">
                                <i class="bi bi-info-circle me-2"></i>
                                Restricciones de unicidad
                            </button>
                        </h2>
                        <div id="colNote" class="accordion-collapse collapse" data-bs-parent="#notes-accordion">
                            <div class="accordion-body py-2" style="font-size: 0.82rem;">
                                Cada combinación de <strong>producto + tiempo de comida + fecha</strong> debe ser única dentro del contrato. El sistema validará duplicados al guardar.
                            </div>
                        </div>
                    </div>
                </div>

            </div>
    
        </section>

        {{-- Right Column --}}
        <section class="col-lg-4">

            <div id="contract-summary" class="card-container justify-content-start rounded-2 p-4">
                {{-- Contract Summary Header --}}
                <div class="d-flex justify-content-between align-items-center gap-2 pb-3 mb-3 border-bottom border-secondary">
                    <div class="d-flex flex-column">
                        <span class="d-flex align-items-center gap-3 fs-5 fw-bold">
                            <i class="bi bi-clipboard-data"></i>
                            Resúmen del Contrato
                        </span>
                        <small class="text-muted d-block">
                            Resúmen de la información del contrato antes de guardar.
                        </small>
                    </div>
                </div>

                <div class="d-flex flex-column gap-3">
                    {{-- Contract Value --}}
                    <div class="bg-success-subtle border border-success rounded-2 text-success" style="padding: .85rem 1rem;">
                        <span class="text-uppercase fw-semibold" style="font-size: .72rem; letter-spacing: 0.06em;">
                            Valor del Contrato
                        </span>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <x-icons.colon-icon width="14" height="14" />
                            <span id="contract-summary-value" class="fs-4 fw-semibold lh-sm">
                                {{ $isEditing ? number_format($contract->total_value, 0, ',', ' ') : 0 }}
                            </span>
                        </div>
                    </div>

                    {{-- Active Days & Portions/day --}}
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="bg-success-subtle border border-success rounded-2 text-success" style="padding: .85rem 1rem;">
                                <span class="text-uppercase fw-semibold" style="font-size: .72rem; letter-spacing: 0.06em;">
                                    Días Activos
                                </span>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span id="contract-summary-days" class="fs-5 fw-semibold lh-sm text">
                                        {{ $isEditing ? count($contract->days_to_serve) . ' días' : '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-success-subtle border border-success rounded-2 text-success" style="padding: .85rem 1rem;">
                                <span class="text-uppercase fw-semibold" style="font-size: .72rem; letter-spacing: 0.06em;">
                                    Porciones/Día
                                </span>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span id="contract-summary-portions" class="fs-5 fw-semibold lh-sm">
                                        {{ $isEditing ? $contract->portions_per_day : '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Selected Client --}}
                    <div>
                        <p class="fw-semibold text-uppercase text-muted mb-2" style="font-size: .7rem; letter-spacing: .08em;">Cliente</p>
                        <div class="d-flex align-items-center gap-2" style="font-size: .85rem;">
                            <i class="bi bi-person text-muted"></i>
                            @isset($contract->client)
                                <span id="contract-summary-client" style="color: var(--bambu-logo-bg);">
                                    {{ $contract->client->full_name }}
                                </span>
                            @else
                                <span id="contract-summary-client">No seleccionado</span>
                            @endisset
                        </div>
                    </div>

                    {{-- Contract Period --}}
                    <div>
                        <p class="fw-semibold text-uppercase text-muted mb-2" style="font-size: .7rem; letter-spacing: .08em;">Per&iacute;odo</p>
                        <div class="d-flex align-items-center gap-2" style="font-size: .85rem;">
                            <i class="bi bi-calendar-range text-muted"></i>
                            <span id="contract-summary-period" @isset($contract->period) class="text-muted" @endif>
                                {{ $isEditing ? $contract->period : 'No definido' }}
                            </span>
                        </div>
                    </div>

                    <hr class="my-1">

                    {{-- Loaded Details --}}
                    <div>
                        <p class="fw-semibold text-uppercase text-muted mb-2" style="font-size: .7rem; letter-spacing: .08em;">Detalles Cargados</p>
                        <div id="contract-summary-meals" class="d-flex flex-column gap-1">
                            @if($contract?->details?->isNotEmpty())
                                @foreach ($contract->details as $detail)
                                    @php
                                        $color = match ($detail->meal_time) {
                                            MealTime::BREAKFAST => 'warning',
                                            MealTime::LUNCH => 'success',
                                            MealTime::DINNER => 'info',
                                            default => 'secondary',
                                        };
                                    @endphp

                                    <div class="d-flex justify-content-between align-items-center" style="font-size: .82rem;">
                                        <span class="badge border rounded-pill text-{{ $color }}-emphasis bg-{{ $color }}-subtle px-3 py-2" style="font-size: .72rem;">
                                            {{ $detail->meal_time ? MealTime::from($detail->meal_time->value)->label() : '—' }}
                                        </span>
                                        <span class="text-muted">{{ $detail->meal_time_count }} fila</span>
                                    </div>
                                @endforeach
                            @else
                                <span class="text-muted" style="font-size: .82rem;">Sin detalles aún.</span>
                            @endif
                        </div>
                    </div>

                    <hr class="my-1">

                    {{-- Progress --}}
                    <div>
                        <div class="d-flex justify-content-between mb-1" style="font-size: .8rem;">
                            <span class="text-muted">Completitud del formulario</span>
                            <span id="contract-progress" class="fw-medium" style="color: var(--bambu-logo-bg);">0%</span>
                        </div>
                        <div class="progress rounded-3" style="height: 6px;">
                            <div class="progress-bar rounded-3" id="contract-progress-bar" role="progressbar" style="width: 0%; background: var(--bambu-logo-bg);" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-grid gap-2 mt-1">
                        {{-- Save Button --}}
                        <x-form.button 
                            :id="$formId . '-button'" 
                            :class="'btn-primary px-4'" 
                            :spinnerId="$formId . '-spinner'" 
                            :loadingMessage="$isEditing ? 'Actualizando...' : 'Guardando...'"
                        >
                            <div id="{{ $formId . '-button-text' }}" class="d-flex flex-row align-items-center justify-content-center">
                                <i class="bi bi-check-circle me-2"></i>
                                {{ $isEditing ? 'Actualizar' : 'Guardar' }}
                            </div>
                        </x-form.button>

                        {{-- Cancel Button --}}
                        <a href="{{ route('contracts.index') }}" class="btn btn-outline-danger px-4">
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>

        </section>
    </div>

</form>

<div class="offcanvas offcanvas-end" data-bs-backdrop="static" tabindex="-1" id="create-offcanvas" aria-labelledby="offcanvas-label">

    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="offcanvas-title">Título del Offcanvas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body" id="offcanvas-body">
        <!-- Dynamic Content -->
    </div>

</div>

@section('scripts')
    <script type="text/javascript">
        window.CONTRACT_FORM_DATA = {
            formId: @json($formId),
            isEditing: @json($isEditing),
            paymentStatuses: @json(collect($paymentStatuses)
                ->map(fn($status) => [
                    'value' => $status->value, 'label' => $status->label()
                ])
            ),
            paymentMethods: @json(collect($paymentMethods)
                ->map(fn($method) => [
                    'value' => $method->value, 'label' => $method->label()
                ])
            ),
            mealTimes: @json(collect($mealTimes)
                ->map(fn($mealTime) => [
                    'value' => $mealTime->value, 'label' => $mealTime->label()
                ])
            ),
            weekDays: @json(collect($weekDays)
                ->map(fn($weekDay) => [
                    'value' => $weekDay->value, 'label' => $weekDay->label()
                ])
            ),
            products: @json($products->map(fn($product) => [
                'id' => $product->id, 
                'name' => $product->name,
                'price' => $product->sale_price
            ])),
            clients: @json($clients->map(fn($client) => [
                'id' => $client->id, 
                'full_name' => $client->full_name
            ])),
        };
    </script>

    @vite(['resources/js/models/contracts/form.js'])
@endsection