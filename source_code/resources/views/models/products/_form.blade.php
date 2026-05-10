@php
    use App\Enums\ProductType;

    // Detects if the form is being rendered inside an offcanvas
    $isOffcanvas ??= false;

    // State variables
    $categories ??= collect();
    $selectedType = old('type', $product?->type?->value ?? '-1');
    $selectedHasInventory = (string) old('has_inventory', (int) $product?->has_inventory ?? 0);
    $selectedCategory = (string) old('category_id', $product?->category_id ?? '-1');
    
    // Form configuration
    $formId = isset($product) ? 'edit-product-form' : 'create-product-form';
    $actionUrl = $action ?? route('products.store');
@endphp

@if(!$isOffcanvas)
    {{-- Header for regular page --}}
    <x-header 
        title="{{ isset($product) ? 'Editar Producto' : 'Crear Producto' }}" 
        subtitle="{{ isset($product) ? 'Modifica la información del producto existente' : 'Agregue un nuevo producto al sistema' }}" 
    />

    {{-- Form Container --}}
    <div class="card-container rounded-2 p-4 w-75 justify-content-start">
@else
    {{-- Container for offcanvas (full width, no card styling) --}}
    <div class="container-fluid px-0">
@endif

    <form id="{{ $formId }}" data-product-type="{{ $selectedType }}" action="{{ $actionUrl }}" method="POST" class="d-flex flex-column gap-3">
        @csrf
        @if(isset($product))
            @method('PUT')
        @endif

        {{-- SECTION: Product Information --}}
        <section id="product-information" class="d-flex flex-column gap-3">
            @if(!$isOffcanvas)
                <h5 class="text-muted pb-3 border-bottom border-secondary">
                    <i class="bi bi-box me-3"></i>
                    Información del Producto
                </h5>
            @endif

            <div class="row g-3">
                {{-- Barcode --}}
                <div class="col-12">
                    <x-form.input
                        :id="'barcode'"
                        :type="'text'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('barcode') ? 'is-invalid' : ''" 
                        :errorMessage="$errors->first('barcode') ?? ''" 
                        :value="old('barcode', $product?->barcode ?? '')" 
                        :placeholder="'Ej: 1234567890123'" 
                        :iconLeft="'bi bi-upc-scan'"
                        :required="false"
                    >
                        Código de Barras
                        <x-slot:buttonIconRight>
                            <button id="generate-barcode-btn" type="button" class="btn btn-sm btn-outline-primary rounded-end-2" data-bs-toggle="tooltip" title="Generar Código">
                                <div class="generate-barcode-spinner d-none mx-2">
                                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                </div>
                                <div class="generate-barcode-button-text d-flex align-items-center gap-1">
                                    <i class="bi bi-upc me-1"></i> Generar
                                </div>
                            </button>
                        </x-slot:buttonIconRight>
                    </x-form.input>
                    <small class="text-muted">Si se deja vacío, se mostrará como N/A.</small>
                </div>
            </div>

            <div class="row g-3">
                {{-- Name --}}
                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.input 
                        :id="'name'" 
                        :type="'text'"
                        :class="'border-secondary'" 
                        :inputClass="$errors->has('name') ? 'is-invalid' : ''" 
                        :placeholder="'Ej: Gallo Pinto Especial'"
                        :value="old('name', $product?->name ?? '')"
                        :errorMessage="$errors->first('name') ?? ''"
                        :iconLeft="'bi bi-type'"
                        :required="true"
                    >
                        Nombre del Producto <span class="text-danger">*</span>
                    </x-form.input>
                </div>
                
                {{-- Category --}}
                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.select 
                        :id="'category_id'" 
                        :class="'border-secondary'"
                        :selectClass="$errors->has('category_id') ? 'is-invalid' : ''" 
                        :errorMessage="$errors->first('category_id') ?? ''" 
                        :iconLeft="'bi bi-tags'" 
                        :required="true"
                    >
                        Categoría <span class="text-danger">*</span>
                        <x-slot:options>
                            <option value="-1">Seleccione una categoría</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $selectedCategory == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </x-slot:options>
                        <x-slot:buttonIconRight>
                            <button 
                                id="add-category-btn"
                                type="button"
                                class="btn btn-sm btn-outline-primary rounded-end-2"
                                data-bs-toggle="tooltip"
                                data-bs-title="Agregar nueva categoría"
                                data-type="category"
                            >
                                <div class="add-category-spinner d-none flex-row align-items-center justify-content-center mx-2">
                                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                </div>

                                <div class="add-category-button-text d-flex flex-row align-items-center justify-content-center gap-1">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    <span>Nuevo</span>
                                </div>
                            </button>
                        </x-slot:buttonIconRight>
                    </x-form.select>
                </div>
            </div>

            <div class="row g-3">
                {{-- Product Type --}}
                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.select
                        :id="'type'"
                        :class="'border-secondary'"
                        :selectClass="$errors->has('type') ? 'is-invalid' : ''"
                        :errorMessage="$errors->first('type') ?? ''"
                        :iconLeft="'bi bi-tag'"
                        :required="true"
                    >
                        Tipo de Producto <span class="text-danger">*</span>
                        <x-slot:options>
                            <option value="-1">Seleccione el tipo</option>
                            @foreach (ProductType::cases() as $type)
                                <option value="{{ $type->value }}" {{ $selectedType === $type->value ? 'selected' : '' }}>
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </x-slot:options>
                    </x-form.select>
                </div>

                {{-- Has Inventory --}}
                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.select
                        :id="'has_inventory'"
                        :class="'border-secondary'"
                        :selectClass="$errors->has('has_inventory') ? 'is-invalid' : ''"
                        :errorMessage="$errors->first('has_inventory') ?? ''" 
                        :iconLeft="'bi bi-boxes'" 
                        :required="true"
                    >
                        Maneja Inventario? <span class="text-danger">*</span>
                        <x-slot:options>
                            <option value="1" {{ $selectedHasInventory === '1' ? 'selected' : '' }}>Si</option>
                            <option value="0" {{ $selectedHasInventory === '0' ? 'selected' : '' }}>No</option>
                        </x-slot:options>
                    </x-form.select>
                </div>
            </div>

            <div id="inventory-stock-row" class="row g-3 {{ $selectedHasInventory === '1' ? '' : 'd-none' }}">
                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.input
                        :id="'current_stock'"
                        :type="'number'"
                        :step="'1'"
                        :min="'0'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('current_stock') ? 'is-invalid' : ''"
                        :errorMessage="$errors->first('current_stock') ?? ''"
                        :value="old('current_stock', $product?->stock?->current_stock ?? '')"
                        :placeholder="'Ej: 25'"
                        :iconLeft="'bi bi-stack'"
                        :textIconRight="true"
                        :required="false"
                    >
                        Stock Actual <span class="text-danger">*</span>
    
                        <x-slot:iconRight>
                            <i 
                                class="bi bi-question-circle"
                                data-bs-toggle="tooltip"
                                data-bs-title="Cantidad actual de unidades existentes para este producto"
                            ></i>
                        </x-slot:iconRight>
                    </x-form.input>
                </div>
    
                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.input
                        :id="'minimum_stock'"
                        :type="'number'"
                        :step="'1'"
                        :min="'0'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('minimum_stock') ? 'is-invalid' : ''"
                        :errorMessage="$errors->first('minimum_stock') ?? ''"
                        :value="old('minimum_stock', $product?->stock?->minimum_stock ?? '')"
                        :placeholder="'Ej: 10'"
                        :iconLeft="'bi bi-exclamation-triangle'"
                        :textIconRight="true"
                        :required="false"
                    >
                        Stock Mínimo <span class="text-danger">*</span>
    
                        <x-slot:iconRight>
                            <i 
                                class="bi bi-question-circle"
                                data-bs-toggle="tooltip"
                                data-bs-title="Cantidad mínima de unidades para este producto antes de generar una alerta de reabastecimiento"
                            ></i>
                        </x-slot:iconRight>
                    </x-form.input>
                </div>
            </div>

            <div id="expiration-fields-row" class="row g-3 {{ $selectedType === ProductType::MERCHANDISE->value ? '' : 'd-none' }}">
                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.input
                        :id="'expiration_date'"
                        :type="'date'"
                        :class="'border-secondary'"
                        :min="Carbon\Carbon::now()->format('Y-m-d')"
                        :inputClass="$errors->has('expiration_date') ? 'is-invalid' : ''"
                        :errorMessage="$errors->first('expiration_date') ?? ''"
                        :value="old('expiration_date', $product?->expiration_date ? $product->expiration_date->format('Y-m-d') : '')"
                        :placeholder="'Fecha de vencimiento'"
                        :iconLeft="'bi bi-calendar-event'"
                        :required="false"
                    >
                        Fecha de Vencimiento <span class="text-danger">*</span>
                    </x-form.input>
                </div>
    
                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-6' }}">
                    <x-form.input 
                        :id="'expiration_alert_days'"
                        :type="'number'" 
                        :step="'1'"
                        :min="'0'"
                        :maxLength="'3'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('expiration_alert_days') ? 'is-invalid' : ''"
                        :errorMessage="$errors->first('expiration_alert_days') ?? ''"
                        :value="old('expiration_alert_days', $product?->expiration_alert_days ?? 7)"
                        :placeholder="'Ej: 7'"
                        :iconLeft="'bi bi-bell'"
                        :textIconRight="true"
                        :required="false"
                    >
                        Alertar con (días) <span class="text-danger">*</span>
    
                        <x-slot:iconRight>
                            <i 
                                class="bi bi-question-circle"
                                data-bs-toggle="tooltip"
                                data-bs-title="Número de días antes de la fecha de vencimiento para recibir una alerta"
                            ></i>
                        </x-slot:iconRight>
                    </x-form.input>
                    <small class="form-text text-muted {{ $product?->expiration_alert_date ? '' : 'd-none' }}" id="expiration-alert-date-container">
                        <span class="fw-bold">Fecha de alerta: </span>
                        <span id="expiration-alert-date">
                            {{ $product?->expiration_alert_date ? $product->expiration_alert_date->translatedFormat('j \d\e F \d\e Y') : '' }}
                        </span>
                    </small>
                </div>
            </div>

            <div id="merchandise-related-row" class="row g-3 {{ $selectedType === ProductType::MERCHANDISE->value ? '' : 'd-none' }}">
                {{-- Reference Cost --}}
                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-4' }}">
                    <x-form.input 
                        :id="'reference_cost'"
                        :type="'number'"
                        :step="'5'"
                        :min="'0'"
                        :maxLength="'10'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('reference_cost') ? 'is-invalid' : ''"
                        :errorMessage="$errors->first('reference_cost') ?? ''"
                        :value="old('reference_cost', $product?->reference_cost ?? '')"
                        :placeholder="'Ej: 1200'"
                        :iconLeft="'bi bi-cash-coin'"
                        :required="false"
                    >
                        Costo de Referencia (₡) <span class="text-danger">*</span>
                    </x-form.input>
                </div>
    
                {{-- Tax Percentage --}}
                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-4' }}">
                    <x-form.input
                        :id="'tax_percentage'"
                        :type="'number'"
                        :step="'1'"
                        :min="'0'"
                        :max="'100'"
                        :maxLength="'3'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('tax_percentage') ? 'is-invalid' : ''"
                        :errorMessage="$errors->first('tax_percentage') ?? ''"
                        :value="old('tax_percentage', $product?->tax_percentage ?? '')"
                        :placeholder="'Ej: 13'"
                        :iconLeft="'bi bi-percent'"
                        :required="false"
                    >
                        Impuesto (%) <span class="text-danger">*</span>
                    </x-form.input>
                </div>
    
                {{-- Margin Percentage --}}
                <div class="{{ $isOffcanvas ? 'col-12' : 'col-12 col-md-4' }}">
                    <x-form.input
                        :id="'margin_percentage'"
                        :type="'number'"
                        :step="'1'"
                        :min="'0'"
                        :max="'100'"
                        :maxLength="'3'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('margin_percentage') ? 'is-invalid' : ''"
                        :errorMessage="$errors->first('margin_percentage') ?? ''"
                        :value="old('margin_percentage', $product?->margin_percentage ?? '')"
                        :placeholder="'Ej: 35'"
                        :iconLeft="'bi bi-graph-up-arrow'"
                        :textIconRight="true"
                        :required="false"
                    >
                        Margen (%) <span class="text-danger">*</span>
    
                        <x-slot:iconRight>
                            <i 
                                class="bi bi-question-circle"
                                data-bs-toggle="tooltip"
                                data-bs-title="Porcentaje de margen de ganancia sobre el costo de referencia."
                            ></i>
                        </x-slot:iconRight>
                    </x-form.input>
                </div>
            </div>

            <div class="row g-3">
                {{-- Sale Price --}}
                <div class="col-12">
                    <x-form.input
                        :id="'sale_price'"
                        :type="'number'"
                        :step="'5'"
                        :min="'0'"
                        :maxLength="'10'"
                        :class="'border-secondary'"
                        :inputClass="$errors->has('sale_price') ? 'is-invalid' : ''"
                        :errorMessage="$errors->first('sale_price') ?? ''"
                        :value="old('sale_price', $product?->sale_price ?? '')"
                        :placeholder="'Ej: 4150'"
                        :textIconLeft="true"
                        :textIconRight="true"
                        :required="true"
                    >
                        <x-slot:iconLeft>
                            <x-icons.colon-icon width="16" height="16" />
                        </x-slot:iconLeft>
    
                        Precio de Venta <span class="text-danger">*</span>
    
                        <x-slot:iconRight>
                            <i 
                                class="bi bi-question-circle"
                                data-bs-toggle="tooltip"
                                data-bs-title="Precio al que se venderá el producto. Para mercadería, se calcula automáticamente en base al costo de referencia, impuesto y margen."
                            ></i>
                        </x-slot:iconRight>
                    </x-form.input>

                    {{-- Hidden Input --}}
                    <input type="hidden" id="sale_price_hidden" name="sale_price" value="{{ old('sale_price', $product?->sale_price ?? '') }}">
                </div>
            </div>

        </section>

        {{-- Form Actions --}}
        <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
            @if(! $isOffcanvas)
                <a href="{{ route('products.index') }}" class="btn btn-outline-danger px-4">Cancelar</a>
            @endif
            
            {{-- Submit Button --}}
            <x-form.button 
                :id="'create-product-form-button'"
                :spinnerId="'create-product-form-spinner'"
                :class="'btn-primary px-4'"
                :loadingMessage="'Guardando...'"
            >
                <div id="create-product-form-button-text" class="d-flex flex-row align-items-center justify-content-center">
                    <i class="bi bi-check-circle me-2"></i>
                    Guardar
                </div>
            </x-form.button>
        </div>
    </form>
</div>

@section('scripts')
    @vite(['resources/js/models/products/form.js'])
@endsection