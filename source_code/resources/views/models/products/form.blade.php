@php
	use App\Enums\ProductType;

	$categories = $categories ?? collect();
	$selectedType = old('type', isset($product) ? ($product->type?->value ?? $product->type) : '');
	$selectedHasInventory = (string) old('has_inventory', isset($product) ? (int) $product->has_inventory : 1);
	$selectedCategory = (string) old('category_id', isset($product) ? $product->category_id : '-1');
@endphp

<div class="container p-0">
	{{-- Page Header --}}
	<x-header
		title="{{ isset($product) ? 'Editar Producto' : 'Crear Producto' }}"
		subtitle="{{
			isset($product) ? 'Modifica la informacion del producto existente'
							: 'Agregue un nuevo producto al sistema'
		}}"
	/>

	{{-- Form Container --}}
	<div class="table-container rounded-2 p-4 w-75 justify-content-start">
		<form
			id="{{ isset($product) ? 'edit-product-form' : 'create-product-form' }}"
			action="{{ $action }}" method="POST" class="d-flex flex-column gap-2"
		>
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
					<div class="col-12 col-md-6">
						<x-form.input
							:id="'barcode'"
							:type="'text'"
							:class="'border-secondary'"
							:inputClass="$errors->has('barcode') ? 'is-invalid' : ''"
							:placeholder="'Ej: 1234567890123'"
							:value="old('barcode', $product->barcode ?? '')"
							:errorMessage="$errors->first('barcode') ?? ''"
							:iconLeft="'bi bi-upc-scan'"
							:required="true"
						>
							Codigo de Barras <span class="text-danger">*</span>
						</x-form.input>
					</div>

					{{-- Name --}}
					<div class="col-12 col-md-6">
						<x-form.input
							:id="'name'"
							:type="'text'"
							:class="'border-secondary'"
							:inputClass="$errors->has('name') ? 'is-invalid' : ''"
							:placeholder="'Ej: Gallo Pinto Especial'"
							:value="old('name', $product->name ?? '')"
							:errorMessage="$errors->first('name') ?? ''"
							:iconLeft="'bi bi-box'"
							:required="true"
						>
							Nombre del Producto <span class="text-danger">*</span>
						</x-form.input>
					</div>
				</div>

				<div class="row g-3">
					{{-- Product Type --}}
					<div class="col-12 col-md-6">
						<x-form.select
							:id="'type'"
							:class="'border-secondary'"
							:selectClass="$errors->has('type') ? 'is-invalid' : ''"
							:errorMessage="$errors->first('type') ?? ''"
							:iconLeft="'bi bi-tags'"
							:required="true"
						>
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
						<x-form.select
							:id="'has_inventory'"
							:class="'border-secondary'"
							:selectClass="$errors->has('has_inventory') ? 'is-invalid' : ''"
							:errorMessage="$errors->first('has_inventory') ?? ''"
							:iconLeft="'bi bi-boxes'"
							:required="true"
						>
							Maneja Inventario <span class="text-danger">*</span>
							<x-slot:options>
								<option value="1" {{ $selectedHasInventory === '1' ? 'selected' : '' }}>Si</option>
								<option value="0" {{ $selectedHasInventory === '0' ? 'selected' : '' }}>No</option>
							</x-slot:options>
						</x-form.select>
					</div>
				</div>

				<div class="row g-3">
					{{-- Sale Price --}}
					<div class="col-12 col-md-4">
						<x-form.input
							:id="'sale_price'"
							:type="'number'"
							:step="'0.01'"
							:min="'0'"
							:class="'border-secondary'"
							:inputClass="$errors->has('sale_price') ? 'is-invalid' : ''"
							:placeholder="'Ej: 3000.00'"
							:value="old('sale_price', $product->sale_price ?? '')"
							:errorMessage="$errors->first('sale_price') ?? ''"
							:iconLeft="'bi bi-currency-dollar'"
							:required="true"
						>
							Precio de Venta <span class="text-danger">*</span>
						</x-form.input>
					</div>

					{{-- Tax Percentage --}}
					<div class="col-12 col-md-4">
						<x-form.input
							:id="'tax_percentage'"
							:type="'number'"
							:step="'0.01'"
							:min="'0'"
							:class="'border-secondary'"
							:inputClass="$errors->has('tax_percentage') ? 'is-invalid' : ''"
							:placeholder="'Ej: 13.00'"
							:value="old('tax_percentage', $product->tax_percentage ?? '')"
							:errorMessage="$errors->first('tax_percentage') ?? ''"
							:iconLeft="'bi bi-percent'"
							:required="true"
						>
							Impuesto (%) <span class="text-danger">*</span>
						</x-form.input>
					</div>

					{{-- Reference Cost --}}
					<div class="col-12 col-md-4">
						<x-form.input
							:id="'reference_cost'"
							:type="'number'"
							:step="'0.01'"
							:min="'0'"
							:class="'border-secondary'"
							:inputClass="$errors->has('reference_cost') ? 'is-invalid' : ''"
							:placeholder="'Ej: 1200.00'"
							:value="old('reference_cost', $product->reference_cost ?? '')"
							:errorMessage="$errors->first('reference_cost') ?? ''"
							:iconLeft="'bi bi-cash-coin'"
							:required="true"
						>
							Costo de Referencia <span class="text-danger">*</span>
						</x-form.input>
					</div>
				</div>

				<div class="row g-3">
					{{-- Margin Percentage --}}
					<div class="col-12 col-md-6">
						<x-form.input
							:id="'margin_percentage'"
							:type="'number'"
							:step="'0.01'"
							:min="'0'"
							:class="'border-secondary'"
							:inputClass="$errors->has('margin_percentage') ? 'is-invalid' : ''"
							:placeholder="'Ej: 150.00'"
							:value="old('margin_percentage', $product->margin_percentage ?? '')"
							:errorMessage="$errors->first('margin_percentage') ?? ''"
							:iconLeft="'bi bi-graph-up-arrow'"
							:required="true"
						>
							Margen (%) <span class="text-danger">*</span>
						</x-form.input>
					</div>

					{{-- Category --}}
					<div class="col-12 col-md-6">
						<x-form.select
							:id="'category_id'"
							:class="'border-secondary'"
							:selectClass="$errors->has('category_id') ? 'is-invalid' : ''"
							:errorMessage="$errors->first('category_id') ?? ''"
							:iconLeft="'bi bi-diagram-3'"
							:required="true"
						>
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
				<x-form.submit
					:id="isset($product) ? 'edit-product-form-button' : 'create-product-form-button'"
					:spinnerId="isset($product) ? 'edit-product-form-spinner' : 'create-product-form-spinner'"
					:class="'btn-primary px-4'"
					:loadingMessage="isset($product) ? 'Actualizando...' : 'Guardando...'"
				>
					<div
						id="{{ isset($product) ? 'edit-product-form-button-text' : 'create-product-form-button-text' }}"
						class="d-flex flex-row align-items-center justify-content-center"
					>
						<i class="bi bi-box me-2"></i>
						{{ isset($product) ? 'Actualizar' : 'Guardar' }}
					</div>
				</x-form.submit>
			</div>
		</form>
	</div>
</div>

@section('scripts')
	@vite(['resources/js/models/products/form.js'])
@endsection
