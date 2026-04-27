@php
	use App\Enums\ProductType;
@endphp

<form id="create-product-form" action="{{ route('products.store') }}" method="POST" class="d-flex flex-column gap-2">
    {{-- CSRF Token --}}
    @csrf

    {{-- SECTION: Product Information --}}
    <section id="product-information" class="d-flex flex-column mb-4 gap-3">
        <div class="row g-3">
            {{-- Barcode --}}
            <div class="col-auto w-100">
                <x-form.input 
                    :id="'barcode'" 
                    :type="'text'" 
                    :class="'border-secondary'" 
                    :inputClass="$errors->has('barcode') ? 'is-invalid' : ''" 
                    :placeholder="'Ej: 1234567890123'" 
                    :iconLeft="'bi bi-upc-scan'" 
                    :required="false"
                >
                    Código de Barras

                    <x-slot:buttonIconRight>
                    <button id="generate-barcode-btn" type="button" class="btn btn-sm btn-offcanvas btn-outline-primary rounded-end-2" data-bs-toggle="tooltip" data-bs-title="Generar Código de Barras">
                        <div class="generate-barcode-spinner d-none flex-row align-items-center justify-content-center mx-2">
                            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                        </div>

                        <div class="generate-barcode-button-text d-flex flex-row align-items-center justify-content-center gap-1">
                            <i class="bi bi-upc me-1"></i>
                            <span>Generar</span>
                        </div>
                    </button>
                    </x-slot:buttonIconRight>
                </x-form.input>
            </div>

            {{-- Name --}}
            <div class="col-auto w-100">
                <x-form.input 
                    :id="'name'" 
                    :type="'text'"
                    :class="'border-secondary'" 
                    :inputClass="$errors->has('name') ? 'is-invalid' : ''" 
                    :placeholder="'Ej: Gallo Pinto Especial'"
                    :iconLeft="'bi bi-type'"
                    :required="true"
                >
                    Nombre del Producto <span class="text-danger">*</span>
                </x-form.input>
            </div>
        </div>

        <div class="row g-3">
            {{-- Product Type --}}
            <div class="col-auto w-100">
                <x-form.select 
                    :id="'type'"
                    :class="'border-secondary'"
                    :selectClass="$errors->has('type') ? 'is-invalid' : ''"
                    :iconLeft="'bi bi-tag'"
                    :required="true"
                >
                    Tipo de Producto <span class="text-danger">*</span>

                    <x-slot:options>
                        <option value="-1">Seleccione el tipo de producto</option>
                        @foreach (ProductType::cases() as $typeEnum)
                        <option value="{{ $typeEnum->value }}" {{ $typeEnum == ProductType::MERCHANDISE ? 'selected' : '' }}>
                            {{ $typeEnum->label() }}
                        </option>
                        @endforeach
                    </x-slot:options>
                </x-form.select>
            </div>
            
            {{-- Category --}}
            <div class="col-auto w-100">
                <x-form.select :id="'category_id'" :class="'border-secondary'" :selectClass="$errors->has('category_id') ? 'is-invalid' : ''" :iconLeft="'bi bi-tags'" :required="true">
                    Categoria <span class="text-danger">*</span>

                    <x-slot:options>
                        <option value="-1">Seleccione una categoría</option>
                        @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </x-slot:options>

                    <x-slot:buttonIconRight>
                        <button id="add-category-btn" type="button" class="btn btn-sm btn-offcanvas btn-outline-primary rounded-end-2" data-bs-toggle="tooltip" data-bs-title="Agregar nueva categoría" data-type="category">
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

            {{-- Has Inventory --}}
            <div class="col-auto w-100">
                <x-form.select
                    :id="'has_inventory'"
                    :class="'border-secondary'"
                    :selectClass="$errors->has('has_inventory') ? 'is-invalid' : ''" 
                    :iconLeft="'bi bi-boxes'" 
                    :required="true"
                >
                    Maneja Inventario? <span class="text-danger">*</span>
                    <x-slot:options>
                        <option value="1" selected>Si</option>
                        <option value="0">No</option>
                    </x-slot:options>
                </x-form.select>
            </div>
        </div>

        <div id="inventory-stock-row" class="row g-3">
            <div class="col-auto w-100">
                <x-form.input
                    :id="'current_stock'"
                    :type="'number'"
                    :step="'1'"
                    :min="'0'"
                    :class="'border-secondary'"
                    :inputClass="$errors->has('current_stock') ? 'is-invalid' : ''"
                    :placeholder="'Ej: 25'"
                    :iconLeft="'bi bi-stack'"
                    :textIconRight="true"
                    :required="true"
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

            <div class="col-auto w-100">
                <x-form.input
                    :id="'minimum_stock'"
                    :type="'number'"
                    :step="'1'"
                    :min="'0'"
                    :class="'border-secondary'"
                    :inputClass="$errors->has('minimum_stock') ? 'is-invalid' : ''"
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

        <div id="expiration-fields-row" class="row g-3">
            <div class="col-auto w-100">
                <x-form.input
                    :id="'expiration_date'"
                    :type="'date'"
                    :class="'border-secondary'"
                    :inputClass="$errors->has('expiration_date') ? 'is-invalid' : ''"
                    :placeholder="'Fecha de vencimiento'"
                    :iconLeft="'bi bi-calendar-event'"
                    :required="false"
                >
                    Fecha de Vencimiento <span class="text-danger">*</span>
                </x-form.input>
            </div>

            <div class="col-auto w-100">
                <x-form.input 
                    :id="'expiration_alert_days'"
                    :type="'number'" 
                    :step="'1'"
                    :min="'0'"
                    :maxLength="'3'"
                    :class="'border-secondary'"
                    :inputClass="$errors->has('expiration_alert_days') ? 'is-invalid' : ''"
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
                <small class="text-muted">Fecha de alerta: <span id="expiration-alert-date"></span></small>
            </div>
        </div>

        <div class="row g-3">
            {{-- Reference Cost --}}
            <div class="col-auto w-100">
                <x-form.input 
                    :id="'reference_cost'"
                    :type="'number'"
                    :step="'0.01'"
                    :min="'0'"
                    :maxLength="'10'"
                    :class="'border-secondary'"
                    :inputClass="$errors->has('reference_cost') ? 'is-invalid' : ''"
                    :placeholder="'Ej: 1200.00'"
                    :iconLeft="'bi bi-cash-coin'"
                    :required="true"
                >
                    Costo de Referencia (₡) <span class="text-danger">*</span>
                </x-form.input>
            </div>

            {{-- Tax Percentage --}}
            <div class="col-auto w-100">
                <x-form.input
                    :id="'tax_percentage'"
                    :type="'number'"
                    :step="'1'"
                    :min="'0'"
                    :max="'100'"
                    :maxLength="'3'"
                    :class="'border-secondary'"
                    :inputClass="$errors->has('tax_percentage') ? 'is-invalid' : ''"
                    :placeholder="'Ej: 13'"
                    :iconLeft="'bi bi-percent'"
                    :required="true"
                >
                    Impuesto (%) <span class="text-danger">*</span>
                </x-form.input>
            </div>

            {{-- Margin Percentage --}}
            <div class="col-auto w-100">
                <x-form.input
                    :id="'margin_percentage'"
                    :type="'number'"
                    :step="'0.01'"
                    :min="'0'"
                    :max="'100'"
                    :maxLength="'3'"
                    :class="'border-secondary'"
                    :inputClass="$errors->has('margin_percentage') ? 'is-invalid' : ''"
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
                    :step="'0.01'"
                    :min="'0'"
                    :maxLength="'10'"
                    :class="'border-secondary'"
                    :inputClass="$errors->has('sale_price') ? 'is-invalid' : ''"
                    :placeholder="'Ej: 4063.50'"
                    :textIconLeft="true"
                    :textIconRight="true"
                    :required="false"
                >
                    <x-slot:iconLeft>
                        <x-icons.colon-icon width="16" height="16" />
                    </x-slot:iconLeft>

                    Precio de Venta <span class="text-danger">*</span>

                    <x-slot:iconRight>
                        <i 
                            class="bi bi-question-circle"
                            data-bs-toggle="tooltip"
                            data-bs-title="Precio de venta calculado automáticamente usando el costo de referencia, impuesto y margen."
                        ></i>
                    </x-slot:iconRight>
                </x-form.input>
            </div>
        </div>
    </section>

    {{-- Form Actions --}}
    <div class="d-flex justify-content-end gap-2">
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