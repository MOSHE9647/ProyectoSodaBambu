@extends('layouts.app')

@section('content')
	{{-- Header --}}
	<div class="d-flex flex-row align-items-center justify-content-between gap-2">
		<x-header title="Ventas" subtitle="Gestión y seguimiento de ventas" />
		
		{{-- Tabs Navigation --}}
		<x-scroll-tabs class="justify-content-end w-75">
		    
			<x-scroll-tabs.item :active="true" :id="'order-tab-001'">
		        ORD-0001
		    </x-scroll-tabs.item>

		</x-scroll-tabs>
	</div>

	{{-- Main Content --}}
	<div class="d-flex flex-row gap-3 justify-content-between" style="height: calc(100vh - 135px);">
		{{-- Products Section --}}
		<section id="products-section" class="table-container d-flex flex-column rounded-2 shadow-sm overflow-hidden p-4" style="width: 70%;">
			
			<div class="d-flex flex-row align-items-center justify-content-between fw-bold">
			    <h5 class="mb-0">Seleccione un producto</h5>
			    <i class="bi bi-box-seam"></i>
			</div>
			<hr>
			<div class="row mb-4 g-3">
			    <div class="col-md-8 mb-3 mb-md-0">
			        <x-form.input 
						id="product-search"
						name="product-search"
						type="search"
						class="border-secondary"
						placeholder="Buscar por producto o código de barras..."
						icon-left="bi bi-search"
						label-class="d-none"
						autocomplete="off"
						autofocus
					/>
			    </div>
			    <div class="col-md-4">
			        <x-form.select
			            id="categorySelect"
			            name="categorySelect"
			            class="border-secondary"
						label-class="d-none"
			        >
			            <x-slot:options>
							<option value="">Todas las categorías</option>
							@foreach($categories as $category)
								<option value="{{ $category->id }}">{{ $category->name }}</option>
							@endforeach
						</x-slot:options>
			        </x-form.select>
			    </div>
			</div>

			<div id="products-list" class="overflow-y-auto pe-1">
				@include('pages.sales._products-list', ['products' => $products])
			</div>

		</section>

		{{-- Sale Details Section --}}
		<section id="sales-section" class="table-container d-flex flex-column rounded-2 shadow-sm overflow-auto p-4" style="width: 30%;">

			<div class="d-flex flex-row align-items-center justify-content-between fw-bold">
				<h5 class="mb-0">Revisar Orden</h5>
				<i class="bi bi-bag"></i>
			</div>
			<hr>
			@php
				$testing = true;
				$testProducts = [
					['name' => 'Café Latte', 'price' => 2500, 'quantity' => 1],
					['name' => 'Croissant', 'price' => 1200, 'quantity' => 1],
					['name' => 'Sándwich de Jamón y Queso', 'price' => 3500, 'quantity' => 1],
					['name' => 'Té Verde', 'price' => 1800, 'quantity' => 1],
					['name' => 'Muffin de Arándanos', 'price' => 1500, 'quantity' => 1],
					['name' => 'Agua Mineral', 'price' => 800, 'quantity' => 1],
					['name' => 'Ensalada César', 'price' => 4000, 'quantity' => 1],
					['name' => 'Jugo de Naranja', 'price' => 2000, 'quantity' => 1],
				];
			@endphp
			@if (!$testing)
			<div id="sale-details" class="d-flex flex-column flex-grow-1 justify-content-center align-items-center text-center text-muted">
			    <i class="bi bi-bag fs-1 mb-2"></i>
				<p>Selecciona un producto para agregarlo a la orden</p>
			</div>
			@else
			<div id="sale-details" class="d-flex flex-column flex-grow-1 gap-3 pe-2 overflow-y-auto">
			    @foreach ($testProducts as $product)
				<div class="d-flex flex-row justify-content-between align-items-center gap-2 w-100">
				    <div class="d-flex flex-column text-start overflow-hidden flex-grow-1">
				        <span class="fw-bold text-truncate text-body" style="font-size: 0.95rem;">{{ $product['name'] }}</span>
				        <span class="text-body-secondary fw-medium" style="font-size: 0.85rem;">₡ {{ number_format($product['price'], 0, ',', '.') }} c/u</span>
				    </div>
				    <div class="d-flex flex-row align-items-center justify-content-end gap-2 flex-shrink-0">
				        <button class="btn border-0 p-0 d-flex align-items-center justify-content-center rounded-2" style="background-color: var(--bs-secondary-bg-subtle); color: var(--bs-body-color); width: 28px; height: 28px;">
				            <i class="bi bi-dash fs-6"></i>
				        </button>
				        <span class="text-center fw-semibold d-inline-block text-body" style="min-width: 18px; font-size: 0.95rem;">{{ $product['quantity'] }}</span>
				        <button class="btn border-0 p-0 d-flex align-items-center justify-content-center rounded-2" style="background-color: var(--bs-secondary-bg-subtle); color: var(--bs-body-color); width: 28px; height: 28px;">
				            <i class="bi bi-plus fs-6"></i>
				        </button>
				        <button class="btn btn-danger border-0 p-0 d-flex align-items-center justify-content-center rounded-2 ms-1" style="width: 28px; height: 28px;">
				            <i class="bi bi-trash"></i>
				        </button>
				    </div>
				</div>
				@endforeach
			</div>
			@endif
			<hr>
			<div class="d-flex flex-column gap-1">
				<div style="font-size: 0.8rem">
					<div class="d-flex justify-content-between text-muted">
					    <span>Impuestos</span>
					    <span id="sale-tax" class="text-end">₡ 0</span>
					</div>
					<div class="d-flex justify-content-between text-muted">
					    <span>Subtotal</span>
					    <span id="sale-subtotal" class="text-end">₡ 0</span>
					</div>
				</div>
				<div class="d-flex justify-content-between fw-bold fs-6">
					<span>Total</span>
					<span id="sale-total" class="text-success text-end">₡ 0</span>
				</div>
			</div>
			<div class="d-flex flex-column gap-2 mt-3">
				<button id="finalize-sale-btn" class="btn btn-success w-100" style="font-size: 15px;" disabled>Proceder al pago</button>
				<button id="clear-sale-btn" class="btn btn-outline-secondary w-100 p-1" style="font-size: 15px;" disabled>Limpiar orden</button>
			</div>
			
		</section>
	</div>
@endsection
