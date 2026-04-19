@php
	use App\Enums\ProductType;

	$categories ?? collect();
	$selectedType = old('type', isset($product) ? ($product->type?->value ?? $product->type) : '');
	$selectedHasInventory = (string) old('has_inventory', isset($product) ? (int) $product->has_inventory : 0);
	$selectedCategory = (string) old('category_id', isset($product) ? $product->category_id : '-1');
	$stockSource = $productStock ?? (isset($product) ? $product->stock : null);
	$selectedCurrentStock = old('current_stock', $stockSource?->current_stock ?? 0);
	$selectedMinimumStock = old('minimum_stock', $stockSource?->minimum_stock ?? '');
	$showInventoryStock = $selectedHasInventory === '1';
	$showMerchandiseExpiration = $selectedType === ProductType::MERCHANDISE->value;
	$selectedExpirationDate = old('expiration_date', isset($product) ? ($product->expiration_date?->format('Y-m-d') ?? '') : '');
	$selectedExpirationAlertDays = old('expiration_alert_days', isset($product) ? ($product->expiration_alert_days ?? 7) : 7);
	$defaultMargin = $selectedType === ProductType::MERCHANDISE->value
		? old('margin_percentage', isset($product) ? ($product->margin_percentage ?? '') : '0.35')
		: old('margin_percentage', isset($product) ? ($product->margin_percentage ?? '') : '');
@endphp

{{-- Page Header --}}
<x-header title="{{ isset($product) ? 'Editar Producto' : 'Crear Producto' }}" subtitle="{{
			isset($product) ? 'Modifica la informacion del producto existente'
							: 'Agregue un nuevo producto al sistema'
		}}" />

{{-- Form Container --}}
<div class="table-container rounded-2 p-4 w-75 justify-content-start">
    <form id="{{ isset($product) ? 'edit-product-form' : 'create-product-form' }}" data-product-type="{{ $selectedType }}" action="{{ $action }}" method="POST" class="d-flex flex-column gap-2">
        {{-- CSRF Token --}}
        @csrf
        @if(isset($product))
        @method('PUT')
        @endif

        {{-- SECTION: Product Information --}}
        <section id="product-information" class="d-flex flex-column mb-4 gap-3">
            <h5 class="text-muted pb-3 border-bottom border-secondary">
                <i class="bi bi-box me-3"></i>
                Informacion del Producto
            </h5>

            <div class="row g-3">
                {{-- Barcode --}}
                <div class="col-6">
                    <x-form.input :id="'barcode'" :type="'text'" :class="'border-secondary'" :inputClass="$errors->has('barcode') ? 'is-invalid' : ''" :placeholder="'Opcional'" :value="old('barcode', $product->barcode ?? '')" :errorMessage="$errors->first('barcode') ?? ''" :iconLeft="'bi bi-upc-scan'" :required="false">
                        Código de Barras
                    </x-form.input>
                    <small class="text-muted">Si lo deja vacío, el sistema lo mostrará como N/A.</small>
                </div>

                {{-- Name --}}
                <div class="col-6">
                    <x-form.input :id="'name'" :type="'text'" :class="'border-secondary'" :inputClass="$errors->has('name') ? 'is-invalid' : ''" :placeholder="'Ej: Gallo Pinto Especial'" :value="old('name', $product->name ?? '')" :errorMessage="$errors->first('name') ?? ''" :iconLeft="'bi bi-box'" :required="true">
                        Nombre del Producto <span class="text-danger">*</span>
                    </x-form.input>
                </div>
            </div>

            <div class="row g-3">
                {{-- Product Type --}}
                <div class="col-12 col-md-6">
                    <x-form.select :id="'type'" :class="'border-secondary'" :selectClass="$errors->has('type') ? 'is-invalid' : ''" :errorMessage="$errors->first('type') ?? ''" :iconLeft="'bi bi-tags'" :required="true">
                        Tipo de Producto <span class="text-danger">*</span>
                        <x-slot:options>
                            <option value="-1">Seleccionar tipo...</option>
                            @foreach (ProductType::cases() as $typeEnum)
                            <option value="{{ $typeEnum->value }}" {{ $selectedType === $typeEnum->value ? 'selected' : '' }}>
                                {{ $typeEnum->label() }}
                            </option>
                            @endforeach
                        </x-slot:options>
                    </x-form.select>
                </div>

                {{-- Has Inventory --}}
                <div class="col-12 col-md-6">
                    <x-form.select :id="'has_inventory'" :class="'border-secondary'" :selectClass="$errors->has('has_inventory') ? 'is-invalid' : ''" :errorMessage="$errors->first('has_inventory') ?? ''" :iconLeft="'bi bi-boxes'" :required="true">
                        Maneja Inventario <span class="text-danger">*</span>
                        <x-slot:options>
                            <option value="1" {{ $selectedHasInventory === '1' ? 'selected' : '' }}>Si</option>
                            <option value="0" {{ $selectedHasInventory === '0' ? 'selected' : '' }}>No</option>
                        </x-slot:options>
                    </x-form.select>
                </div>
            </div>

            <div id="inventory-stock-row" class="row g-3 {{ $showInventoryStock ? '' : 'd-none' }}">
                <div class="col-12 col-md-6">
                    <x-form.input :id="'current_stock'" :type="'number'" :step="'1'" :min="'0'" :class="'border-secondary'" :inputClass="$errors->has('current_stock') ? 'is-invalid' : ''" :placeholder="'Ej: 25'" :value="$selectedCurrentStock" :errorMessage="$errors->first('current_stock') ?? ''" :iconLeft="'bi bi-archive'" :required="false">
                        Stock Actual <span id="current-stock-required" class="text-danger">*</span>
                    </x-form.input>
                </div>

                <div class="col-12 col-md-6">
                    <x-form.input :id="'minimum_stock'" :type="'number'" :step="'1'" :min="'0'" :class="'border-secondary'" :inputClass="$errors->has('minimum_stock') ? 'is-invalid' : ''" :placeholder="'Ej: 10'" :value="$selectedMinimumStock" :errorMessage="$errors->first('minimum_stock') ?? ''" :iconLeft="'bi bi-exclamation-triangle'" :required="false">
                        Stock Mínimo <span id="minimum-stock-required" class="text-danger">*</span>
                    </x-form.input>
                </div>
            </div>

            <div id="expiration-fields-row" class="row g-3 {{ $showMerchandiseExpiration ? '' : 'd-none' }}">
                <div id="expiration-date-group" class="col-12 col-md-6">
                    <x-form.input :id="'expiration_date'" :type="'date'" :class="'border-secondary'" :inputClass="$errors->has('expiration_date') ? 'is-invalid' : ''" :placeholder="'Fecha de vencimiento'" :value="$selectedExpirationDate" :errorMessage="$errors->first('expiration_date') ?? ''" :iconLeft="'bi bi-calendar-event'" :required="$showMerchandiseExpiration">
                        Fecha de Vencimiento <span id="merchandise-expiration-date-required" class="text-danger">*</span>
                    </x-form.input>
                </div>

                <div id="expiration-alert-days-group" class="col-12 col-md-6">
                    <x-form.input :id="'expiration_alert_days'" :type="'number'" :step="'1'" :min="'0'" :maxLength="'3'" :class="'border-secondary'" :inputClass="$errors->has('expiration_alert_days') ? 'is-invalid' : ''" :placeholder="'Ej: 7'" :value="$selectedExpirationAlertDays" :errorMessage="$errors->first('expiration_alert_days') ?? ''" :iconLeft="'bi bi-bell'" :required="$showMerchandiseExpiration">
                        Días de Alerta de Vencimiento <span id="merchandise-alert-days-required" class="text-danger">*</span>
                    </x-form.input>
                    <small class="text-muted">Define con cuántos días antes se alertará un producto próximo a vencer.</small>
                </div>
            </div>

            <div class="row g-3">
                {{-- Reference Cost --}}
                <div id="reference-cost-group" class="col-12 col-md-4">
                    <x-form.input :id="'reference_cost'" :type="'number'" :step="'0.01'" :min="'0'" :maxLength="'10'" :class="'border-secondary'" :inputClass="$errors->has('reference_cost') ? 'is-invalid' : ''" :placeholder="'Ej: 1200.00'" :value="old('reference_cost', $product->reference_cost ?? '')" :errorMessage="$errors->first('reference_cost') ?? ''" :iconLeft="'bi bi-cash-coin'" :required="false">
                        Costo de Referencia <span id="merchandise-reference-cost-required" class="text-danger">*</span>
                    </x-form.input>
                </div>

                {{-- Tax Percentage --}}
                <div id="tax-percentage-group" class="col-12 col-md-4">
                    <x-form.input :id="'tax_percentage'" :type="'number'" :step="'0.01'" :min="'0'" :max="'1'" :maxLength="'6'" :class="'border-secondary'" :inputClass="$errors->has('tax_percentage') ? 'is-invalid' : ''" :placeholder="'Ej: 0.13'" :value="old('tax_percentage', $product->tax_percentage ?? '')" :errorMessage="$errors->first('tax_percentage') ?? ''" :iconLeft="'bi bi-percent'" :required="false">
                        Impuesto (%) <span id="merchandise-tax-required" class="text-danger">*</span>
                    </x-form.input>
                </div>

                {{-- Margin Percentage --}}
                <div id="margin-percentage-group" class="col-12 col-md-4">
                    <x-form.input :id="'margin_percentage'" :type="'number'" :step="'0.01'" :min="'0'" :max="'1'" :maxLength="'6'" :class="'border-secondary'" :inputClass="$errors->has('margin_percentage') ? 'is-invalid' : ''" :placeholder="'Ej: 0.35'" :value="old('margin_percentage', $defaultMargin)" :errorMessage="$errors->first('margin_percentage') ?? ''" :iconLeft="'bi bi-graph-up-arrow'" :required="false">
                        Margen (%) <span id="merchandise-margin-required" class="text-danger">*</span>
                    </x-form.input>
                    <small class="text-muted">Obligatorio solo para Mercadería.</small>
                </div>
            </div>

            <div class="row g-3">
                {{-- Sale Price --}}
                <div id="sale-price-group" class="col-12">
                    <x-form.input :id="'sale_price'" :type="'number'" :step="'0.01'" :min="'0'" :maxLength="'10'" :class="'border-secondary'" :inputClass="$errors->has('sale_price') ? 'is-invalid' : ''" :placeholder="'Ej: 4063.50'" :value="old('sale_price', $product->sale_price ?? '')" :errorMessage="$errors->first('sale_price') ?? ''" :iconLeft="'bi bi-cash-stack'" :required="in_array($selectedType, [ProductType::DISH->value, ProductType::DRINK->value, ProductType::PACKAGED->value], true)">
                        Precio de Venta <span id="sale-price-required" class="text-danger d-none">*</span>
                    </x-form.input>
                    <small id="sale-price-help" class="text-muted">Para Mercadería este precio se calcula automáticamente</small>
                </div>
            </div>

            <div class="row g-3">
                {{-- Category --}}
                <div class="col-12 col-md-6">
                    <x-form.select :id="'category_id'" :class="'border-secondary'" :selectClass="$errors->has('category_id') ? 'is-invalid' : ''" :errorMessage="$errors->first('category_id') ?? ''" :iconLeft="'bi bi-diagram-3'" :required="true">
                        Categoria <span class="text-danger">*</span>
                        <x-slot:options>
                            <option value="-1">Seleccionar categoria...</option>
                            @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ $selectedCategory === (string) $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </x-slot:options>
                    </x-form.select>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="button" id="open-create-category-modal" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#quick-create-category-modal">
                            <i class="bi bi-plus-circle me-1"></i>
                            Crear categoría rápida
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- Form Actions --}}
        <div class="d-flex justify-content-end gap-2">
            {{-- Cancel Button --}}
            <a href="{{ route('products.index') }}" class="btn btn-outline-danger px-4">
                Cancelar
            </a>

            {{-- Submit Button --}}
            <x-form.button :id="isset($product) ? 'edit-product-form-button' : 'create-product-form-button'" :spinnerId="isset($product) ? 'edit-product-form-spinner' : 'create-product-form-spinner'" :class="'btn-primary px-4'" :loadingMessage="isset($product) ? 'Actualizando...' : 'Guardando...'">
                <div id="{{ isset($product) ? 'edit-product-form-button-text' : 'create-product-form-button-text' }}" class="d-flex flex-row align-items-center justify-content-center">
                    <i class="bi bi-box me-2"></i>
                    {{ isset($product) ? 'Actualizar' : 'Guardar' }}
                </div>
            </x-form.button>
        </div>
    </form>
</div>

<div class="modal fade" id="quick-create-category-modal" tabindex="-1" aria-labelledby="quick-create-category-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quick-create-category-modal-label">
                    <i class="bi bi-tag-fill me-2"></i>
                    Nueva Categoría
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quick-create-category-form" class="d-flex flex-column">
                <div class="modal-body d-flex flex-column gap-3">
                    <div>
                        <label for="quick-category-name" class="form-label fw-semibold">
                            Nombre <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="quick-category-name" class="form-control border-secondary" placeholder="Ej: Bebidas frías" maxlength="255" required>
                        <small id="quick-category-name-error" class="text-danger d-none"></small>
                    </div>
                    <div>
                        <label for="quick-category-description" class="form-label fw-semibold">Descripción</label>
                        <textarea id="quick-category-description" class="form-control border-secondary" rows="3" placeholder="Descripción breve de la categoría" maxlength="255"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="quick-create-category-submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>
                        Guardar categoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
	@vite(['resources/js/models/products/form.js'])
@endsection
