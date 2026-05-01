@php
    use App\Enums\MealTime;
    use App\Enums\PaymentStatus;
    use App\Enums\WeekDay;

    $pageTitle = isset($contract) ? 'Editar Contrato' : 'Nuevo Contrato';
    $pageSubtitle = isset($contract) ? 'Modifica la información del contrato existente' : 'Registra un nuevo contrato con un cliente';

    $formId = isset($contract) ? 'edit-contract-form' : 'create-contract-form';
    $actionUrl = isset($contract) ? route('contracts.update', $contract) : route('contracts.store');
    $paymentStatuses = [PaymentStatus::PENDING, PaymentStatus::PAID];
    $weekDays = WeekDay::cases();
    $mealTimes = MealTime::cases();
@endphp

<x-header title="{{ $pageTitle }}" subtitle="{{ $pageSubtitle }}" />

<form id="{{ $formId }}" action="{{ $actionUrl }}" method="POST" class="container-fluid px-0 pb-0">

    @csrf

    @if(isset($contract))
        @method('PUT')
    @endif

    <div class="row g-4 align-items-start">

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
                            :required="true"
                        >
                            Porciones por Día <span class="text-danger">*</span>
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
                            :value="old('start_date', isset($contract) ? $contract->start_date->format('Y-m-d') : '')"
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
                            :value="old('end_date', isset($contract) ? $contract->end_date->format('Y-m-d') : '')"
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
                        <div id="days-to-serve" class="check-button d-flex flex-wrap gap-2 mb-2">
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
                        <small class="text-muted d-block">
                            Los días seleccionados determinan qué detalles se pueden agregar.
                        </small>
                    </div>

                    {{-- Contract Value --}}
                    <div class="col-md-5">
                        <x-form.input 
                            :id="'contract_value'"
                            :type="'number'"
                            :min="0"
                            :step="1"
                            :class="'border-secondary w-auto'"
                            :inputClass="$errors->has('contract_value') ? 'is-invalid' : ''"
                            :placeholder="'Ej: 1500.00'"
                            :value="old('contract_value', $contract?->total_value ?? 0)"
                            :errorMessage="$errors->first('contract_value') ?? ''"
                            :textIconLeft="true"
                            :required="true"
                        >
                            <x-slot:iconLeft>
                                <x-icons.colon-icon width="14" height="14" />
                            </x-slot:iconLeft>

                            Valor Total del Contrato <span class="text-danger">*</span>
                        </x-form.input>
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

                    <div class="d-flex flex-column justify-content-between align-items-end gap-2">
                        <span class="badge border rounded-pill text-info-emphasis bg-info-subtle px-3 ms-2">
                            <span id="added-items-badge">{{ $contract?->details?->count() ?? 0 }}</span> item/s
                        </span>

                        <button id="btn-add-row" class="btn btn-sm btn-outline-primary rounded-2" type="button">
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
                                    <tr>
                                        <td>
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
                                            <button type="button" class="action-btn btn btn-sm btn-outline-danger rounded-2">
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