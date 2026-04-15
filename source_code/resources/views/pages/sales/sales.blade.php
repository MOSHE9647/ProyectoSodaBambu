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

			{{-- Search and Filter --}}
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
			            id="category-select"
			            name="category-select"
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

			{{-- Products List --}}
			<div id="products-list" class="overflow-y-auto pe-1">

				{{-- Products Grid --}}
			    <div id="products-grid" class="grid-container">
					@include('pages.sales._products-list', ['products' => $products])
			    </div>

			    {{-- Scroll Sentinel --}}
				<div id="products-scroll-sentinel" class="d-flex flex-column align-items-center p-4" style="height: 50px;">
				    <div id="loading-text" class="spinner-border text-primary" role="status" style="display: none;">
				        <span class="visually-hidden">Cargando...</span>
				    </div>
				</div>

				{{-- Skeleton Template (hidden, used for cloning) --}}
				<template id="skeleton-template">
				    @include('pages.sales._skeleton')
				</template>

			</div>

		</section>

		{{-- Sale Details Section --}}
		<section id="sales-section" class="table-container d-flex flex-column rounded-2 shadow-sm overflow-auto p-4" style="width: 30%;">

			{{-- Header --}}
			<div class="d-flex flex-row align-items-center justify-content-between fw-bold">
				<h5 class="mb-0">Revisar Orden</h5>
				<i class="bi bi-bag"></i>
			</div>
			<hr>

			{{-- Sale Details --}}
			<div id="sale-details" class="d-flex flex-column flex-grow-1 gap-3 pe-2 overflow-y-auto">
			    <div class="d-flex flex-column flex-grow-1 justify-content-center align-items-center text-center text-muted">
					<i class="bi bi-bag fs-1 mb-2"></i>
					<p>Selecciona un producto para agregarlo a la orden</p>
				</div>
			</div>
			<hr>

			{{-- Totals --}}
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

			{{-- Action Buttons --}}
			<div class="d-flex flex-column gap-2 mt-3">
				<button id="finalize-sale-btn" class="btn btn-success w-100" style="font-size: 15px;" disabled>Proceder al pago</button>
				<button id="clear-sale-btn" class="btn btn-outline-secondary w-100 p-1" style="font-size: 15px;" disabled>Limpiar orden</button>
			</div>
			
		</section>
	</div>
@endsection

@section('scripts')
	@vite(['resources/js/pages/sales/main.js'])
@endsection